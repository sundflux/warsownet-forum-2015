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
 * 2011 Victor Luchits <vic@warsow.net>
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
 * Import module
 *
 * @package       Module
 * @subpackage    Forum
 * @todo          rewrite imports, quite incompatible with current codebase
 */

class Module_Forum_Import extends Main
{
    public function __construct()
    {
        if(!defined('ENABLE_IMPORT') || ENABLE_IMPORT != 1)
            die("Import is disabled. Please define ENABLE_IMPORT, 1 to run import.");

    }

    public function index()
    {
        if(!$this->request->getParam(0))

            return;

        $driver = $this->request->getParam(0);
        if (class_exists($driver)) {
            $import = new $driver($this->request->getParam(1));
        } else {
            DEBUG("Import driver {$driver} not found");
        }
    }

}

class ImportDB
{
    public static $db = false;

    public static function openDBConnection()
    {
        if (!self::$db instanceof DB) {
            try {
                if (IMPORT_SIMPLEPRESS_DBCONN != "sqlite") {
                    $initquery = "SET NAMES 'UTF8'";
                } else {
                    $initquery = "";
                }
                self::$db = new DB(
                    IMPORT_SIMPLEPRESS_SERVER,
                    IMPORT_SIMPLEPRESS_USER,
                    IMPORT_SIMPLEPRESS_PASSWORD,
                    IMPORT_SIMPLEPRESS_DATABASE,
                    IMPORT_SIMPLEPRESS_DBCONN,
                    false, $initquery);
            } catch (Exception $e) {
                die($e->getMessage());
            }
        }

        return self::$db;
    }
}

/**
 * Parent class for all kinds of imports
 */
class CommonImport
{
    public static function cleanupPost($post)
    {
        $post = str_replace(array('\r', '\n', '&nbsp;', '&quot;', '&lt;', '&gt;', '&amp;', '&#133;', '&amp;#039;','’'), array('', '', ' ', '"', '<', '>', '&', '...', "'","'"), $post);
        $post = strip_tags($post);
        $post = preg_replace('!'."\n".'{3,}!im', "\n\n", $post);
        $post = preg_replace('!\[/quote\]'."\s".'{2,}!im', '[/quote]'."\n", $post);
        $post = preg_replace('!\[url=(.*?)\]\\1\[/url\]!', '[url]$1[/url]', $post);

        return trim($post);
    }

    public static function createRoot()
    {
        $ap = md5("root");
        $time = time();
        try {
            db()->exec("INSERT INTO ".SITEID."users (username,password,updated,created) VALUES ('root','{$ap}',{$time},{$time})");
            $id = db()->execute("SELECT userid FROM ".SITEID."users WHERE username='root'")->fetchColumn();
            $groupid = db()->execute("SELECT groupid FROM ".SITEID."groups WHERE name='root'")->fetchColumn();
            db()->exec("INSERT INTO ".SITEID."userfeature (userid,keyword,value,updated,created) VALUES ('{$id}','Group',{$groupid},{$time},{$time})");
        } catch (Exception $e) {
            throw new DB_Exception("Unable to create default users. Please check your database settings and try again.");
        }
    }

}

/**
 * SimplePress import
 *
 * Imports simplepress content in following order:
 * 1) Forum groups
 * 2) Forums in groups
 * 3) Topics in forum
 * 4) Posts in topic
 */
class SimplePress extends CommonImport
{
    // dry run?
    private $insert = false;

    // how many items per run?
    private $patches = 100;

    // statistics
    private $users = 0;
    private $groups = 0;
    private $forums = 0;
    private $topics = 0;
    private $posts = 0;
    private $pms = 0;

    private $tmpcounter = 0;

