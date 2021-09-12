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
 * Forum search
 * @package       Kernel
 * @subpackage    Forum
 * @uses          Common_User
 */

class forum_search
{
    /**
     * Search forum
     *
     * @access      public
     * @param  string $string Search string
     * @param  mixed  $id     ForumID or false
     * @param  int    $page   Start from page X
     * @uses        Forum
     * @uses        Forum_Pagination
     * @return mixed  Object of results or false
     *
     * @todo        Integrate lucene or something?
     *              Content seaching is DISABLED as of now because
     *              it's simply too slow with forums with 200 000+ posts.
     */
    public static function search($string = false, $forum = false, $page = 1)
    {
        if (!$string || empty($string) || strlen($string) < 3) {
            return;
        }

        $search = new stdClass;

        // Default search query.
        // Gets overwritten by userid search if $string is an userid
        $searchQuery = " forum_topic.title LIKE ? ";

        // include only forums from groups where user has access to
        $groupSearch = $forumSearch = $forums = "";
        if (isset($_SESSION["UserID"]) && isset($_SESSION["forum_allowed_groups"]) && is_array($_SESSION["forum_allowed_groups"])) {
            $groupSearch = " OR (forum_group.public = 0 AND forum_group.id IN (".implode(",", $_SESSION["forum_allowed_groups"]).")) ";
        }

        if (is_numeric($forum)) {
            $forumSearch = " AND forum_forum.id = ? ";
        }

        if (is_array($forum)) {
            foreach ($forum as $k => $v) {
                Forum::hasPermission($v);
                $forums .= "&forums[{$v}]=on";
            }
            $forumSearch = " AND forum_forum.id IN (".implode(",", $forum).") ";
            $forum = "";
        }
        $search->url = "/forum_search/advanced?query={$string}&forum_id={$forum}{$forums}&page=";

        // Search by user?
        $user = new Common_User;
        $userid = $user->getUserIDByName($string);
        if (is_numeric($userid)) {
            $string = $userid;
            $searchQuery = " forum_post.user_id = ?";
        } else {
            // wildcard string
            $string = "%{$string}%";
        }

        // Count total number of found threads
        $query = "
            SELECT COUNT(forum_topic.id) as count
            FROM forum_topic,forum_forum,forum_post,forum_group
            WHERE forum_topic.id=forum_post.topic_id AND {$searchQuery} AND forum_forum.group_id=forum_group.id AND (forum_group.public=1 {$groupSearch}) {$forumSearch}
            GROUP BY forum_topic.id";
            // OR forum_post.content LIKE ?
        try {
            $stmt = db()->prepare($query);
            $stmt->set($string);
            //$stmt->set($string);
            if (is_numeric($forum)) {
                $stmt->set((int) $forum);
            }

            $stmt->execute();
            $count = $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Search failed.");
        }
        $search->pages = Forum_Pagination::pages($page, FORUM_THREADS, $count->count);

        // search magic
        $query = "
            SELECT forum_forum.name as forum_name,forum_forum.id as forum_id,forum_topic.title as topic_title,forum_topic.id as topic_id,forum_post.created as post_created,forum_post.id as post_id,users.username as username,users.userid as user_id
            FROM forum_forum,forum_topic,forum_post,forum_group,users
            WHERE forum_topic.forum_id=forum_forum.id AND forum_topic.id=forum_post.topic_id AND forum_post.user_id=users.userid AND {$searchQuery} AND forum_forum.group_id=forum_group.id AND (forum_group.public=1 {$groupSearch}) {$forumSearch}
            GROUP BY forum_topic.id
            ORDER BY forum_topic.id DESC,forum_post.id DESC LIMIT {$search->pages->limit} OFFSET {$search->pages->offset}";
            // AND (forum_topic.title LIKE ? OR forum_post.content LIKE ?)

        try {
            $stmt = db()->prepare($query);
            $stmt->set($string);
            //$stmt->set($string);
            if(is_numeric($forum))
                $stmt->set((int) $forum);

            $stmt->execute();
            $search->result = $stmt->fetchAll();

            return $search;
        } catch (Exception $e) {
            throw new Exception("Search failed.");
        }

        return false;
    }

}
