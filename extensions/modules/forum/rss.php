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
 * Forum RSS
 *
 * @package       Module
 * @subpackage    Forum
 */

class Module_Forum_RSS extends Main
{
    private $feedType = "RSS";
    private $feed;

    public function __construct()
    {
        if ($this->request->getParam(1) == "RSS" || $this->request->getParam(1) == "Atom") {
            $this->feedType = $this->request->getParam(1);
        }

        $this->feed = new XML_Feeds($this->feedType);
        $this->feed->setAuthor(FORUM_AUTHOR);
        $this->feed->setCopyright(FORUM_AUTHOR);
        $this->feed->setTitle(SITETITLE);
        $this->feed->setEncoding("utf-8");
    }

    /**
     * Get latest 10 posts in group and create RSS feed.
     *
     * @return mixed Feed XML
     */
    public function group()
    {
        $url = $this->request->getBaseUri()."forum_rss/group/".$this->request->getParam(0);

        // Caching hash
        $urlHash = "rss_group_".md5($url);

        // Load from cache if available
        if (file_exists(PUBLIC_TEMP."/{$urlHash}.cache") && file_exists(PUBLIC_TEMP."/cache_time_{$urlHash}.cache")) {
            $time = file_get_contents(PUBLIC_TEMP."/cache_time_{$urlHash}.cache");
            if ($time > time()) {
                // Load from cache
                $this->feed = unserialize(file_get_contents(PUBLIC_TEMP."/{$urlHash}.cache"));
                echo (string) $this->feed;
                exit;
            }
        }

        $this->feed->setLink($url);
        $query = "
            SELECT forum_group.public,forum_group.name as group_name,forum_topic.title,forum_topic.id as topic_id,forum_post.id as post_id,forum_post.content,forum_post.created as post_created,forum_forum.name as forum_name,users.username
            FROM users,forum_group,forum_topic,forum_forum,forum_post
            WHERE forum_forum.group_id=forum_group.id AND forum_topic.forum_id=forum_forum.id AND forum_post.topic_id=forum_topic.id AND users.userid=forum_post.user_id AND forum_group.id=?
            ORDER BY forum_post.id
            DESC
            LIMIT 10";

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $this->request->getParam(0));
            $stmt->execute();
            foreach ($stmt as $row) {
                if ($row->public == 0) {
                    throw new Exception("Access denied");
                }

                $item = new FeedItem;
                $item->setTitle("{$row->username} on $row->title");
                $item->setLink($this->request->getBaseUri()."forum/thread/t/{$row->post_id}#post-{$row->post_id}");
                $item->setTimeCreated(strtotime($row->post_created));
                $item->addCategory($row->forum_name);
                $format = new Format_BBCodeRSS(Security::strip($row->content));
                $c = (string) $format;
                $item->setContent($c);
                $this->feed->addItem($item);
            }
            $this->feed->setDescription("Latest forum posts in forum group {$row->group_name}");

            // Cache the feed
            $cacheTime = time() + CACHE_TIME;
            file_put_contents(PUBLIC_TEMP."/{$urlHash}.cache", serialize($this->feed));
            file_put_contents(PUBLIC_TEMP."/cache_time_{$urlHash}.cache", $cacheTime);

            echo (string) $this->feed;
            exit;
        } catch (Exception $e) {
            // Error handling, woot? =P
        }
    }

    /**
     * Get latest 10 posts in forum and create RSS feed.
     *
     * @return mixed Feed XML
     */
    public function forum()
    {
        $url = $this->request->getBaseUri()."forum_rss/forum/".$this->request->getParam(0);

        // Caching hash
        $urlHash = "rss_forum_".md5($url);

        // Load from cache if available
        if (file_exists(PUBLIC_TEMP."/{$urlHash}.cache") && file_exists(PUBLIC_TEMP."/cache_time_{$urlHash}.cache")) {
            $time = file_get_contents(PUBLIC_TEMP."/cache_time_{$urlHash}.cache");
            if ($time > time()) {
                // Load from cache
                $this->feed = unserialize(file_get_contents(PUBLIC_TEMP."/{$urlHash}.cache"));
                echo (string) $this->feed;
                exit;
            }
        }

        $this->feed->setLink($url);
        $query = "
            SELECT forum_group.public,forum_group.name as group_name,forum_topic.title,forum_topic.id as topic_id,forum_post.id as post_id,forum_post.content,forum_post.created,forum_forum.name as forum_name,users.username
            FROM users,forum_group,forum_topic,forum_forum,forum_post
            WHERE forum_forum.group_id=forum_group.id AND forum_topic.forum_id=forum_forum.id AND forum_post.topic_id=forum_topic.id AND users.userid=forum_post.user_id AND forum_forum.id=?
            ORDER BY forum_post.id
            DESC
            LIMIT 10";

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $this->request->getParam(0));
            $stmt->execute();
            foreach ($stmt as $row) {
                if ($row->public == 0) {
                    throw new Exception("Access denied");
                }

                $item = new FeedItem;
                $item->setTitle("{$row->username} on $row->title");
                $item->setLink($this->request->getBaseUri()."forum/thread/t/{$row->post_id}#post-{$row->post_id}");
                $item->setTimeCreated($row->created);
                $item->addCategory($row->forum_name);
                $format = new Format_BBCodeRSS(Security::strip($row->content));
                $c = (string) $format;
                $item->setContent($c);
                $this->feed->addItem($item);
            }
            $this->feed->setDescription("Latest forum posts in forum {$row->forum_name}");

            // Cache the feed
            $cacheTime = time() + CACHE_TIME;
            file_put_contents(PUBLIC_TEMP."/{$urlHash}.cache", serialize($this->feed));
            file_put_contents(PUBLIC_TEMP."/cache_time_{$urlHash}.cache", $cacheTime);

            echo (string) $this->feed;
            exit;
        } catch (Exception $e) {
        }
    }

}
