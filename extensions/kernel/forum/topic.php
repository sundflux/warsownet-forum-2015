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
 * Forum module, forum updates and actions
 * @package       Kernel
 * @subpackage    Forum
 */

class forum_topic
{
    /**
     * Add new topic.
     *
     * @access      public
     * @param  int    $forum_id ForumID
     * @param  string $title    Topic title
     * @param  int    $pinned   Pinned topic?
     * @param  int    $closed   Closed topic?
     * @param  int    $views    Views
     * @param  string $time     Created timestamp
     * @return int    $id Inserted DB row id
     */
    public static function add($forum_id, $title, $pinned = 0, $closed = 0, $views = 0, $time = false)
    {
        // Check for title
        $title = trim($title);
        if (empty($title) || !is_numeric($forum_id)) {
            throw new Common_Exception(i18n("ForumID and Title required"));
        }

        if (!$time) {
            // The usual case
            $query = "
                INSERT INTO forum_topic (title,forum_id,closed,pinned,views,posts,created)
                VALUES (?,?,?,?,?,0,NOW())";

        } else {
            // Imports
            $query = "
                INSERT INTO forum_topic (title,forum_id,closed,pinned,views,posts,created)
                VALUES (?,?,?,?,?,0,?)";

        }
        $stmt = db()->prepare($query);
        $stmt->set($title);
        $stmt->set((int) $forum_id);
        $stmt->set($closed);
        $stmt->set($pinned);
        $stmt->set($views);
        if ($time) {
            $stmt->set($time);
        }

        try {
            $stmt->execute();

            return db()->lastInsertID();
        } catch (Exception $e) {
            throw new Exception("Adding topic failed.");
        }
    }

    /**
     * Move topic to new forum.
     *
     * @access      public
     * @param int $topic_id TopicID
     * @param int $forum_id ForumID
     */
    public static function move($topic_id, $forum_id)
    {
        if (!is_numeric($topic_id) || !is_numeric($forum_id)) {
            throw new Exception("ForumID and Topic required");
        }

        $query = "
            UPDATE forum_topic
            SET forum_id = ?
            WHERE id = ?";

        $stmt = db()->prepare($query);
        $stmt->set((int) $forum_id);
        $stmt->set((int) $topic_id);
        $stmt->execute();
    }

    /**
     * Rename topic
     *
     * @access      public
     * @param int    $topic_id TopicID
     * @param string $title    New title
     */
    public static function rename($topic_id, $title)
    {
        if (empty($title) || !is_numeric($topic_id)) {
            throw new Exception("Title and TopicID required");
        }

        $query = "
            UPDATE forum_topic
            SET title = ?
            WHERE id = ?";

        $stmt = db()->prepare($query);
        $stmt->set($title);
        $stmt->set((int) $topic_id);
        $stmt->execute();
    }

    /**
     * Update first post
     *
     * @access      public
     * @param int    $topic_id TopicID
     * @param string $title    New title
     */
    public static function firstpost($topic_id, $firstpost_id)
    {
        if (!is_numeric($topic_id) || !is_numeric($firstpost_id)) {
            throw new Exception("PostID and TopicID required. Got {$topic_id},{$firstpost_id}");
        }

        $query = "
            UPDATE forum_topic
            SET first_post_id = ?
            WHERE id = ?";

        $stmt = db()->prepare($query);
        $stmt->set((int) $firstpost_id);
        $stmt->set((int) $topic_id);
        $stmt->execute();
    }

