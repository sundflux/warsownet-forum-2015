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
 * Make forum thread act like a blog
 *
 * @package       Kernel
 * @subpackage    Blog
 */

class Blog
{
    // Default limit for blog posts per page
    private $limit = 10;

    // Internal page counter
    private $page = 1;

    // Forum ID's which should be included in the blog
    private $forums;

    /**
     * Change posts-per-page limit
     *
     * @param int $l
     */
    public function setLimit($l)
    {
        $this->limit = (int) $l;
    }

    /**
     * Include a forum as a blog (can be called multiple times to include several forums)
     *
     * @param int $id
     */
    public function addForum($id)
    {
        $this->forums[] = $id;
    }

    /**
     * Change page
     *
     * @param int $p
     */
    public function setPage($p)
    {
        $this->page = (int) $p;
    }

    /**
     * Get entries from forum as a blog object.
     */
    public function get()
    {
        // Our return object
        $blog = new stdClass;

        // Forum ID's
        $forums = implode(",",$this->forums);

        // No forums
        if (empty($forums)) {
            return false;
        }

        // Count blog posts
        $query = "
            SELECT COUNT(forum_topic.id) as count
            FROM forum_topic, forum_post
            WHERE forum_topic.forum_id IN (?)
            AND forum_topic.id = forum_post.topic_id";

        try {
            $stmt = db()->prepare($query);
            $stmt->set($forums);
            $stmt->execute();
            $row = $stmt->fetch();

            // Number of blog posts
            $total = $row->count;
        } catch (Exception $e) {
            throw new Exception("Loading blog failed");
        }

        // Pagination
        $blog->pages = Forum_Pagination::pages($this->page, $this->limit, $total);

        // Get blog posts
        $query = "
            SELECT forum_topic.id as topic_id, forum_topic.title as topic_title, forum_topic.posts as topic_comments, forum_topic.created as created, forum_post.id as post_id, forum_post.user_id as user_id, forum_post.content
            FROM forum_topic, forum_post
            WHERE forum_topic.forum_id IN (".$forums.")
            AND forum_topic.id = forum_post.topic_id
            AND forum_topic.first_post_id = forum_post.id
            ORDER BY forum_topic.pinned DESC, forum_topic.id
            DESC
            LIMIT {$blog->pages->limit}
            OFFSET {$blog->pages->offset}";

        try {
            $stmt = db()->prepare($query);
            $stmt->execute();
        } catch (Exception $e) {
            //Debug::d($e->getMessage());
            throw new Exception("Loading blog failed");
        }

        // Parse contents
        foreach ($stmt as $post) {
            // Parse the content with Tidy + format using desired parser interface
            $post->content = Format_Tidy::validate(Format::parse(htmlspecialchars($post->content), FORUM_PARSER_INTERFACE), Format_Tidy::$repair);
            $blog->posts[] = $post;

            // Note!!
            // For better performance we gather userinfo in separate query to prevent mysql from using memory tables
            $users[] = $post->user_id;
        }

        // Add users to the return object. These need to be matched at XSL side like:
        // <xsl:value-of select="../users[user_id=$user]/username"/>
        if (isset($users)) {
            $blog->users = Forum_User::getProfile($users);
        }

        return $blog;
    }

}
