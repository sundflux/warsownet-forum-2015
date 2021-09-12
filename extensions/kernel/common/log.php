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
 * 2004,2005,2007 Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * The Initial Developer of the Original Code is
 * Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * Portions created by the Initial Developer are Copyright (C) 2004,2005,2007
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * Joni Halme <jontsa@angelinecms.info>
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
 * Library for logging events.
 *
 * Events contain the following
 * - UserID which is detected automatically or 0 if not logged in. There is one exception however
 *   which is log entries from authentication. If user attempts to login with invalid password,
 *   the userid of the username that was used is stored to log entry.
 * - Priority or "log level". This is determined by the method you are using (info, debug etc)
 *   Log level is stored as integer. See www.php.net/syslog for log level constants.
 * - Facility which is not to be confused with syslog facility. This is a string identifier
 *   that tells where the log entry is came from. This can be controller name, module or package for example
 * - Type is a short string identifier for the log entry to make machine parsing of logs easier
 *   For example if your log entry is about user updating his/her name, type could be "namechange"
 * - Description is a human readable log entry
 * - IP-address which is detected automatically or "cli" if used from console.
 *
 * @package       Kernel
 * @subpackage    Common
 * @todo          After PHP 5.3 has taken over 5.2.*, replace wrappers with __callStatic
 * @todo          Add methods for other log levels as needed
 */

class common_log
{
    public static function err($facility, $type = NULL, $desc = NULL)
    {
        self::write(LOG_ERR, $facility, $type, $desc);
    }

    public static function info($facility, $type = NULL, $desc = NULL)
    {
        self::write(LOG_INFO, $facility, $type, $desc);
    }

    public static function debug($facility, $type = NULL, $desc = NULL)
    {
        self::write(LOG_DEBUG, $facility, $type, $desc);
    }

    public static function auth($type, $desc = NULL, $userid = false)
    {
        if ($userid && !is_numeric($userid)) {
            $user = new Common_User;
            $userid = (int) $user->getUserIDByName($userid);
        }
        self::write(LOG_INFO, "auth", $type, $desc, $userid);
    }

    private static function write($level, $facility, $type = NULL, $desc = NULL, $userid = false)
    {
        if (!is_numeric($userid)) {
            $userid = isset($_SESSION["UserID"])?(int) $_SESSION["UserID"]:0;
        }
        $ip = $_SERVER["REMOTE_ADDR"];
        $time = time();
        try {
            $stmt = db()->prepare("INSERT INTO logs (userid,priority,facility,type,description,ip,created,updated) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind($userid);
            $stmt->bind($level);
            $stmt->bind($facility);
            $stmt->bind($type);
            $stmt->bind($desc);
            $stmt->bind($ip);
            $stmt->bind($time)->bind($time);
            $stmt->execute();
        } catch (Exception $e) {

        }
    }

}
