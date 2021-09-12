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
 * 2004,2005,2011 Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * The Initial Developer of the Original Code is
 * Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * Portions created by the Initial Developer are Copyright (C) 2004,2005,2011
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * 2011 Johannes Athmer <hangy@warsow.net>
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
 * Database authentication driver. Authenticates user against database backend.
 *
 * @package    Kernel
 * @subpackage Auth
 * @uses       Common_Validate
 * @uses       Controller_Request
 * @uses       Common_User
 * @uses       Auth_PasswordHash
 */

class auth_fauth implements Auth_IFace, Auth_PWResetIFace
{
    /**
     * Authentication. Takes username+password as parameter.
     *
     * Checks that user exists & passwords match. Returns true or false depending
     * on wether login was succesfull.
     *
     * @access      public
     * @param  string $user Username
     * @param  string $pass Password
     * @return bool
     * @uses        Common_Validate
     */
    public function authenticate($user = false, $pass = false)
    {
        // Check if user exists
        if (!Common_Validate::user($user)) {
            return false;
        }

        // Get password
        try {
            $query = "
                SELECT password
                FROM users
                WHERE username = ?";

            $stmt = db()->prepare($query);
            $stmt->bind($user)->execute();
            $password = $stmt->fetchColumn();
        } catch (Exception $e) {
            return false;
        }

        // Empty password = disable account
        if (empty($password)) {
            return false;
        }

        // Detect password type from its length
        switch (strlen($password)) {
            // MD5 hashed password
            case 32:
                return $this->authenticateMD5($pass, $password);

            // PunBB-style passwords
            case 40:
                return $this->authenticatePunBB($user, $pass, $password);

            // Phpass salted passwords
            default:
                return $this->authenticatePhpass($pass, $password);

        }

        // None matched, false
        return false;
    }

    public function authorize($user, $module)
    {
        if (isset($_SESSION["UserID"])) {
            return true;
        }

        return false;
    }

    public function getExternalUserID($user)
    {
        $tmp = new Common_User;

        return $tmp->getUserIDByName($user);
    }

    public function getExternalSessionID($user)
    {
        return false;
    }

    /**
     * Updates user password to database.
     *
     * @param  string $user Username
     * @param  string $pass Password
     * @return bool   True on success, false on error or if nothing was updated.
     *
     * @TODO Detect password type and use the same hashing method as the PW originally had.
     */
    public function updatePassword($user, $pass)
    {
        $pass = md5($pass);
        try {
            $query = "
                UPDATE users
                SET password=?,updated=".time()."
                WHERE username=?";

            $stmt = db()->prepare($query);
            $stmt->bind($pass)->bind($user)->execute();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Logut
     *
     * @uses        Controller_Request.
     */
    public function logout()
    {
        // Clean autologin hash and cookie
        $request = Controller_Request::getInstance();

        // Destroy the AutoLogin cookie
        setcookie("ForumAutologin", "", time()-(3600 * 24 * 14), $request->getPath());

        // Important!
        // This may be called by auth driver on authentication failures,
        // so check for userid before attempting to update user profile
        // to avoid errors.
        if (isset($_SESSION["UserID"])) {
            $user = new Common_User;
            $user->setSetting("AutologinHash","");
            $user->setSetting("AutologinExpire","");
        }
    }

    /* Authentication methods */

    /*
     * Basic md5 auth
     *
     * @param  string $user Username
     * @param  string $pass Password
     * @return bool   True on success, false on error
     */
    private function authenticateMD5($pass, $password)
    {
        return !empty($password) && md5(trim($pass)) == trim($password);
    }

    /*
     * PunBB salted sha1
     *
     * @param  string $user        Username
     * @param  string $pass        Password
     * @param  string $stored_hash Hash
     * @return bool   True on success, false on error
     * @uses        Common_User.
     */
    private function authenticatePunBB($user, $pass, $stored_hash)
    {
        $common_user = new Common_User;
        $salt = $common_user->getSetting('PunBBSalt', $user);

        return $stored_hash == sha1($salt.sha1($pass));
    }

    /*
     * Portable PHP password hash (phpass); ie. from WordPress
     *
     * @param  string $pass        Password
     * @param  string $stored_hash Hash
     * @return bool   True on success, false on error
     * @uses        Auth_PasswordHash.
     */
    private function authenticatePhpass($pass, $stored_hash)
    {
        $phpass = new Auth_PasswordHash(8, TRUE);

        return $phpass->CheckPassword($pass, $stored_hash);
    }

}