    public function __construct($dryRun = false)
    {
        $this->db = ImportDB::openDBConnection();
        $this->insert = $dryRun;
        DEBUG("Using SimplePress import");
        if(!$dryRun)
            DEBUG("Dry run, not inserting anything");

        // Drop old data

        if ($this->insert) {
            db()->exec("TRUNCATE TABLE forum_forum");
            db()->exec("TRUNCATE TABLE forum_group");
            db()->exec("TRUNCATE TABLE forum_group_acl");
            db()->exec("TRUNCATE TABLE forum_pm_messages");
            db()->exec("TRUNCATE TABLE forum_post");
            db()->exec("TRUNCATE TABLE forum_profile");
            db()->exec("TRUNCATE TABLE forum_profile_acl");
            db()->exec("TRUNCATE TABLE forum_registration");
            db()->exec("TRUNCATE TABLE forum_topic");
            db()->exec("TRUNCATE TABLE forum_unread");

            db()->exec("TRUNCATE TABLE import_forum");
            db()->exec("TRUNCATE TABLE import_topic");
            db()->exec("TRUNCATE TABLE import_post");
            db()->exec("TRUNCATE TABLE import_group");
            db()->exec("TRUNCATE TABLE import_user");
            db()->exec("TRUNCATE TABLE import_pm");

            db()->exec("TRUNCATE TABLE users");
            db()->exec("TRUNCATE TABLE userfeature");
            db()->exec("TRUNCATE TABLE acl");
            db()->exec("TRUNCATE TABLE logs");
            db()->exec("TRUNCATE TABLE navi");
        }

        $this->importUsers();
        $this->importGroups();
        $this->importForums();

        // Get topics, patched in 50 at the time (to keep memory usage under control)
        $this->importTopics($this->patches);
        // Get posts, patched in ($this->patches amount) at the time (to keep memory usage under control)
        $this->importPosts($this->patches);

        $this->importTerms();
        $this->importTermTopics($this->patches);
        $this->importTermComments($this->patches);
        $this->importPrivMessages($this->patches);

        $this->updatePosts();
        $this->updateTopics();
        $this->updateForums();
        $this->updateGroups();
        $this->updatePrivMessages();
        $this->updateUsers();

        DEBUG("IMPORTED:");
        DEBUG("{$this->groups} groups");
        DEBUG("{$this->forums} forums");
        DEBUG("{$this->topics} topics");
        DEBUG("{$this->posts} posts");
        DEBUG("{$this->pms} private messages");
        DEBUG("{$this->users} users");
    }

    private function importGroup($obj)
    {
        if(!$this->insert)

            return;

        DEBUG("Importing group...");
        $query = "
            INSERT INTO forum_group (name,`order`,public)
            VALUES (?,?,0)";
        $stmt = db()->prepare($query);
        $stmt->set($obj->group_name);
        $stmt->set($obj->group_seq);
        $stmt->execute();

        $newID = db()->lastInsertID();

        $query = "INSERT INTO import_group(id, import_id) VALUES (?,?)";
        $stmt = db()->prepare($query);
        $stmt->set($newID);
        $stmt->set($obj->group_id);
        $stmt->execute();

        DEBUG("Done.");

        return $newID;
    }

    private function importGroups()
    {
        DEBUG("Importing forum groups...");

        // start with forum groups
        $query = "
            SELECT *
            FROM wp_sfgroups
            ORDER BY group_id ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();

        foreach ($stmt as $row) {
            $this->groups++;
            DEBUG("Found group {$row->group_name} with id {$row->group_id}");
            // Import group and get new id
            $this->importGroup($row);
        }

        DEBUG("Done.");
    }

    private function importForum($obj)
    {
        if(!$this->insert)

            return;

        DEBUG("Importing forum...");

        $query = "
            INSERT INTO forum_forum (name,group_id,visible,`order`)
            VALUES (?,?,1,?)";
        $stmt = db()->prepare($query);
        $stmt->set(html_entity_decode($obj->forum_name));
        $stmt->set($obj->group_id);
        $stmt->set($obj->forum_seq);
        $stmt->execute();

        $newID = db()->lastInsertID();

        $query = "INSERT INTO import_forum(id, import_id) VALUES (?,?)";
        $stmt = db()->prepare($query);
        $stmt->set($newID);
        $stmt->set($obj->forum_id);
        $stmt->execute();

        DEBUG("Done.");

        return $newID;
    }

    private function importForums()
    {
        DEBUG("Importing forum forums...");

        // Get forums
        $query = "
            SELECT *
            FROM wp_sfforums
            ORDER BY forum_id ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();

        foreach ($stmt as $row) {
            $this->forums++;
            DEBUG("- Found forum {$row->forum_name} with id {$row->forum_id}");
            // Import forum
            $this->importForum($row);
        }
    }