    /**
     * Update last topic poster and post count
     *
     * @access      public
     * @param  int $id Topic ID
     * @uses        Forum_Pagination
     * @return int $count Total number of pages in topic
     */
    public static function update($id)
    {
        // Get latests post in this topic
        $query = "
            SELECT forum_post.id,forum_post.user_id,forum_post.created
            FROM forum_post
            WHERE forum_post.topic_id=?
            ORDER BY forum_post.id DESC
            LIMIT 1";

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $id);
            $stmt->execute();
            $row = $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Database error while getting post.");
        }
        $count = self::getPostCount($id);

        // Update topic data
        $query="
            UPDATE forum_topic
            SET last_post_id=?,last_post=?,last_post_by=?,posts=?
            WHERE id=?";

        try {
            $stmt = db()->prepare($query);
            $stmt->set($row->id);
            $stmt->set($row->created);
            $stmt->set((int) $row->user_id)->set((int) $count)->set((int) $id);
            $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Database error while updating topic.");
        }
        $pages = Forum_Pagination::pages(1, FORUM_THREAD_POSTS, $count);

        return $pages->pages;
    }

    /**
     * Returns number of posts in topic
     *
     * @access      public
     * @param  int $topic_id Topic ID
     * @return int $count Number of posts
     */
    public static function getPostCount($id)
    {
        // Count number of posts
        $query = "
            SELECT COUNT(id) AS count
            FROM forum_post
            WHERE topic_id=?";

        try {
            $stmt=db()->prepare($query);
            $stmt->set((int) $id);
            $stmt->execute();

            return $stmt->fetchColumn();
        } catch (Exception $e) {
            throw new Exception("Database error while counting posts.");
        }
    }

    /**
     * Returns forum_id of topic where it belongs
     *
     * @access      public
     * @param  int $topic_id Topic ID
     * @return int $forum_id Forum ID
     */
    public static function getForumID($topic_id)
    {
        if (!is_numeric($topic_id)) {
            throw new Exception("TopicID required");
        }

        $query = "
            SELECT forum_id
            FROM forum_topic
            WHERE id=?";

        try {
            $stmt=db()->prepare($query);
            $stmt->set((int) $topic_id);
            $stmt->execute();

            return $stmt->fetchColumn();
        } catch (Exception $e) {
            throw new Exception("Database error.");
        }
    }

    /**
     * Returns info about topic
     *
     * @access      public
     * @param  int    $topic_id Topic ID
     * @return object
     */
    public static function getTopicInfo($topic_id)
    {
        if (!is_numeric($topic_id)) {
            throw new Exception("TopicID required");
        }

        $query = "
            SELECT closed, pinned, views, posts
            FROM forum_topic
            WHERE id =?";

        $stmt = db()->prepare($query);
        $stmt->set((int) $topic_id);
        try {
            $stmt->execute();

            return $stmt->fetch();
        } catch (Exception $e) {

        }
    }

    /**
     * Returns topic owner
     *
     * @access      public
     * @param  int $topic_id Topic ID
     * @return int
     */
    public static function getTopicOwner($topic_id)
    {
        if (!is_numeric($topic_id)) {
            throw new Exception("TopicID required");
        }

        $query = "
            SELECT forum_post.user_id
            FROM forum_post, forum_topic
            WHERE forum_post.id = forum_topic.first_post_id
            AND forum_topic.id = ?";

        $stmt = db()->prepare($query);
        $stmt->set((int) $topic_id);
        try {
            $stmt->execute();

            return $stmt->fetch()->user_id;
        } catch (Exception $e) {

        }
    }

    /**
     * Sticky the topic
     *
     * @access      public
     * @param int $topic_id Topic ID
     */
    public static function sticky($topic_id, $pinned = 1)
    {
        if (!is_numeric($topic_id)) {
            throw new Exception("TopicID required");
        }

        $query = "
            UPDATE forum_topic
            SET pinned = ?
            WHERE id = ?";

        $stmt = db()->prepare($query);
        $stmt->set((int) $pinned);
        $stmt->set((int) $topic_id);
        try {
            $stmt->execute();
        } catch (Exception $e) {

        }
    }

    /**
     * Unsticky the topic
     *
     * @access      public
     * @param int $topic_id Topic ID
     */
    public static function unsticky($topic_id)
    {
        self::sticky($topic_id, 0);
    }

    /**
     * Open the topic
     *
     * @access      public
     * @param int $topic_id Topic ID
     * @param int $closed   Closed or not? Defaults to open
     */
    public static function opentopic($topic_id, $closed = 0)
    {
        if (!is_numeric($topic_id)) {
            throw new Exception("TopicID required");
        }

        $query = "
            UPDATE forum_topic
            SET closed = ?
            WHERE id = ?";

        $stmt = db()->prepare($query);
        $stmt->set((int) $closed);
        $stmt->set((int) $topic_id);
        try {
            $stmt->execute();
        } catch (Exception $e) {

        }
    }

    /**
     * Close the topic
     *
     * @access      public
     * @param int $topic_id Topic ID
     */
    public static function closetopic($topic_id)
    {
        self::opentopic($topic_id, 1);
    }

}
