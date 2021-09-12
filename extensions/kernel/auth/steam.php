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
class auth_steam implements Auth_IFace, Auth_PWResetIFace
{
    public function authenticate($user = false, $pass = false)
    {
        // Check if user exists
        if (!Common_Validate::user($user)) {
            return false;
        }

        return $pass; // Should be true if steam sso was succesful
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

    public function updatePassword($user, $pass)
    {
        return false;
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
        setcookie("ForumAutologin", "", time() - (3600 * 24 * 14), $request->getPath());

        // Important!
        // This may be called by auth driver on authentication failures,
        // so check for userid before attempting to update user profile
        // to avoid errors.
        if (isset($_SESSION["UserID"])) {
            $user = new Common_User;
            $user->setSetting("AutologinHash", "");
            $user->setSetting("AutologinExpire", "");
        }
    }

}