    private function importTopics($limit)
    {
        DEBUG("Importing topics...");

        $lastID = 0;
        for ($offset=0; ; $offset+=$limit) {
            $query = "
                SELECT *
                FROM wp_sftopics
                WHERE topic_id > 0
                ORDER BY topic_id ASC
                LIMIT {$limit}
                OFFSET {$offset}
            ";
            $stmt = $this->db->prepare($query);
            //$stmt->set($lastID);
            $stmt->execute();

            $imported = 0;
            foreach ($stmt as $row) {
                $imported++;
                $this->topics++;
                $lastID = $row->topic_id;
                DEBUG("Found topic {$row->topic_name} with id {$row->topic_id}");
                $this->importTopic($row);
            }
            if (!$imported)
                break;
        }
    }

    private function importTopic($obj)
    {
        if(!$this->insert)

            return;

        DEBUG("Importing topic...");

        $query = "
            INSERT INTO forum_topic (title,created,closed,forum_id,pinned,views,last_post_id,posts)
            VALUES (?,?,?,?,?,?,?,?)";
        $stmt = db()->prepare($query);
        $stmt->set(html_entity_decode($obj->topic_name));
        $stmt->set($obj->topic_date);
        $status = 0;
        if(!empty($obj->status))
            $status = $obj->status;

        $stmt->set($status);
        $stmt->set($obj->forum_id);
        $stmt->set($obj->topic_pinned);
        $stmt->set($obj->topic_opened);
//		$stmt->set($obj->post_id);
        $stmt->set(NULL);
        $stmt->set($obj->post_count);
        $stmt->execute();

        $newID = db()->lastInsertID();

        $query = "INSERT INTO import_topic(id, import_id) VALUES (?,?)";
        $stmt = db()->prepare($query);
        $stmt->set($newID);
        $stmt->set($obj->topic_id);
        $stmt->execute();

        DEBUG("Done.");

        return $newID;
    }

    private function html2bb($content)
    {
        $content = preg_replace('!<br\s*/?>!', "\n", $content);
        $content = preg_replace('!<strong>(.*?)</strong>!ims', '[b]$1[/b]', $content);
        //$content = preg_replace('!<strong>\s*(.*?)\s*said:\s*</strong>\s*<blockquote>\s*(.*?)\s*</blockquote>!ims', '[quote=$1]$2[/quote]', $content);
        $content = preg_replace('!\[b\]\s*([^[]+?)\s+said:\s*\[/b\]\s*<blockquote>!ims', '[quote=$1]', $content);
        $content = preg_replace('!<(/?)blockquote\s*>!', '[$1quote]', $content);
        //$content = preg_replace('!\[b\]\s*(.*?)\s*said:\s*\[/b\]\s*\[quote\]\s*(.*?)\s*\[/quote\]!ims', '[quote=$1]$2[/quote]', $content);
        //$content = preg_replace('!<a[^>]+?href\s*=\s*"([^"]+?)"[^>]?*>\s*(.+?)\s*</a>!i', '[url=$1]$2[/url]', $content);
        $content = preg_replace('!<a\s[^>]*?href\s*=\s*"([^"]+?)"[^>]*?>\s*(.+?)\s*</a>!i', '[url=$1]$2[/url]', $content);
        $content = preg_replace('!<div\s+class="sfcode">(.*?)</div>!ims', '[code]$1[/code]', $content);
        $content = preg_replace('!<(/?)(i|s|u)>!', '[$1$2]', $content);
        $content = preg_replace('!<img\s[^>]*?src="([^"]+?)"\s+alt="([^"]+?)"[^>]*/>!im', '[img=$2]$1[/img]', $content);
        $content = preg_replace('!<img\s[^>]*?src="([^"]+?)"[^>]*/>!im', '[img]$1[/img]', $content);
        $content = preg_replace('!<span\s+style="[^"]*?color:\s*([^\s]+?)">(.*?)</span>!ims', '[color=$1]$2[/color]', $content);

        return CommonImport::cleanupPost($content);
    }

    private function importPost($obj)
    {
        if(!$this->insert)

            return;

        DEBUG("Importing post...");

        $query = "
            INSERT INTO forum_post (topic_id,parent_id,content,user_id,updated_by,updated,created)
            VALUES (?,?,?,?,?,?,?)";
        $stmt = db()->prepare($query);
        $stmt->set($obj->topic_id);
        $stmt->set(0);
        $stmt->set($this->html2bb($obj->post_content));
        if(empty($obj->user_id) || !is_numeric($obj->user_id))
            $obj->user_id=-1;

        $stmt->set($obj->user_id);
        $stmt->set(0);
        $stmt->set(null);
        $stmt->set($obj->post_date);
        $stmt->execute();

        $newID = db()->lastInsertID();

        $query = "INSERT INTO import_post(id, import_id) VALUES (?,?)";
        $stmt = db()->prepare($query);
        $stmt->set($newID);
        $stmt->set($obj->post_id);
        $stmt->execute();

        DEBUG("Done.");
    }

