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
 * Forum posts
 * @package       Kernel
 * @subpackage    Forum
 */

class forum_post
{
    /**
     * Add new post.
     *
     * @access      public
     * @param  int    $topic_id Topic ID
     * @param  string $content  Post content
     * @param  int    $userid   UserID who owns the post
     * @param  string $time     Timestamp for the post
     * @uses        Forum_Topic
     * @return int    $id Inserted DB row id
     */
    public static function add($topic_id, $content, $userid, $time = false)
    {
        $topic = Forum_Topic::getTopicInfo($topic_id);
        if ($topic->closed == 1) {
            throw new Exception("This topic is closed.");
        }

        $content = trim($content);
        if (!$time) {
            // The usual suspects
            $query = "
                INSERT INTO forum_post (topic_id,parent_id,content,user_id,created)
                VALUES (?,0,?,?,NOW())";

        } else {
            // Imports
            $query = "
                INSERT INTO forum_post (topic_id,parent_id,content,user_id,created)
                VALUES (?,0,?,?,?)";

        }
        $stmt = db()->prepare($query);
        $stmt->set((int) $topic_id);
        $stmt->set($content);
        $stmt->set((int) $userid);
        if ($time) {
            $stmt->set($time);
        }

        $stmt->execute();

        return db()->lastInsertID();
    }

    /**
     * Update post.
     *
     * @access      public
     * @param  int    $post_id Post ID
     * @param  string $content Post content
     * @return int    $id Updated DB row id
     */
    public static function update($post_id, $content)
    {
        $content = trim($content);
        $query = "
            UPDATE forum_post
            SET content=?,updated_by=?,updated=NOW()
            WHERE id=?";

        $stmt = db()->prepare($query);
        $stmt->set($content);
        $stmt->set((int) $_SESSION["UserID"]);
        $stmt->set((int) $post_id);
        $stmt->execute();

        return db()->lastInsertID();
    }

    /**
     * Get post page in thread
     *
     * @access      public
     * @param  int $post_id Post ID
     * @return int $page
     */
    public static function getPage($id)
    {
        // Default to page zero when getting crap data
        if (!is_numeric($id)) {
            return 0;
        }

        try {
            // A bit tricky queries, but hunts down the correct page by calculating
            // pagination at sql side, saving us from calculating it in the code. Faster too.
            db()->execute("SET @topic_id = (SELECT topic_id FROM forum_post WHERE id={$id})");
            $stmt = db()->execute("SELECT (floor(count(*)/".FORUM_THREAD_POSTS.")+1) as page,topic_id FROM forum_post WHERE topic_id=@topic_id and id<={$id}");
            $row = $stmt->fetch();
            $retval = new stdClass;
            $retval->page = $row->page;
            $retval->topic_id = $row->topic_id;

            return $retval;
        } catch (Exception $e) {

        }
    }

    /**
     * Get post
     *
     * @access      public
     * @param  int    $post_id Post ID
     * @return object $stmt DB object
     */
    public static function getPost($id)
    {
        $query = "
            SELECT forum_post.*,users.username
            FROM forum_post,users
            WHERE forum_post.user_id=users.userid AND forum_post.id=?";

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $id);
            $stmt->execute();

            return $stmt->fetch();
        } catch (Exception $e) {

        }
    }

    /**
     * Check given UserID has permissions to save (edit) this post.
     *
     * @access      public
     * @param  int  $post_id Post ID
     * @param  int  $user_id User ID
     * @return bool
     */
    public static function validateHasPermission($post_id, $user_id)
    {
        // Are we admin?
        $admin = 0;
        $moderator = 0;
        if (isset($_SESSION["admin"])) {
            $admin = $_SESSION["admin"];
        }
        if (isset($_SESSION["moderator"])) {
            $moderator = $_SESSION["moderator"];
        }

        // Yes we are, so allow editing (bail out here)
        if ($admin == 1 || $moderator == 1) {
            return true;
        }

        // Are we owner?
        if (!self::isOwner($post_id, $user_id)) {
            throw new Exception("Not an owner, no permissions to edit");
        }

        return true;
    }

    /**
     * Check if post is owned by given UserID
     *
     * @access      public
     * @param  int  $post_id Post ID
     * @param  int  $user_id User ID
     * @return bool
     */
    public static function isOwner($post_id, $user_id)
    {
        $query = "
            SELECT user_id
            FROM forum_post
            WHERE id=?";

        $stmt = db()->prepare($query);
        $stmt->set((int) $post_id);
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row->user_id == $user_id) {
            return true;
        }

        return false;
    }

}
