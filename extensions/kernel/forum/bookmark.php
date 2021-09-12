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
 * 2012 Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * The Initial Developer of the Original Code is
 * Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * Portions created by the Initial Developer are Copyright (C) 2012
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
 * Bookmark threads
 * @package       Kernel
 * @subpackage    Forum
 */

// maximum number of bookmarked topics to keep in session.
if(!defined('FORUM_MAX_BOOKMARKED_TOPICS'))  DEFINE('FORUM_MAX_BOOKMARKED_TOPICS', 256);

// bookmarked session id's get cached to this var
if(!isset($_SESSION["__bookmarked_topics"])) $_SESSION["__bookmarked_topics"] = array();

class forum_bookmark
{
    /**
     * Add bookmark
     *
     * @access      public
     * @param int $id      Topic ID
     * @param int $user_id User ID
     */
    public static function add($topic_id, $user_id = false)
    {
        if (!$user_id && isset($_SESSION["UserID"])) {
            $user_id = $_SESSION["UserID"];
        }

        if (!is_numeric($user_id) || !is_numeric($topic_id)) {
            throw new Exception("Malformed data");
        }

        $query="
            INSERT INTO forum_bookmark (user_id, topic_id)
            VALUES (?,?)";

        $stmt = db()->prepare($query);
        $stmt->set((int) $user_id);
        $stmt->set((int) $topic_id);
        try {
            $stmt->execute();
            $_SESSION["__bookmarked_topics"][$topic_id] = true;
        } catch (Exception $e) {

        }
    }

    /**
     * Delete bookmark
     *
     * @access      public
     * @param int $id      Topic ID
     * @param int $user_id User ID
     */
    public static function delete($topic_id, $user_id = false)
    {
        if (!$user_id && isset($_SESSION["UserID"])) {
            $user_id = $_SESSION["UserID"];
        }

        if (!is_numeric($user_id) || !is_numeric($topic_id)) {
            throw new Exception("Malformed data");
        }

        $_SESSION["__bookmarked_topics"][$topic_id] = false;

        $query="
            DELETE FROM forum_bookmark
            WHERE user_id = ? AND topic_id = ?";

        $stmt=db()->prepare($query);
        $stmt->set((int) $user_id);
        $stmt->set((int) $topic_id);
        try {
            $stmt->execute();
        } catch (Exception $e) {

        }
    }

    /**
     * Get bookmarks
     *
     * @access      public
     * @param int $user_id User ID
     */
    public static function get($user_id = false)
    {
        if (!$user_id && isset($_SESSION["UserID"])) {
            $user_id = $_SESSION["UserID"];
        }

        if (!is_numeric($user_id)) {
            throw new Exception("Malformed data");
        }

        $query="
            SELECT forum_topic.id as topic_id, forum_topic.title as topic_title, forum_topic.last_post_id as last_post_id, forum_topic.last_post as last_post, forum_topic.last_post_by as last_post_by
            FROM forum_topic, forum_bookmark
            WHERE forum_topic.id = forum_bookmark.topic_id
                AND forum_bookmark.user_id = ?";

        $stmt = db()->prepare($query);
        $stmt->set((int) $user_id);
        try {
            $retval = new stdClass;
            $stmt->execute();
            $obj = $stmt->fetchAll();
            $retval->threads = $obj;
            foreach ($obj as $row) {
                // For better performance we gather userinfo in separate query to prevent mysql from using memory tables
                $users[] = $row->last_post_by;
            }
            if (isset($users)) {
                $retval->users = Forum_User::getProfile($users);
            }

            return $retval;
        } catch (Exception $e) {

        }
    }

    /**
     * Is this topic bookmarked?
     *
     * @access      public
     * @param int $id      Topic ID
     * @param int $user_id User ID
     */
    public static function isBookmarked($topic_id, $user_id = false)
    {
        // check if topic is read already in this session
        if (isset($_SESSION["__bookmarked_topics"]) && isset($_SESSION["__bookmarked_topics"][$topic_id])) {
            return $_SESSION["__bookmarked_topics"][$topic_id];
        }

        if (!$user_id && isset($_SESSION["UserID"])) {
            $user_id = $_SESSION["UserID"];
        }
        if (!is_numeric($user_id) || !is_numeric($topic_id)) {
            return false;
        }

        $query="
            SELECT id
            FROM forum_bookmark
            WHERE user_id = ? AND topic_id = ?";

        $stmt = db()->prepare($query);
        $stmt->set((int) $user_id);
        $stmt->set((int) $topic_id);
        try {
            $stmt->execute();
            $val = $stmt->fetch();

            // bookmarked
            if (!empty($val) && is_numeric($val)) {
                $_SESSION["__bookmarked_topics"][$topic_id] = true;

                return true;
            }

            // not bookmarked
            $_SESSION["__bookmarked_topics"][$topic_id] = false;

            return false;
        } catch (Exception $e) {

        }
    }

}