    private function importPosts($limit)
    {
        DEBUG("Importing forum posts...");

        $lastID = 0;
        for ($offset=0; ; $offset+=$limit) {
            $query="
                SELECT *
                FROM wp_sfposts
                WHERE post_id > 0
                ORDER BY post_id ASC
                LIMIT {$limit}
                OFFSET {$offset}
            ";
            $stmt = $this->db->prepare($query);
            //$stmt->set($lastID); // note: this doesn't work for some reason
            $stmt->execute();

            $imported = 0;
            foreach ($stmt as $row) {
                $imported++;
                $this->posts++;
                $lastID = $row->post_id;
                DEBUG("Found post id {$row->post_id}");
                $this->importPost($row);
            }
            if (!$imported)
                break;
        }
    }

    private function importUsers()
    {
        DEBUG("Importing users...");
        if(!$this->insert)

            return;

        $limit = $this->patches;
        $common_user = new Common_User;

        for ($offset=0; ; $offset+=$limit) {
            $query="
                SELECT u.ID as user_id,u.user_login,u.user_pass,u.user_email,u.user_registered,m.avatar,m.signature,m.lastvisit
                FROM wp_users u
                INNER JOIN wp_sfmembers m ON (m.user_id=u.ID)
                ORDER BY u.ID ASC
                LIMIT {$limit}
                OFFSET {$offset}
            ";
            $stmt = $this->db->prepare($query);
            $stmt->execute();

            $imported = 0;
            foreach ($stmt as $row) {
                $imported++;
                $this->users++;
                DEBUG("Found user {$row->user_login}, id {$row->user_id}");

                // OLD userid
                $oldID = $row->user_id;
                try {
                    // meta data
                    $query2 = "SELECT * FROM wp_usermeta WHERE user_id=?";
                    $stmt2 = $this->db->prepare($query2);
                    $stmt2->set($oldID);
                    $stmt2->execute();

                    $user_meta = array();
                    foreach ($stmt2 as $row2) {
                        $user_meta[$row2->meta_key] = $row2->meta_value;
                    }
                    $avatar = @unserialize($row->avatar);

                    if (empty($row->lastvisit)) {
                        // Try fetching the last post as the last visit time
                        $query2 = "SELECT created FROM forum_post WHERE user_id=? ORDER BY id DESC LIMIT 1";
                        $stmt2 = db()->prepare($query2);
                        $stmt2->set($oldID);
                        $stmt2->execute();
                        $row->lastvisit = $stmt2->fetchColumn();
                    }

                    // Create new user
                    $user = new Forum_User;
                    $user->username = $row->user_login;
                    $user->asHash = true;
                    $user->password = $row->user_pass;
                    $user->email = $row->user_email;
                    $user->joined = $row->user_registered;
                    $user->last_visit = $row->lastvisit;
                    $user->real_name = @(string) $user_meta['first_name'].' '.@$user_meta['last_name'];
                    $user->location = @(string) $user_meta['location'];
                    $user->skype = @(string) $user_meta['skype'];
                    $user->facebook = @(string) $user_meta['facebook'];
                    $user->twitter = @(string) $user_meta['twitter'];
                    $user->jabber = @(string) $user_meta['jabber'];
                    $user->msn = @(string) $user_meta['msn'];
                    $user->icq = @(string) $user_meta['icq'];
                    $user->gravatar = empty($avatar['uploaded']);
                    if ($user->gravatar) {
                        $user->avatar = md5($user->email);
                    }
                    $user->signature=html_entity_decode($this->html2bb($row->signature));
                    $user->commit();

                    $newID = $user->id;
                    $query2 = "INSERT INTO import_user (id, import_id) VALUES(?,?)";
                    $stmt2 = db()->prepare($query2);
                    $stmt2->set((int) $newID);
                    $stmt2->set((int) $oldID);
                    $stmt2->execute();

                    if (array_key_exists('punbb_hash', $user_meta)) {
                        $common_user->setSetting('PunBBSalt', @(string) $user_meta['punbb_hash'], $newID);
                    }
                } catch (Exception $e) {

                }
            }
            if (!$imported)
                break;

        }
        DEBUG("Done.");
    }

