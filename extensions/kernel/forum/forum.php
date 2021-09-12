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

class forum_forum
{
    /**
     * Add new forum
     *
     * @access      public
     * @param string $name   Group name
     * @param int    $public 0 or 1
     * @param int    $order
     */
    public static function add($title, $group_id, $order = 0, $topics = 0, $visible = 1, $last_post_by = 0, $last_post_id = 0, $last_post = "0000-00-00 00:00:00")
    {
        if (empty($title) || !is_numeric($group_id)) {
            throw new Exception("Malformed data");
        }

        $query = "
            INSERT INTO forum_forum (`name`,`group_id`,`order`,`topics`,`visible`,`last_post_by`,`last_post_id`,`last_post`)
            VALUES (?,?,?,?,?,?,?,?)";

        try {
            $stmt = db()->prepare($query);
            $stmt->set($title);
            $stmt->set((int) $group_id);
            $stmt->set((int) $order);
            $stmt->set((int) $topics);
            $stmt->set((int) $visible);
            $stmt->set($last_post_by);
            $stmt->set($last_post_id);
            $stmt->set($last_post);
            $stmt->execute();

            $ForumID = db()->lastInsertID();
        } catch (Exception $e) {

        }
    }

    /**
     * Update last forum poster and topics count
     *
     * @access      public
     * @param int $id Forum ID
     */
    public static function update($id)
    {
        // Get latests post in this forum
        $query = "
            SELECT forum_post.id, forum_post.user_id, forum_post.created
            FROM forum_post,forum_topic
            WHERE forum_topic.id=forum_post.topic_id AND forum_topic.forum_id=?
            ORDER BY forum_post.id
            DESC LIMIT 1";

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $id);
            $stmt->execute();
            $row = $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Database error while getting post.");
        }

        // Count number of topics
        $query = "
            SELECT COUNT(id) AS count
            FROM forum_topic
            WHERE forum_id=?";

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $id);
            $stmt->execute();
            $count = $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Database error while counting threads.");
        }

        // Update forum data
        $query = "
            UPDATE forum_forum
            SET last_post_id=?,last_post=?,last_post_by=?,topics=?
            WHERE id=?";

        try {
            $stmt = db()->prepare($query);
            $stmt->set($row->id)->set($row->created)->set((int) $row->user_id)->set((int) $count->count)->set((int) $id);
            $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Database error while updating forum.");
        }
    }

    /**
     * Returns forum name
     *
     * @access      public
     * @param  int    $forum_id Topic ID
     * @return string $name Forum name
     */
    public static function getForumName($forum_id)
    {
        if (!is_numeric($forum_id)) {
            throw new Exception("ForumID required");
        }

        $query = "
            SELECT name
            FROM forum_forum
            WHERE id=?";

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $forum_id);
            $stmt->execute();

            return $stmt->fetchColumn();
        } catch (Exception $e) {
            throw new Exception("Database error.");
        }
    }

    /**
     * Rename forum
     *
     * @access      public
     * @param int    $forum_id ForumID
     * @param string $title    New title
     */
    public static function rename($forum_id, $title)
    {
        if (empty($title) || !is_numeric($forum_id)) {
            throw new Exception("Title and ForumID required");
        }

        $query = "
            UPDATE forum_forum
            SET name = ?
            WHERE id = ?";

        $stmt = db()->prepare($query);
        $stmt->set($title);
        $stmt->set((int) $forum_id);
        $stmt->execute();
    }

    /**
     * Get all forums by groupid, return as object
     *
     * @access      public
     * @param  int    $group_id
     * @return object
     */
    public static function getForums($group_id)
    {
        $query = "
            SELECT id, name, visible, `order`
            FROM forum_forum
            WHERE group_id = ?
            ORDER BY `order` ASC";

        $stmt = db()->prepare($query);
        $stmt->set((int) $group_id);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Update order number of the item
     *
     * @access      public
     * @param int $forum_id
     * @param int $order
     */
    public static function updateOrder($forum_id, $order)
    {
        $query = "
            UPDATE forum_forum
            SET `order` = ?
            WHERE id = ?";

        $stmt = db()->prepare($query);
        $stmt->set((int) $order);
        $stmt->set((int) $forum_id);
        try {
            $stmt->execute();
        } catch (Exception $e) {

        }
    }

    /**
     * Returns info about forum
     *
     * @access      public
     * @param  int    $forum_id ForumID
     * @return object
     */
    public static function getForumInfo($forum_id)
    {
        if (!is_numeric($forum_id)) {
            throw new Exception("ForumID required");
        }

        $query = "
            SELECT id, name, group_id, last_post_id, last_post, last_post_by, topics, visible, `order`
            FROM forum_forum
            WHERE id =?";

        $stmt = db()->prepare($query);
        $stmt->set((int) $forum_id);
        try {
            $stmt->execute();

            return $stmt->fetch();
        } catch (Exception $e) {

        }
    }

}
