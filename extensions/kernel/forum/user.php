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
 * User related stuff, updating post counts etc.
 *
 * @package       Kernel
 * @subpackage    Forum
 */

if(!defined('FORUM_USERS_PER_PAGE')) DEFINE('FORUM_USERS_PER_PAGE', 20);

class forum_user
{
    public $user; // User object with profile
    private $updatePassword = false;
    private $updateUsername = false;

    /**
     * Initialize user class
     *
     * @access      public
     * @param mixed $id UserID. False/empty to create new user object
     */
    public function __construct($id = false)
    {
        // Initialize empty user object
        $this->_empty();

        // Load user by id
        if ($id || is_numeric($id)) {
            $this->byID($id);
        }
    }

    /**
     * Set value in user object
     *
     * @access      public
     * @param string $k Key
     * @param mixed  $v Value
     */
    public function __set($k, $v)
    {
        // password got updated
        if ($k == "password") {
            $this->updatePassword = true;
        }
        // username got updated
        if ($k == "username") {
            $this->updateUsername = true;
        }
        if ($k == "user_from") {
            $this->user->location = $v;
        }
        $this->user->$k = $v;
    }

    /**
     * Get value from user object
     *
     * @access      public
     * @param string $k Key
     */
    public function __get($k)
    {
        // Alias user_from<>location for compability
        if ($k == "user_from" && isset($this->user->location)) {
            return $this->user->location;
        }
        if (isset($this->user->$k)) {
            return $this->user->$k;
        }
    }

    /**
     * Initialize empty user object
     * @access      public
     */
    public function _empty()
    {
        $this->user=new stdClass;
        $this->user->id = "";
        $this->user->alias = "";
        $this->user->username = "";
        $this->user->password = "";
        $this->user->joined = "";
        $this->user->last_visit = "";
        $this->user->posts = 0;
        $this->user->email = "";
        $this->user->avatar = "";
        $this->user->signature = "";
        $this->user->bio = "";
        $this->user->gravatar = 0;
        $this->user->banned = 0;
        $this->user->title = "";
        $this->user->location = "";
        $this->user->real_name = "";
        $this->user->www = "";
        $this->user->skype = "";
        $this->user->facebook = "";
        $this->user->twitter = "";
        $this->user->jabber = "";
        $this->user->msn = "";
        $this->user->icq = "";
        $this->user->asHash = false;
        $this->user->admin = 0;
        $this->user->moderator = 0;
    }

    /**
     * Load user by ID
     *
     * @access      public
     * @param int $id UserID
     */
    public function byID($id)
    {
        foreach (self::getProfile($id) as $p) {
            $this->user = $p;
            $this->user->id = $p->user_id;
            unset($this->user->user_id);
        }
    }

    /**
     * Commit user object
     *
     * @access      public
     * @uses        Common_User
     * @uses        Auth
     */
    public function commit()
    {
        try {
            db()->beginTransaction();
            if (empty($this->user->id) && !is_numeric($this->user->id)) {
                // new user
                $user = new Common_User;
                $this->user->id = $user->addUser(trim($this->user->username), $this->user->password, "", $this->user->asHash);

                // create empty profile
                $query = "
                    INSERT INTO forum_profile (
                        user_id,
                        alias,
                        joined,
                        last_visit,
                        posts,
                        email,
                        avatar,
                        bio,
                        gravatar,
                        banned,
                        title,
                        location,
                        real_name,
                        signature,
                        admin,
                        moderator)

                    VALUES (
                        ?,
                        COALESCE(?,NOW()),
                        COALESCE(?,NOW()),
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?)";

                $stmt = db()->prepare($query);
                $stmt->set((int) $this->user->id);
                $stmt->set($this->user->alias);
                if (empty($this->user->joined)) {
                    $this->user->joined = date("Y-m-d H:i:s", time());
                }
                $stmt->set($this->user->joined);
                $stmt->set($this->user->last_visit);
                $stmt->set($this->user->posts);
                $stmt->set($this->user->email);
                $stmt->set($this->user->avatar);
                $stmt->set($this->user->bio);
                $stmt->set($this->user->gravatar);
                $stmt->set($this->user->banned);
                $stmt->set($this->user->title);
                $stmt->set($this->user->location);
                $stmt->set($this->user->real_name);
                $stmt->set($this->user->signature);
                $stmt->set($this->user->admin);
                $stmt->set($this->user->moderator);

                $stmt->execute();
            } elseif (!empty($this->user->id) && is_numeric($this->user->id)) {
                // Edit old user
                // Update password if needed
                if ($this->updatePassword) {
                    $auth = new Auth;
                    $auth->updatePassword($this->user->username, $this->user->password);
                }
                if ($this->updateUsername) {
                    $query = "
                        UPDATE users
                        SET username=?
                        WHERE userid=?";

                    $stmt = db()->prepare($query);
                    $stmt->set($this->user->username);
                    $stmt->set((int) $this->user->id);
                    $stmt->execute();
                }

                // Update profile
                $query = "
                    UPDATE forum_profile
                    SET alias=?,joined=?,last_visit=?,posts=?,email=?,avatar=?,bio=?,gravatar=?,banned=?,title=?,location=?,real_name=?,www=?,skype=?,facebook=?,twitter=?,jabber=?,msn=?,icq=?,signature=?,admin=?,moderator=?
                    WHERE user_id=?";

                $stmt=db()->prepare($query);

                $stmt->set($this->user->alias);
                // Blah, bubblegum for bugged imported forum registrations.
                if ($this->user->joined == "0000-00-00 00:00:00") {
                    $this->user->joined = date("Y-m-d H:i:s", time());
                }
                $stmt->set($this->user->joined);
                $stmt->set($this->user->last_visit);
                $stmt->set((int) $this->user->posts);
                $stmt->set($this->user->email);
                $stmt->set($this->user->avatar);
                $stmt->set($this->user->bio);
                $stmt->set((int) $this->user->gravatar);
                $stmt->set((int) $this->user->banned);
                $stmt->set($this->user->title);
                $stmt->set($this->user->location);
                $stmt->set($this->user->real_name);
                $stmt->set($this->user->www);
                $stmt->set($this->user->skype);
                $stmt->set($this->user->facebook);
                $stmt->set($this->user->twitter);
                $stmt->set($this->user->jabber);
                $stmt->set($this->user->msn);
                $stmt->set($this->user->icq);
                $stmt->set($this->user->signature);
                $stmt->set($this->user->admin);
                $stmt->set($this->user->moderator);
                $stmt->set((int) $this->user->id);
                $stmt->execute();
            }

            db()->commit();
        } catch (Exception $e) {
            db()->rollBack();
            throw new Exception("User creation failed. " . $e->getMessage());
        }
    }

