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
 * Forum module core
 * @package       Kernel
 * @subpackage    Forum
 */

class Forum
{
    /**
     * Actions that should be ran on every forum page.
     *
     * @access      public
     * @uses        Auth
     * @uses        Forum_Unread
     */
    public static function init()
    {
        // Check for autologin
        Auth_AutologinCheck::autologin();

        // Check that auth process is complete, don't go around bans.
        if (isset($_SESSION["UserID"]) && isset($_SESSION["AuthComplete"]) && $_SESSION["AuthComplete"] == 0) {
            $auth = new Auth;
            $auth->logout();
        }

        // Update post counts on timely intervals
        if (isset($_SESSION["UserID"]) && isset($_SESSION["AuthComplete"]) && $_SESSION["AuthComplete"] == 1) {
            // check for new topics every X seconds
            if (FORUM_CHECK_UNREAD_POSTS) {
                $updateSeconds = FORUM_CHECK_UNREAD_POSTS;
            } else {
                // Default to one minute if not set (pretty relaxed checking)
                $updateSeconds = 60;
            }

            if (!isset($_SESSION["NextCheck"])) {
                $_SESSION["NextCheck"] = time() + $updateSeconds;
            }

            // Check for unread posts
            if (time() > $_SESSION["NextCheck"]) {
                // Update unread posts
                $unread = new Forum_Unread;
                $unread->get();

                // Reset timer
                $_SESSION["NextCheck"] = time() + $updateSeconds;
            }

            // Cleaning the cache is low-prio
            $updateCacheSeconds = CACHE_TIME;
            if (!isset($_SESSION["NextCacheCheck"])) {
                $_SESSION["NextCacheCheck"] = time() + $updateCacheSeconds;
            }

            if (time() > $_SESSION["NextCacheCheck"]) {
                // Clean the cache
                new Cache_Autoclean;

                // Reset timer
                $_SESSION["NextCacheCheck"] = time() + $updateCacheSeconds;
            }
        }
    }

    /**
     * Get forums (grouped by groups) (where user has permission to, group id's set as array in $_SESSION["forum_allowed_groups"]).
     * Grouping logic is a bit trickery, but fastest and allowed getting forums with single query.
     *
     * @access      public
     * @return object Groups with forums
     */
    public static function getForums()
    {
        // Include private forums in query
        $groupSearch = "";
        if (isset($_SESSION["UserID"]) && isset($_SESSION["forum_allowed_groups"]) && is_array($_SESSION["forum_allowed_groups"])) {
            $groupSearch = " OR (forum_group.public = 0 AND forum_group.id IN (".implode(",", $_SESSION["forum_allowed_groups"]).")) ";
        }

        // Get groups and topics
        $query = "
            SELECT forum_group.id AS group_id,forum_group.name AS group_name,forum_group.public,forum_forum.*
            FROM forum_group, forum_forum
            WHERE forum_forum.group_id=forum_group.id AND forum_forum.visible=1 AND (forum_group.public=1 {$groupSearch})
            ORDER BY forum_group.order ASC, forum_forum.order ASC";

        try {
            $stmt = db()->prepare($query);
            $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Database error");
        }

        // Sort forums by groups
        $i = 0;
        foreach ($stmt as $row) {
            if (!isset($groups[$i]->group) || ($row->group_name != $groups[$i]->group)) {
                $i++;
                $groups[$i] = new stdClass;
                $groups[$i]->group = $row->group_name;
                $groups[$i]->group_id = $row->group_id;
                $groups[$i]->public = $row->public;
            }
            $groups[$i]->forums[] = $row;
        }

        return $groups;
    }

