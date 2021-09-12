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
 * @uses          Auth
 */

class auth_autologincheck
{
    /**
     * Autologin if ForumAutoLogin cookie is set.
     *
     * @access      public
     * @uses        Auth
     */
    public static function autologin()
    {
        // Skip autologin if we're already logged in
        if (isset($_SESSION["UserID"])) {
            return;
        }

        // Check for autologin if we're not logged in and ForumAutoLogin cookie is set.
        if (!isset($_SESSION["UserID"]) && isset($_COOKIE["ForumAutologin"]) && !empty($_COOKIE["ForumAutologin"])) {
            // Cookie format username;hash
            $hash = explode(";",$_COOKIE["ForumAutologin"]);
            $auth = new Auth;

            // Swap to Auth_Autologin driver
            $auth->setAuthenticationDriver("Autologin");

            // Attempt autologin...
            try {
                if (empty($hash[0]) || empty($hash[1])) {
                    throw new Exception("Empty values.");
                }

                $auth->authenticate($hash[0], $hash[1]);
            } catch (Exception $e) {
                die($e->getMessage());
                // ... we just ignore autologin if it fails

            }
        }
    }

}
