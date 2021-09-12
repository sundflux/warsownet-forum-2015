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
 * 2013 Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * The Initial Developer of the Original Code is
 * Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * Portions created by the Initial Developer are Copyright (C) 2013
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

class Security
{
    public static function requireSessionID()
    {
        if (!isset($_GET["session_id"])) {
            throw new Exception("Unable to locate security token.");
        }
        if ($_GET["session_id"] != session_id()) {
            throw new Exception("Unable to verify security token.");
        }
    }

    public static function verifyReferer()
    {
        // Note: referer can't be trusted.
        $referer = $_SERVER["HTTP_REFERER"];
        if (empty($referer)) {
            throw new Exception("Unable to verify origin.");
        }
        $request = new Controller_Request;
        $origin = $request->getBaseUri();
        $tmp = substr($origin, 0, strlen($origin));
        if ($tmp != $origin) {
            throw new Exception("Unable to verify origin.");
        }
    }

    public static function strip($text, $strip = true)
    {
        // Basics, this stuff ain't allowed
        $text = str_ireplace("$.", "", $text);
        $text = str_ireplace(" onerror", "", $text);
        $text = str_ireplace(" onclick", "", $text);
        $text = str_ireplace(" onmouse", "", $text);
        $text = str_ireplace(" onload", "", $text);
        $text = str_ireplace(" onexit", "", $text);
        $text = str_ireplace(" src", "", $text);
        $text = str_ireplace("javascript:", "", $text);

        return $text;
    }

}