    private function updatePosts()
    {
        DEBUG("Updating posts...");
        if(!$this->insert)

            return;

        // update user and topic ids
        $query = "
            UPDATE forum_post p
            LEFT JOIN import_user i ON (i.import_id=p.user_id)
            INNER JOIN import_topic i2 ON (i2.import_id=p.topic_id)
            SET p.user_id=COALESCE(i.id,-1), p.topic_id=i2.id
        ";
        $stmt = db()->prepare($query);
        $stmt->execute();

        // assign unassigned posts to Guest
        $guest_id = db()->execute("SELECT userid FROM ".SITEID."users WHERE username='Guest'")->fetchColumn();
        if (empty($guest_id)) {
            return;
        }

        $query = "
            UPDATE forum_post
            SET user_id=? WHERE user_id=-1
        ";
        $stmt = db()->prepare($query);
        $stmt->set($guest_id);
        $stmt->execute();
    }

    private function updateTopics()
    {
        DEBUG("Updating topics...");
        if(!$this->insert)

            return;

        // update forum id's
        $query = "
            UPDATE forum_topic t
            INNER JOIN import_forum i ON (i.import_id=t.forum_id)
            SET t.forum_id=i.id
        ";
        $stmt = db()->prepare($query);
        $stmt->execute();

        // update last id, last post by, etc
        $query = "DROP TEMPORARY TABLE IF EXISTS forum_topic_update";
        $stmt = db()->prepare($query);
        $stmt->execute();

        $query = "
            CREATE TEMPORARY TABLE forum_topic_update(
            topic_id int(11) NOT NULL PRIMARY KEY,
            posts int(11) NOT NULL default '0',
            first_post_id int(11),
            last_post_id int(11),
            UNIQUE(first_post_id),
            UNIQUE(last_post_id)
        )";
        $stmt = db()->prepare($query);
        $stmt->execute();

        $query = "
            INSERT INTO forum_topic_update(topic_id, first_post_id, last_post_id, posts)
            SELECT topic_id, MIN(id), MAX(id), COUNT(id)
            FROM forum_post
            GROUP BY 1";
        $stmt = db()->prepare($query);
        $stmt->execute();

        $query = "
            UPDATE forum_topic t
            INNER JOIN forum_topic_update u ON (u.topic_id=t.id)
            INNER JOIN forum_post p ON (p.id=u.last_post_id)
            SET t.posts=u.posts, t.first_post_id=u.first_post_id, t.last_post_id=u.last_post_id, t.last_post=p.created, t.last_post_by=p.user_id
        ";
        $stmt = db()->prepare($query);
        $stmt->execute();

        // cleanup
        $query = "DROP TEMPORARY TABLE IF EXISTS forum_topic_update";
        $stmt=db()->prepare($query);
        $stmt->execute();

        DEBUG("Done.");
    }

    private function updateUsers()
    {
        DEBUG("Updating users...");
        if(!$this->insert)

            return;

        // update total posts count

        $query = "DROP TEMPORARY TABLE IF EXISTS forum_user_update";
        $stmt = db()->prepare($query);
        $stmt->execute();

        $query = "
            CREATE TEMPORARY TABLE forum_user_update(
            user_id int(11) NOT NULL PRIMARY KEY,
            posts int(11) NOT NULL default '0'
        )";
        $stmt = db()->prepare($query);
        $stmt->execute();

        $query = "
            INSERT INTO forum_user_update(user_id, posts)
            SELECT user_id, COUNT(id)
            FROM forum_post
            GROUP BY 1";
        $stmt = db()->prepare($query);
        $stmt->execute();

        $query = "
            UPDATE forum_profile p
            INNER JOIN forum_user_update u ON (u.user_id=p.user_id)
            SET p.posts=u.posts
        ";
        $stmt = db()->prepare($query);
        $stmt->execute();

        // cleanup
        $query = "DROP TEMPORARY TABLE IF EXISTS forum_user_update";
        $stmt=db()->prepare($query);
        $stmt->execute();

        DEBUG("Done.");
    }

