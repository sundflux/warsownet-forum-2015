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
 * 2005,2006,2013 Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * The Initial Developer of the Original Code is
 * Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * Portions created by the Initial Developer are Copyright (C) 2005,2006,2013
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * 2007 Markus Sällinen <mack@angelinecms.info>
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
 * Library for handling users
 *
 * @package       Kernel
 * @subpackage    Common
 */

class common_user
{
    /**
     * Get username by userid.
     *
     * @access      public
     * @param  int   $id UserID
     * @return mixed Either string with username or false if not found.
     */
    public function getUsernameByID($id)
    {
        try {
            $stmt = db()->prepare("SELECT username FROM users WHERE userid=?");
            $stmt->set((int) $id)->execute();
        } catch (Exception $e) {
            throw new DB_Exception("Unable to look up userid.");
        }

        return $stmt->fetchColumn();
    }

    /**
     * Get users ID by username.
     *
     * @access      public
     * @param  strint $name Users username
     * @return mixed  Either string with userid or false if not found.
     */
    public function getUserIDByName($name)
    {
        try {
            $stmt = db()->prepare("SELECT userid FROM users WHERE username=?");
            $stmt->set($name)->execute();
        } catch (Exception $e) {
            throw new DB_Exception("Unable to look up username.");
        }

        return $stmt->fetchColumn();
    }

    /**
     * Adds new user.
     *
     * @access  public
     * @param  string $user     Username
     * @param  string $password Password
     * @param  string $name     Users name
     * @return User   ID
     */
    public function addUser($user, $password, $name = "", $asHash = false)
    {
        $user = trim($user);
        if (Common_Validate::user($user)) {
            throw new Exception("Unable to add new user. User with same username already exists.");
        }
        if (strlen($user) < 2) {
            throw new Exception("Username must be atleast 2 characters long.");
        }
        if (!$asHash) {
            $password = md5(trim($password));
        }
        $name = trim($name);
        $time = time();
        try {
            $stmt = db()->prepare("INSERT INTO users (name,username,password,updated,created) VALUES (?,?,?,?,?)");
            $stmt->set($name)->set($user)->set($password)->set($time)->set($time)->execute();
            if (LIBVALOA_DB === "mysql") {
                return db()->lastInsertID();
            }

            return $this->getUserIDByName($user);
        } catch (Exception $e) {
            throw new DB_Exception("Unable to add new user.");
        }
    }

    /**
     * Deletes user.
     *
     * @access public
     * @param  mixed Either username or user id
     */
    public function deleteUser($user)
    {
        if (is_numeric($user)) {
            if (!$this->getUserNameByID($user)) {
                $user = $this->getUserIDByName($user);
            }
        } else {
            $user = $this->getUserIDByName($user);
        }
        if (!$user) {
            return false;
        }
        if ($user <> $this->getUserIDByName("root")) {
            try {
                $stmt = db()->prepare("DELETE FROM userfeature WHERE userid=?");
                $stmt->set((int) $user)->execute();
                $stmt = db()->prepare("DELETE FROM users WHERE userid=?");
                $stmt->set((int) $user)->execute();
            } catch (Exception $e) {
                throw new DB_Exception("Unable to delete user.");
            }
        } else {
            throw new Exception("Unable to delete user. Root user can not be deleted!");
        }
    }

    /**
     * Returns user specific value for setting entry.
     *
     * @access public
     * @param  string $item Setting entry to lookup.
     * @param  mixed  $user Either username, user id or false to use user id from session
     * @return mixed  Either string with setting value or false if not found
     */
    public function getSetting($item, $user = false)
    {
        if (is_numeric($user)) {
            $userid = $user;
        } elseif (!$user && isset($_SESSION["UserID"])) {
            $userid = $_SESSION["UserID"];
        } elseif ($user) {
            $userid = $this->getUserIDByName($user);
        }
        if (!isset($userid) || !$userid) {
            return false;
        }
        if (isset($_SESSION["UserID"]) && $_SESSION["UserID"] == $userid) {
            $cache = new Cache;
            if ($cache->compare("usersetting", $item)) {
                return $cache->read("usersetting");
            }
        }
        try {
            $stmt = db()->prepare("SELECT value FROM userfeature WHERE keyword=? AND userid=?");
            $stmt->bind($item)->set((int) $userid)->execute();
        } catch (Exception $e) {
            return false;
        }
        $value = $stmt->fetchColumn();
        if ($value !== false) {
            if (isset($_SESSION["UserID"]) && $_SESSION["UserID"] == $userid) {
                $cache->update("usersetting", $value, $item);
            }
        }

        return $value;
    }

    /**
     * Sets user specific value for settings entry.
     *
     * @access public
     * @param string $item  Entry name
     * @param string $value Entry value
     * @param mixed  $user  Either username, user id or false to use user id from session
     */
    public function setSetting($item, $value, $user = false)
    {
        if (is_numeric($user)) {
            $userid = $user;
        } elseif (!$user && isset($_SESSION["UserID"])) {
            $userid = $_SESSION["UserID"];
        } elseif ($user) {
            $userid = $this->getUserIDByName($user);
        }
        if (!$item || !isset($userid) || !$userid) {
            throw new Exception("Unable to update user profile.");
        }
        $time = time();
        if (!$value) {
            $stmt = db()->prepare("DELETE FROM userfeature WHERE userid=? AND keyword=?");
            $stmt->set((int) $userid)->bind($item)->execute();
        } elseif ($this->getSetting($item,$userid) === false) {
            $stmt = db()->prepare("INSERT INTO userfeature (userid,keyword,value,updated,created) VALUES (?,?,?,?,?)");
            $stmt->set((int) $userid)->bind($item)->bind($value)->bind($time)->bind($time);
        } else {
            $stmt = db()->prepare($query="UPDATE userfeature SET value=?,updated=".time()." WHERE userid=? AND keyword=?");
            $stmt->bind($value)->set((int) $userid)->bind($item);
        }
        try {
            $stmt->execute();
        } catch (Exception $e) {
            throw new DB_Exception("Unable to update user profile.");
        }
        if (isset($_SESSION["UserID"]) && $_SESSION["UserID"] == $userid) {
            $cache = new Cache;
            $cache->update("usersetting", $value, $item);
        }
    }

    /**
     * Get number of users in database.
     *
     * @access      public
     * @return int Number of users in user table
     */
    public static function getUserCount()
    {
        $stmt = db()->execute("SELECT COUNT(*) FROM users");

        return $stmt->fetchColumn();
    }

    /**
     * Search Users.
     *
     * @access      public
     * @static
     * @param string $search string to search from usernames
     */
    public static function search($search = "")
    {
        try {
            $searchparams = "";
            if ($search) {
                $searchparams.= "WHERE username LIKE ?";
                $search = "%{$search}%";
            }
            $stmt = db()->prepare("SELECT name,username,userid FROM users {$searchparams}");
            if ($search) {
                $stmt->bind($search);
            }
            $stmt->execute();
        } catch (Exception $e) {
            throw new DB_Exception("Unable to search user.");
        }
        $rows = $stmt->fetchAll();

        return empty($rows)?false:$rows;
    }

}
