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
 * Forum spam handler
 *
 * @package       Kernel
 * @subpackage    Forum
 * @todo          Spam analyzing from spam flagged posts
 */

class forum_spam
{
    /* Spam handling */

    /**
     * Get ID for spam forum.
     * Spam topics are moved here.
     *
     * @access      public
     */
    public static function getID()
    {
        try {
            $query = "
                SELECT id
                FROM forum_forum
                WHERE name='__SPAM__' and visible = 0";

            $stmt = db()->prepare($query);
            $stmt->execute();
            $id = $stmt->fetchColumn();
            // spam forum doesn't exist, create one
            if (!$id) {
                self::createSpamForum();

                return self::getID();
            }

            return $id;
        } catch (Exception $e) {
            throw new Exception("Getting Spam forum ID failed.");
        }
    }

    /**
     * Get ID for spam topic.
     * Spam posts are moved here.
     *
     * @access      public
     */
    public static function getTopicID()
    {
        $forumid = self::getID();

        try {
            $query = "
                SELECT id
                FROM forum_topic
                WHERE title='__SPAM__' and forum_id = ?";

            $stmt = db()->prepare($query);
            $stmt->set($forumid);
            $stmt->execute();
            $id = $stmt->fetchColumn();
            // spam topic doesn't exist, create one
            if (!$id) {
                self::createSpamTopic();

                return self::getTopicID();
            }

            return $id;
        } catch (Exception $e) {
            throw new Exception("Getting Spam topic ID failed.");
        }
    }

    /**
     * Create spam forum if it doesn't exist
     *
     * @access      public
     */
    private static function createSpamForum()
    {
        try {
            $query="
                INSERT INTO forum_forum (name,group_id,last_post_id,last_post,last_post_by,topics,visible,`order`)
                VALUES (?,?,?,?,?,?,?,?)";

            $stmt = db()->prepare($query);
            $stmt->set("__SPAM__");
            $stmt->set(-1);
            $stmt->set(-1);
            $stmt->set(-1);
            $stmt->set(-1);
            $stmt->set(-1);
            $stmt->set(0);
            $stmt->set(0);
            $stmt->execute();
        } catch (Exception $e) {

        }
    }

    /**
     * Create spam topic if it doesn't exist
     *
     * @access      public
     */
    private static function createSpamTopic()
    {
        $forumid = self::getID();

        return Forum_Topic::add($forumid, "__SPAM__");
    }

    /**
     * Mark given topic as spam
     *
     * @access      public
     */
    public static function topicAsSpam($id)
    {
        $spamForumID = self::getID();
        try {
            // Get current forum id for topic
            $ForumID = Forum_Topic::getForumID($id);

            // Move topic to spam forum
            $query = "
                UPDATE forum_topic
                SET forum_id = ?
                WHERE id = ?";

            $stmt = db()->prepare($query);
            $stmt->set($spamForumID);
            $stmt->set($id);
            $stmt->execute();

            // Update counts after we've moved spam one out
            Forum_Forum::update($ForumID);
        } catch (Exception $e) {

        }
    }

    /**
     * Mark given post as spam
     *
     * @access      public
     */
    public static function postAsSpam($id)
    {
        $spamTopicID = self::getTopicID();
        try {
            // Get topic
            $TopicInfo = Forum_Post::getPost($id);
            $TopicID = $TopicInfo->topic_id;

            // Get current forum id for topic
            $ForumID = Forum_Topic::getForumID($TopicID);

            // Move to spam thread
            $query = "
                UPDATE forum_post
                SET topic_id = ?
                WHERE id = ?";

            $stmt = db()->prepare($query);
            $stmt->set($spamTopicID);
            $stmt->set($id);
            $stmt->execute();

            // Update topic info after moving to spam thread
            Forum_Topic::update($TopicID);
            Forum_Forum::update($ForumID);
        } catch (Exception $e) {

        }
    }

    /**
     * Mark given topic as spam and ban the original poster
     *
     * @access      public
     */
    public static function topicAsSpamWithBan($id)
    {
        $query = "
            SELECT forum_post.user_id AS user_id
            FROM forum_post, forum_topic
            WHERE forum_topic.first_post_id = forum_post.id
            AND forum_topic.id = ?";

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $id);
            $stmt->execute();
            $user_id = $stmt->fetchColumn();
        } catch (Exception $e) {

        }

        // Ban and mark as spam
        self::ban($user_id);
        self::topicAsSpam($id);
    }

    /**
     * Mark given post as spam and ban the poster
     *
     * @access      public
     */
    public static function postAsSpamWithBan($id)
    {
        $query = "
            SELECT user_id
            FROM forum_post
            WHERE id = ?";

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $id);
            $stmt->execute();
            $user_id = $stmt->fetchColumn();
        } catch (Exception $e) {

        }

        // Ban and mark as spam
        self::ban($user_id);
        self::postAsSpam($id);
    }

    public static function ban($user_id, $ban = 1)
    {
        $query = "
            UPDATE forum_profile
            SET banned = ?
            WHERE user_id = ?";
        try {
            $stmt = db()->prepare($query);
            $stmt->set($ban);
            $stmt->set($user_id);
            $stmt->execute();
        } catch (Exception $e) {
            $this->ui->addError("Changing user ban status failed.");
        }
    }

}
