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
 * Autologin/"remember me"
 *
 * @package       Kernel
 * @subpackage    Auth
 * @uses          Common_User
 * @uses          Controller_Request
 * @uses          Forum_Login
 * @uses          Forum_Unread
 */

class auth_autologin implements Auth_IFace, Auth_PWResetIFace
{
    /**
     * Authentication. Takes username+hash as parameter.
     *
     * @access      public
     * @param string $user Username
     * @param string $pass Password
     * @uses        Common_User
     * @uses        Controller_Request
     * @uses        Forum_Login
     * @uses        Forum_Unread
     *
     * @return bool
     */
    public function authenticate($user, $pass)
    {
        $request = Controller_Request::getInstance();

        // Try to get autologin hash for the user
        $cuser = new Common_User;
        $hash = $cuser->getSetting("AutologinHash", $user);

        // Autologin expired
        if (time() > $cuser->getSetting("AutologinExpire", $user)) {
            return false;
        }

        // Autologin found, hashes match
        if ($hash == $pass) {
            $_SESSION["AuthComplete"] = 0;

            // Regular login procedure
            $_SESSION["User"] = $user;
            $_SESSION["UserID"] = $cuser->getUserIDByName($user);
            $_SESSION["Groups"] = array();

            // Generic session variables for the framework
            $_SESSION["IP"] = Auth::getClientIP();
            $_SESSION["BASEHREF"] = $request->getBaseUri(true);

            // Check for bans
            Forum_Login::checkBan();

            // Check for verified status
            Forum_Login::checkVerified();

            // Load user permissions to session
            Forum_Login::loadAccessGroups();

            // Update unread posts
            $unread = new Forum_Unread;
            $unread->get();

            // Mark authentication as complete into the session
            $_SESSION["AuthComplete"] = 1;

            // Redirect back to original url
            $request = Controller_Request::getInstance();
            Controller_Redirect::to($request->getUri(), true);
        }
    }

    public function authorize($userid, $module)
    {
        //
    }

    public function getExternalUserID($user)
    {
        //
    }

    public function getExternalSessionID($user)
    {
        //
    }

    /**
     * Update password
     *
     * @access      public
     * @param string $user Username
     * @param string $pass Password
     */
    public function updatePassword($user,$pass)
    {
        // Autologin does not implement password changing
        return false;
    }

    /**
     * Logout
     *
     * @access      public
     * @uses        Common_User
     * @uses        Controller_Request
     */
    public function logout()
    {
        // Clean autologin hash and cookie
        $request = Controller_Request::getInstance();

        // Destroy the AutoLogin cookie
        setcookie("ForumAutologin", "", time() - (3600 * 24 * 14), $request->getPath());

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

}