    /**
     * Get forum threads (if user has permission to requested forum)
     *
     * @access      public
     * @param  int    $id   Forum ID
     * @param  int    $page Get page X
     * @uses        Forum_Pagination
     * @return object $forum Forum with all relevant info and threads as object
     */
    public static function getForum($id, $page = false, $order = false)
    {
        // Check if user has permission to this forum.
        self::hasPermission($id);

        // Forum as object
        $forum = new stdClass;

        // Default ordering
        $orderby = " forum_topic.pinned DESC, forum_topic.last_post_id ";
        if ($order) {
            $orderby = " {$order} ";
        }

        // Get forum info and topics count for pagination.
        $query = "
            SELECT forum_forum.topics,forum_forum.name as forum_name,forum_forum.id,forum_group.name as group_name,forum_group.public
            FROM forum_forum,forum_group
            WHERE forum_forum.group_id=forum_group.id AND forum_forum.id=?";

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $id);
            $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Database error");
        }

        // Forum info
        foreach ($stmt as $row) {
            $forum->id = $id;

            // Title for the page
            $forum->sitetitle = "{$row->group_name} / {$row->forum_name}";
            $forum->name = $row->forum_name;
            $forum->forum_id = $row->id;
            $forum->topics = $row->topics;
            $forum->public = $row->public;
            $forum->info = Forum_Forum::getForumInfo($id);
            $forum->permissions = Forum_ACL::getPermissionsFromSession($forum->info->group_id);

            // Unverified account - override permission
            if (is_numeric($forum->forum_id) && !isset($_SESSION["verified"]) || (isset($_SESSION["verified"]) && $_SESSION["verified"] == 0)) {
                $tmp = explode(",", FORUM_REQUIRE_VERIFIED_IDS);
                foreach ($tmp as $tmpk => $tmpid) {
                    // Set permission to read only
                    if ($tmpid == $row->id) {
                        $forum->permissions = 4;
                    }
                }
            }

            // RSS
            if ($forum->public == 1) {
                $forum->rss = "forum_rss/forum/{$forum->forum_id}";
            }

            // URL for the pagination targets
            $forum->url = "/forum/{$id}/";
            unset($row,$stmt);
        }

        // Get pagination
        $forum->pages = Forum_Pagination::pages($page, FORUM_THREADS, $forum->topics);

        // Get topics
        $query = "
            SELECT forum_topic.id,forum_topic.title,forum_topic.last_post_id,forum_topic.last_post_by,forum_topic.last_post,forum_topic.closed,forum_topic.pinned,forum_topic.views,forum_topic.posts,forum_topic.created,users.username as lastPostByName
            FROM users,forum_topic
            WHERE forum_topic.last_post_by=users.userid AND forum_topic.forum_id=?
            ORDER BY {$orderby} DESC
            LIMIT {$forum->pages->limit}
            OFFSET {$forum->pages->offset}";//ORDER BY forum_topic.pinned DESC, forum_topic.id DESC

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $id);
            $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Database error");
        }
        $forum->threads = $stmt->fetchAll();

        return $forum;
    }

    /**
     * Get thread (topic)
     *
     * @access      public
     * @param  int    $id   Topic id
     * @param  int    $page Get page X
     * @uses        Forum_Post
     * @uses        Forum_Pagination
     * @uses        Format_Tidy
     * @uses        Forum_User
     * @return object $thread Thread with all relevant info and posts as object
     */
    public static function getThread($id = false, $page = false, $byPostID = false)
    {
        // Thread as object
        $thread = new stdClass;
        $thread->posts_count = 0;

        // Locate thread by PostID
        if (!$id && is_numeric($byPostID)) {
            $byPost = Forum_Post::getPage($byPostID);
            $id = $byPost->topic_id;
            $page = $byPost->page;
        }

        // Get forum info and topics count for pagination.
        $query = "
            SELECT forum_forum.name as forum_name,forum_forum.id as forum_id,forum_group.id as group_id,forum_group.name as group_name,forum_group.public,forum_topic.title,forum_topic.posts,forum_topic.closed
            FROM forum_forum,forum_group,forum_topic
            WHERE forum_forum.group_id=forum_group.id AND forum_topic.forum_id=forum_forum.id AND forum_topic.id=?";

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $id);
            $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Database error");
        }

        // Thread info
        foreach ($stmt as $row) {
            if ($row->public == 0) {
                // Forum is not public:
                // Check if user has permission to this forum.
                self::hasPermission($row->forum_id);
                $tmp = Forum_Forum::getForumInfo($row->forum_id);
                $thread->permissions = Forum_ACL::getPermissionsFromSession($tmp->group_id);
            }

            // Forum id
            $thread->id = $row->forum_id;

            // Thread id
            $thread->thread_id = $id;

            // Title for the page
            $thread->sitetitle = $row->title;

            if ($page > 1) {
                $thread->sitetitle .= " (page {$page})";
            }

            $thread->name = $row->forum_name;
            $thread->title = $row->title;
            $thread->closed = $row->closed;
            $thread->posts_count = $row->posts;

            // No point going past this
            if ($thread->posts_count == 0) {
                return $thread;
            }

            // URL for the pagination targets
            $thread->url = "/forum/thread/{$id}/";
            unset($row, $stmt);
        }
        // Get pagination
        $thread->pages = Forum_Pagination::pages($page, FORUM_THREAD_POSTS, $thread->posts_count);

        // Get posts
        $query = "
            SELECT forum_post.id as forum_id,forum_post.id as post_id,forum_post.content,forum_post.user_id,forum_post.updated,forum_post.created
            FROM forum_post
            WHERE forum_post.topic_id=?
            ORDER BY forum_post.id ASC
            LIMIT {$thread->pages->limit}
            OFFSET {$thread->pages->offset}";

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $id);
            $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Database error");
        }

        // Parse contents
        foreach ($stmt as $post) {
            $post->content = Format_Tidy::validate(Format::parse(htmlspecialchars(Security::strip($post->content)), FORUM_PARSER_INTERFACE), Format_Tidy::$repair);
            $thread->posts[] = $post;

            // For better performance we gather userinfo in separate query to prevent mysql from using memory tables
            $users[] = $post->user_id;
        }
        if (isset($users)) {
            $thread->users = Forum_User::getProfile($users);
        }

        return $thread;
    }

    /**
     * Check if user has permission to this forum.
     *
     * @access      public
     * @param  int   $id Forum ID
     * @return mixed true or exception
     */
    public static function hasPermission($id)
    {
        if (!is_numeric($id)) {
            throw new Exception("Access denied");
        }

        // Check if this forum group has limited access (has access group attached to it).
        $query = "
            SELECT forum_forum.group_id,forum_group.public
            FROM forum_forum,forum_group
            WHERE forum_forum.group_id=forum_group.id AND forum_forum.id=?";

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $id);
            $stmt->execute();
            $row = $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Database error");
        }

        // Ok, the forum is public
        if (isset($row->public) && $row->public == 1) {
            return true;
        }

        // Ok, got permissions
        if (isset($_SESSION["forum_allowed_groups"]) && in_array($row->group_id, $_SESSION["forum_allowed_groups"])) {
            return true;
        }

        // Boo! bail out
        throw new Exception("Access denied");
    }

}