    private function updateForums()
    {
        DEBUG("Updating forums...");
        if(!$this->insert)

            return;

        // update group id
        $query = "
            UPDATE forum_forum f
            INNER JOIN import_group i ON (i.import_id=f.group_id)
            SET f.group_id=i.id
        ";
        $stmt = db()->prepare($query);
        $stmt->execute();

        // update total topics count, last topic id, etc

        $query = "DROP TEMPORARY TABLE IF EXISTS forum_forum_update";
        $stmt = db()->prepare($query);
        $stmt->execute();

        $query = "
            CREATE TEMPORARY TABLE forum_forum_update(
            forum_id int(11) NOT NULL PRIMARY KEY,
            topics int(11) NOT NULL default '0',
            last_topic_id int(11),
            UNIQUE(last_topic_id)
        )";
        $stmt = db()->prepare($query);
        $stmt->execute();

        $query = "
            INSERT INTO forum_forum_update(forum_id, last_topic_id, topics)
            SELECT forum_id, MAX(id), COUNT(id)
            FROM forum_topic
            GROUP BY 1";
        $stmt = db()->prepare($query);
        $stmt->execute();

        $query = "
            UPDATE forum_forum f
            INNER JOIN forum_forum_update u ON (u.forum_id=f.id)
            INNER JOIN forum_topic t ON (t.id=u.last_topic_id)
            SET f.topics=u.topics, f.last_post_id=t.last_post_id, f.last_post=t.last_post, f.last_post_by=t.last_post_by
        ";
        $stmt = db()->prepare($query);
        $stmt->execute();

        // cleanup
        $query = "DROP TEMPORARY TABLE IF EXISTS forum_forum_update";
        $stmt = db()->prepare($query);
        $stmt->execute();

        DEBUG("Done.");
    }

    private function updateGroups()
    {
    }

    private function importTerms()
    {
        $specialID = 127; // HACK

        DEBUG("Importing terms...");

        DEBUG("Creating group...");
        $group->group_id = $specialID;
        $group->group_name = 'Terms';
        $group->group_seq = 0;
        $groupID = $this->importGroup($group);

        $stmt = $this->db->prepare("SET @group_seq:=-1");
        $stmt->execute();

        // import WP category terms
        $query = "
            SELECT -term_taxonomy_id as forum_id, name as forum_name, (@group_seq:=@group_seq+1) AS forum_seq
            FROM `wp_term_taxonomy`
            INNER JOIN `wp_terms` USING (term_id)
            WHERE `taxonomy`='category' AND `count`>0
            ORDER BY `term_taxonomy_id`"
        ;
        $stmt = $this->db->prepare($query);
        $stmt->execute();

        foreach ($stmt as $row) {
            $this->forums++;
            DEBUG("Found term {$row->forum_name} with id {$row->forum_id}");
            // Import group and get new id
            $row->group_id = $specialID;
            $this->importForum($row);
        }

        DEBUG("Done.");
    }

    private function importTermTopics($limit)
    {
        DEBUG("Importing term topics...");

        for ($offset=0; ; $offset+=$limit) {
            $query = "
                SELECT
                -p.ID AS topic_id,
                post_title AS topic_name,
                post_date AS topic_date,
                -r.term_taxonomy_id as forum_id,
                0 AS topic_pinned,
                IF(comment_status='closed',0,1) AS topic_opened,
                0 AS post_count,
                p.post_content,
                p.post_date,
                /* magic constant follows */
                -p.ID - 1000000 AS post_id,
                p.post_author AS user_id
                FROM `wp_term_taxonomy` t
                INNER JOIN `wp_term_relationships` r ON (r.term_taxonomy_id=t.term_taxonomy_id)
                INNER JOIN `wp_posts` p ON (p.ID=r.object_id)
                INNER JOIN `wp_users` u ON (u.ID=p.post_author)
                WHERE `taxonomy`='category' AND `count`>0 AND `post_status`='publish'
                ORDER BY `post_date`
                LIMIT {$limit}
                OFFSET {$offset}
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute();

            $imported = 0;
            foreach ($stmt as $row) {
                $this->topics++;
                $this->posts++;
                DEBUG("Found term topic with id {$row->topic_id}");
                $this->importTopic($row);
                $this->importPost($row);
                $imported++;
            }
            if (!$imported) {
                break;
            }
        }

        DEBUG("Done.");
    }