    /**
     * Get user profile
     *
     * @access      public
     * @param  mixed  $id     UserID or array of ids
     * @param  array  $fields Profile fields to select
     * @uses        Format
     * @return object Profile
     */
    public static function getProfile($id, $fields = false, $edit = false)
    {
        if (!is_array($id) && !is_numeric($id)) {
            throw new Exception("Malformed UserID");
        }
        // id can take either an array of id's or a single id.
        // if we get just one id, lets make it as array with single value
        if (!is_array($id)) {
            $tmp[] = $id;
            $id = $tmp;
        }

        // by default search all fields unless otherwise specified
        if (!is_array($fields)) {
            unset($fields);
            $fields[] = "forum_profile.*";
        }

        // Get user profiles
        $query = "
            SELECT users.username,".implode(",", $fields)."
            FROM users,forum_profile
            WHERE users.userid=forum_profile.user_id AND forum_profile.user_id IN (".implode(",",array_unique($id)).")";

        try {
            $stmt = db()->prepare($query);
            $stmt->execute();
            foreach ($stmt as $user) {
                $users[$user->user_id] = $user;

                // format bio for viewing
                if (!empty($users[$user->user_id]->bio)) {
                    $users[$user->user_id]->bio = Security::strip($users[$user->user_id]->bio);
                    $users[$user->user_id]->bioView = Format::parse(htmlspecialchars(Security::strip($users[$user->user_id]->bio)), FORUM_PARSER_INTERFACE);
                }

                // External avatar url
                if (strpos($users[$user->user_id]->avatar, 'http://') !== false) {
                    $users[$user->user_id]->external_avatar = $users[$user->user_id]->avatar;
                }

                // Check for broken avatar images
                if($users[$user->user_id]->gravatar == 0 &&
                        (empty($users[$user->user_id]->avatar) // empty avatar
                        // cheap check first for gravatar mdsums when gravatars are disabled
                        || (strlen($users[$user->user_id]->avatar) == 32 && preg_match('/^[a-f0-9]{32}$/', $users[$user->user_id]->avatar))
                        // real does file exist check. most of the buggy import cases should be catched by above check anyway.
                        || !file_exists(AVATARPATH."/".$users[$user->user_id]->avatar) )) {
                    unset($users[$user->user_id]->avatar);
                }

                // disable signatures if defined
                if (FORUM_DISABLE_SIGNATURES == 1 && !$edit) {
                    if (isset($users[$user->user_id]->signature)) {
                        unset($users[$user->user_id]->signature);
                    }

                    // Bail out here so we don't format signature for nothing in the next step
                    continue;
                }

                if (isset($users[$user->user_id]->www)) {
                    $users[$user->user_id]->www = Security::strip($users[$user->user_id]->www);
                }

                // format signature for viewing
                if (!empty($users[$user->user_id]->signature)) {
                    $users[$user->user_id]->signatureView = Format::parse(htmlspecialchars(Security::strip($users[$user->user_id]->signature)), FORUM_PARSER_INTERFACE);
                }

                // steam identifier
                if (substr($user->username, 0, 6) == 'steam_') {
                    $users[$user->user_id]->steam = 1;
                }
            }
            if (isset($users)) {
                return $users;
            }

            return false;
        } catch (Exception $e) {
            throw new Exception("Database error when getting users.");
        }
    }

