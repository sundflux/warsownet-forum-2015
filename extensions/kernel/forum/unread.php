<?php
/**
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Copyright (C)
 * 2011 Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * The Initial Developer of the Original Code is
 * Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * Portions created by the Initial Developer are Copyright (C) 2011
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 */
/**
 * Handle unread topics/posts
 *
 * @package       Kernel
 * @subpackage    Forum
 */

class forum_unread
{
    private $lastvisit;
    private $view;

    public function __construct()
    {
        // Not really a correct place to autologin/redirect here, but no can do.

        // Check for autologin.
        Auth_AutologinCheck::autologin();

        // If still not set, redirect.
        if (!isset($_SESSION["UserID"])) {
            Controller_Redirect::to("forum_login?ref=".base64_encode("forum_unread"));
        }

        if (!isset($_SESSION["PreviousLogin"]) || empty($_SESSION["PreviousLogin"]) || $_SESSION["PreviousLogin"] == false) {
            $user = new Common_User;
            $this->lastvisit = $_SESSION["PreviousLogin"] = $user->getSetting("LastLogin");
            if (!is_numeric($_SESSION["PreviousLogin"])) {
                // Show posts from last 24 hours as 'new' for first login.
                $this->lastvisit = time() - (24 * 3600);
            }
        } else {
            // Show new posts since last visit
            $this->lastvisit = $_SESSION["PreviousLogin"];
        }

        // Get unread posts and write them to DB
        $this->prepare();
    }

    /**
     * Get new posts since PreviousLogin timestamp and write them to db as unread topics, update the PreviousLogin timestamp.
     *
     * @access      public
     * @uses        Common_User
     */
    private function prepare()
    {
        // Check permissions
        $groupSearch = "";
        if (isset($_SESSION["UserID"]) && isset($_SESSION["forum_allowed_groups"]) && is_array($_SESSION["forum_allowed_groups"])) {
            $groupSearch = " OR (forum_group.public = 0 AND forum_group.id IN (".implode(",", $_SESSION["forum_allowed_groups"]).")) ";
        }

        // Get posts since PreviousLogin timestamp.
        $query="
            SELECT forum_topic.id as topic_id
            FROM forum_topic, forum_forum, forum_group
            WHERE forum_topic.last_post > FROM_UNIXTIME(?) AND forum_topic.last_post_by != ? AND forum_topic.forum_id = forum_forum.id AND forum_forum.group_id=forum_group.id AND (forum_group.public=1 {$groupSearch}) AND forum_forum.id != ?";

        try {
            $spamForumID = Forum_Spam::getID();
            $stmt = db()->prepare($query);
            $stmt->set($this->lastvisit)->set($_SESSION["UserID"])->set($spamForumID)->execute();
        } catch (Exception $e) {

        }

        // Write unread topic id's to DB.
        if (isset($stmt)) {
            $query = "
                INSERT INTO  `forum_unread` (`id`,`user_id`,`topic_id`)
                VALUES (NULL,?,?)";

            $insert_stmt = db()->prepare($query);
            foreach ($stmt as $row) {
                try {
                    $insert_stmt->set($_SESSION["UserID"])->set($row->topic_id)->execute();
                } catch (Exception $e) {

                }
            }

            // Update last topic check date..
            $user = new Common_User;
            $_SESSION["PreviousLogin"] = time();
            $user->setSetting("LastLogin", $_SESSION["PreviousLogin"]);
        }
    }

    /**
     * Get unread topics for current user in session.
     *
     * @access      public
     * @uses        Forum_User
     * @return object
     */
    public function get()
    {
        // Get unread topics
        $query="
            SELECT DISTINCT forum_topic.id as topic_id, forum_topic.title as topic_title, forum_topic.last_post_by as user_id, forum_topic.last_post, forum_topic.last_post_id, forum_forum.name as forum_name
            FROM forum_unread, forum_topic, forum_forum
            WHERE forum_topic.forum_id=forum_forum.id AND forum_unread.topic_id=forum_topic.id AND forum_unread.user_id=? AND forum_forum.id != ?
            ORDER BY forum_forum.id";

        $stmt = db()->prepare($query);
        $stmt->set($_SESSION["UserID"]);
        $spamForumID = Forum_Spam::getID();
        $stmt->set($spamForumID);
        try {
            $stmt->execute();
        } catch (Exception $e) {

        }

        if (isset($stmt)) {
            // Sort forums by groups

            // key for forums
            $i = 0;

            // Number of new posts
            $ii = 0;
            foreach ($stmt as $row) {
                $ii++;

                // Sort posts by forum
                if (!isset($forums[$i]->forum) || ($row->forum_name != $forums[$i]->forum)) {
                    $i++;
                    $forums[$i] = new stdClass;
                    $forums[$i]->forum = $row->forum_name;
                }
                $forums[$i]->threads[] = $row;

                // Gather userid's so we can get them in separate query
                $userids[] = $row->user_id;
            }

            // Drop duplicate userid's off
            if (isset($userids)) {
                $userids = array_unique($userids);
            }
        }

        $retval = new stdClass;
        if (isset($ii)) {
            $_SESSION["UnreadPosts"] = $retval->numposts = $ii;
        }
        if (isset($forums)) {
            $retval->forums = $forums;
        }
        if (isset($userids)) {
            $retval->users = Forum_User::getProfile($userids);
        }

        // Get unread private messages count
        $pm = new Forum_PM($_SESSION["UserID"]);
        $_SESSION["UnreadPrivateMessages"] = $pm->getUnreadCount();

        return $retval;
    }

    /**
     * Mark given topic as read for current user in session.
     *
     * @access      public
     * @param int $topic_id Topic ID
     */
    public static function markAsRead($topic_id)
    {
        // Make sure nothing else than int can slip to the query
        $topic_id = (int) $topic_id;

        // Run only when user is logged in
        if (isset($_SESSION["UserID"]) && is_numeric($_SESSION["UserID"]) && is_numeric($topic_id)) {
            // We don't use prepared query here for performance reasons, but this should be safe ^^
            try {
                db()->exec("DELETE FROM forum_unread WHERE user_id={$_SESSION["UserID"]} AND topic_id={$topic_id}");
            } catch (Exception $e) {

            }
        }
    }

    /**
     * Mark all topics as read for current user in session.
     *
     * @access      public
     */
    public static function markAllRead()
    {
        // Run only when user is logged in
        if (isset($_SESSION["UserID"]) && is_numeric($_SESSION["UserID"])) {
            // We don't use prepared query here for performance reasons, but this should be safe ^^
            try {
                db()->exec("DELETE FROM forum_unread WHERE user_id={$_SESSION["UserID"]}");
            } catch (Exception $e) {

            }
        }
    }

    /**
     * Get amount of unread topics for current user in session.
     *
     * @access      public
     * @param int $unread Number of unread posts
     */
    public static function getUnreadCount()
    {
        if (isset($_SESSION["UserID"]) && is_numeric($_SESSION["UserID"]) && isset($_SESSION["UnreadPosts"])) {
            return $_SESSION["UnreadPosts"];
        }

        return 0;
    }

    /**
     * Get amount of unread private messages for current user in session.
     *
     * @access      public
     * @param int $unread Number of unread posts
     */
    public static function getUnreadPrivateMessageCount()
    {
        if (isset($_SESSION["UserID"]) && is_numeric($_SESSION["UserID"]) && isset($_SESSION["UnreadPrivateMessages"])) {
            return $_SESSION["UnreadPrivateMessages"];
        }

        return 0;
    }

}