    private function importTermComments($limit)
    {
        DEBUG("Importing term comments...");

        for ($offset=0; ; $offset+=$limit) {
            $query = "
                SELECT
                -p.ID AS topic_id,
                -c.comment_ID AS post_id,
                c.comment_date AS post_date,
                c.comment_content AS post_content,
                /* posts from unknown users go to guest */
                COALESCE(u.ID,-1) AS user_id
                FROM `wp_term_taxonomy` t
                INNER JOIN `wp_term_relationships` r ON (r.term_taxonomy_id=t.term_taxonomy_id)
                INNER JOIN `wp_posts` p ON (p.ID=r.object_id)
                INNER JOIN `wp_comments` c ON (c.comment_post_ID=p.ID)
                LEFT JOIN `wp_users` u ON (u.ID=c.user_id)
                WHERE `taxonomy`='category' AND `count`>0 AND `post_status`='publish'
                AND c.user_id>0 and c.comment_type!='pingback'
                ORDER BY `comment_date`
                LIMIT {$limit}
                OFFSET {$offset}
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute();

            $imported = 0;
            foreach ($stmt as $row) {
                // ugh..
                $this->posts++;
                DEBUG("Found term comment with id {$row->post_id}");
                $this->importPost($row);
                $imported++;
            }
            if (!$imported) {
                break;
            }
        }

        DEBUG("Done.");
    }

    private function importPrivMessage($obj)
    {
        if(!$this->insert)

            return;

        DEBUG("Importing priv message...");

        $query = "
            INSERT INTO forum_pm_messages (sender_id,receiver_id,sent_date,read_date,parent_id,subject,message,status,inbox,outbox)
            VALUES (?,?,?,?,?,?,?,?,?,?)";
        $stmt = db()->prepare($query);
        $stmt->set($obj->sender_id);
        $stmt->set($obj->receiver_id);
        $stmt->set($obj->sent_date);
        $stmt->set($obj->read_date);
        $stmt->set($obj->parent_id);
        $stmt->set($this->html2bb($obj->subject));
        $stmt->set($this->html2bb($obj->message));
        $stmt->set($obj->status);
        $stmt->set($obj->inbox);
        $stmt->set($obj->outbox);
        $stmt->execute();

        $newID = db()->lastInsertID();

        $query = "INSERT INTO import_pm(id, import_id) VALUES (?,?)";
        $stmt = db()->prepare($query);
        $stmt->set($newID);
        $stmt->set($obj->pm_id);
        $stmt->execute();

        DEBUG("Done.");
    }

    private function importPrivMessages($limit)
    {
        DEBUG("Importing private messages...");

        for ($offset=0; ; $offset+=$limit) {
            $query = "
                SELECT
                message_id as pm_id,
                sent_date,
                sent_date + interval 1 hour AS read_date,
                from_id AS sender_id,
                to_id AS receiver_id,
                NULL AS parent_id,
                title AS subject,
                message,
                'read' AS status,
                inbox,
                sentbox AS outbox
                FROM wp_sfmessages
                WHERE to_id IS NOT NULL
                ORDER BY message_id ASC
                LIMIT {$limit}
                OFFSET {$offset}
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute();

            $imported = 0;
            foreach ($stmt as $row) {
                $this->pms++;
                DEBUG("Found private message with id {$row->pm_id}");
                $this->importPrivMessage($row);
                $imported++;
            }
            if (!$imported) {
                break;
            }
        }

        DEBUG("Done.");
    }

    private function updatePrivMessages()
    {
        DEBUG("Updating private messages...");
        if(!$this->insert)

            return;

        // update user ids
        $query = "
            UPDATE forum_pm_messages pm
            LEFT JOIN import_user i1 ON (i1.import_id=pm.sender_id)
            LEFT JOIN import_user i2 ON (i2.import_id=pm.receiver_id)
            SET pm.sender_id=COALESCE(i1.id,-1), pm.receiver_id=COALESCE(i2.id,-1)
        ";
        $stmt = db()->prepare($query);
        $stmt->execute();

        // delete messages from unknown users
        $query = "
            DELETE FROM forum_pm_messages WHERE sender_id=-1 OR receiver_id=-1
        ";
        $stmt = db()->prepare($query);
        $stmt->execute();
    }
}
