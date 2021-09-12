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
 * 2010 Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * The Initial Developer of the Original Code is
 * Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * Portions created by the Initial Developer are Copyright (C) 2010
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
 * Login library.
 *
 * Handles login
 *
 * @package       Kernel
 * @subpackage    Auth
 * @uses          Common_Log
 */

if(!defined('ALLOWEDLOGINATTEMPTS')) DEFINE('ALLOWEDLOGINATTEMPTS', 10);

class auth_login
{
    private $username;
    private $password;

    public function __construct($username = false, $password = false)
    {
        if ($username) {
            $this->username = $username;
        }
        if ($password) {
            $this->password = $password;
        }
    }

    public function __set($key, $value)
    {
        $this->$key = $value;
    }

    public function login()
    {
        // Check for failed logins from target IP
        if (ALLOWEDLOGINATTEMPTS !== false) {
            $rs = db()->prepare("SELECT COUNT(*) AS count FROM logs WHERE ip=? AND type='LoginError' AND created>=".strtotime("-1 hour"));
            $rs->set($_SERVER["REMOTE_ADDR"]);
            $rs->execute();
            $attempts = $rs->fetchColumn();

            // Show error page if too many false logins
            if ($attempts > ALLOWEDLOGINATTEMPTS) {
                throw new Exception("Your IP-address is banned. Too many false logins.");
            }
        }

        // .. Otherwise, try to authenticate
        $auth = new Auth;
        if ($auth->authenticate($this->username, $this->password)) {
            Common_Log::auth("LoginOK","User logged in", $this->username);
        } else {
            // Log failed login attempt
            Common_Log::auth("LoginError", sprintf("Invalid login %s", $this->username), $this->username);
            throw new Exception("Login failed");
        }
    }

}