    /**
     * Update post count for user
     *
     * @access      public
     * @param int $id Topic ID
     */
    public static function update($id)
    {
        $query="
            UPDATE forum_profile
            SET posts = posts + 1
            WHERE user_id=?";

        $stmt = db()->prepare($query);
        $stmt->set((int) $id);
        try {
            $stmt->execute();
        } catch (Exception $e) {

        }
    }

    /**
     * Recount post count for user
     *
     * @access      public
     * @param int $id Topic ID
     */
    public static function hardUpdate($id)
    {
        // Count number of posts
        $query = "
            SELECT COUNT(id) AS count
            FROM forum_post
            WHERE user_id=?";

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $id);
            $stmt->execute();
            $count=$stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Database error while counting posts.");
        }

        // Update topic data
        $query="
            UPDATE forum_profile
            SET posts=?
            WHERE user_id=?";

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $count->count)->set((int) $id);
            $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Database error while updating user.");
        }
    }

    /**
     * Get users with pagination or limited by search string
     *
     * @access      public
     * @param  int    $page         Page of results to get
     * @param  string $searchstring Username to search for
     * @uses        Forum_Pagination
     * @return object
     */
    public static function getUsers($page, $searchstring = false)
    {
        // Search by username
        $f = "";
        if ($searchstring) {
            $f.= " AND users.username LIKE '%".$searchstring."%' ";
        }

        // Get number of users
        $query = "
            SELECT COUNT(forum_profile.id) AS count
            FROM users, forum_profile
            WHERE forum_profile.user_id = users.userid {$f}";

        try {
            $stmt = db()->prepare($query);
            $stmt->execute();
            $numUsers = $stmt->fetchColumn();
        } catch (Exception $e) {

        }

        // Pagination
        $users = new stdClass;
        $users->pages = Forum_Pagination::pages($page, FORUM_USERS_PER_PAGE, $numUsers);

        // Actual searching, include only users with forum profile, ignore the rest (non-forum users).
        $query = "
            SELECT users.userid as user_id, forum_profile.id as profile_id, users.username as username
            FROM users, forum_profile
            WHERE forum_profile.user_id = users.userid {$f}
            ORDER BY users.username ASC
            LIMIT {$users->pages->limit}
            OFFSET {$users->pages->offset}";

        try {
            $stmt = db()->prepare($query);
            $stmt->execute();
            $users->users = $stmt->fetchAll();
        } catch (Exception $e) {

        }

        return $users;
    }

    public static function getProfileIDByUserID($userid)
    {
        $query = "
            SELECT id
            FROM forum_profile
            WHERE user_id = ?";

        $stmt = db()->prepare($query);
        $stmt->set((int) $userid);
        try {
            $stmt->execute();

            return $stmt->fetchColumn();
        } catch (Exception $e) {

        }
    }

    public static function getUserIDByProfileID($profileid)
    {
        $query = "
            SELECT user_id
            FROM forum_profile
            WHERE id = ?";

        $stmt = db()->prepare($query);
        $stmt->set((int) $userid);
        try {
            $stmt->execute();

            return $stmt->fetchColumn();
        } catch (Exception $e) {

        }
    }

    public static function getAccessGroups($userid)
    {
        $profile_id = self::getProfileIDByUserID($userid);

        $query = "
            SELECT forum_accessgroup.id as accessgroup_id
            FROM forum_accessgroup, forum_profile_acl
            WHERE forum_accessgroup.id = forum_profile_acl.accessgroup_id
            AND forum_profile_acl.profile_id = ?";

        $stmt = db()->prepare($query);
        $stmt->set((int) $profile_id);
        try {
            $stmt->execute();
            foreach ($stmt as $row) {
                $ids[] = $row->accessgroup_id;
            }

            $groups = Forum_ACL::getAccessGroups();
            foreach ($groups as $group) {
                if (isset($ids) && is_array($ids) && in_array($group->id, $ids)) {
                    $group->access = true;
                }
                $tmp[] = $group;
            }

            if (isset($tmp)) {
                return $tmp;
            }
        } catch (Exception $e) {

        }
    }

    public static function clearAccessGroups($userid)
    {
        $profile_id = self::getProfileIDByUserID($userid);

        $query = "
            DELETE FROM forum_profile_acl
            WHERE profile_id = ?";

        $stmt = db()->prepare($query);
        $stmt->set((int) $profile_id);
        try {
            $stmt->execute();
        } catch (Exception $e) {

        }
    }

    public static function addAccessGroup($userid, $accessgroupid)
    {
        $profile_id = self::getProfileIDByUserID($userid);

        $query = "
            INSERT INTO forum_profile_acl (profile_id, accessgroup_id)
            VALUES (?,?)";

        $stmt = db()->prepare($query);
        $stmt->set((int) $profile_id);
        $stmt->set((int) $accessgroupid);
        try {
            $stmt->execute();
        } catch (Exception $e) {

        }
    }

}
