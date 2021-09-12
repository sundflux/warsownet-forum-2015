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
 * 2004,2005,2006 Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * The Initial Developer of the Original Code is
 * Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * Portions created by the Initial Developer are Copyright (C) 2004,2005,2006
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * 2008 Joni Halme <jontsa@angelinecms.info>
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
 * Validation library.
 *
 * Methods for different kind of input validation.
 *
 * @package       Kernel
 * @subpackage    Common
 */

class common_validate
{
    /**
     * Checks if strings is valid as XML node name.
     *
     * @access public
     * @param  string $node Node name
     * @return bool   True if string can be used as node name, otherwise false.
     */
    public static function XMLElementName($node)
    {
        return !(empty($node) || is_numeric($node[0]) || strtolower(substr($node, 0, 3))==="xml" || strstr($node, " "));
    }

    /**
     * Validates email address.
     *
     * @access      public
     * @param  string  $string Claimed email address string
     * @return boolean true if string is valid email address. Note that does not validate if email exists, only if it has valid format
     */
    public static function email($string)
    {
        $string = trim($string);

        return (bool) preg_match("/^(?=.{5,254})(?:(?:\"[^\"]{1,62}\")|(?:(?!\.)(?!.*\.[.@])[a-z0-9!#$%&'*+\/=?^_`{|}~^.-]{1,64}))@(?:(?:\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])\])|(?:(?!-)(?!.*-\$)(?!.*-\.)(?!.*\.-)(?!.*[^n]--)(?!.*[^x]n--)(?!n--)(?!.*[^.]xn--)(?:[a-z0-9-]{1,63}\.){1,127}(?:[a-z0-9-]{1,63})))$/i",$string);
    }

    /**
     * Validates wether or not user exists.
     *
     * @access      public
     * @param  string $username Username
     * @return True   if user exists, false if user does not exist
     */
    public static function user($user)
    {
        try {
            $stmt = db()->prepare("SELECT COUNT(*) FROM users WHERE username=?");
            $stmt->bind($user)->execute();
        } catch (Exception $e) {
            return false;
        }

        return ($stmt->fetchColumn() == 1);
    }

    /**
     * Validates wether or not group exists.
     *
     * @access      public
     * @param  string $groupname Groupname
     * @return True   if group exists, false if group does not exist
     */
    public static function group($group)
    {
        try {
            $stmt = db()->prepare("SELECT COUNT(*) FROM groups WHERE name=?");
            $stmt->bind($group)->execute();
        } catch (Exception $e) {
            return false;
        }

        return ($stmt->fetchColumn() == 1);
    }

    /**
     * Validates that string contains only a-z 0-9 characters
     *
     * @access      public
     * @param  string  $value String
     * @return boolean True if string contains only a-z0-9 us characters. False if not
     */
    public static function containsUSChars($value)
    {
        $value = strtolower(trim($value));

        return (bool) preg_match("/^[a-z0-9]+$/", $value);
    }

    /**
     * Validates a hostname or fqdn. Invalid, empty or hostnames longer than 256 characters return false.
     * @param  string  $value      Hostname to validate
     * @param  boolean $allowlocal Allow localhosts
     * @return boolean
     */
    public static function hostname($value, $allowlocal = false)
    {
        $value = strtolower(trim($value));
        if ($allowlocal && $value == "localhost") {
            return true;
        }

        if (empty($value) || strlen($value) > 256 || strstr($value, "..")) {
            return false;
        }

        return (bool) preg_match("/^[a-zöäå0-9\.\-]+\.[a-z]{2,8}$/", $value);
    }

    /**
     * Validates URI. Empty or invalid URIs return false.
     * Valid URI is prefix://hostname[:port][?|/[path or parameters]]
     * @param  string  $value    URI to validate
     * @param  mixed   $prefixes Optional string or array of prefixes that are allowed. Defaults to http and https.
     * @return boolean
     */
    public static function uri($value, $prefixes = false)
    {
        $value = strtolower(trim($value));
        if (empty($value)) {
            return false;
        }

        if (!$prefixes) {
            $prefixes = array("http", "https");
        } elseif (!is_array($prefixes)) {
            $prefixes = array($prefixes);
        }

        return (bool) preg_match("/^[".implode(":\/\/|", $prefixes).":\/\/]{1,}[a-zöäå0-9\.\-]+\.[a-z]{2,8}[\:\d{1,6}]?[\/|\?]*/", $value);
    }

}
