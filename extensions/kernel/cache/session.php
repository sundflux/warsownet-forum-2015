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
 * 2005,2007 Tarmo Alexander Sundström <ta@sundstrom.im>
 * 2005 Joni Halme <jontsa@angelinecms.info>
 *
 * The Initial Developer of the Original Code is
 * Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * Portions created by the Initial Developer are Copyright (C) 2005
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

class cache_session implements Cache_IFace
{
    private $target;

    public function __construct($target)
    {
        $this->target = $target;
        if (!isset($_SESSION["cache"])) {
            $_SESSION["cache"] = array();
        }
    }

    public function setTarget($value)
    {
        $this->target = $value;
    }

    public function update($name, $data, $checksum)
    {
        $_SESSION["cache"][$this->target][$name] = new stdClass;
        $_SESSION["cache"][$this->target][$name]->checksum = $checksum;
        $_SESSION["cache"][$this->target][$name]->data = $data;
    }

    public function read($name)
    {
        if (isset($_SESSION["cache"][$this->target][$name])) {
            return $_SESSION["cache"][$this->target][$name]->data;
        } else {
            return false;
        }
    }

    public function compare($name, $checksum)
    {
        if (isset($_SESSION["cache"][$this->target][$name]) && $_SESSION["cache"][$this->target][$name]->checksum == $checksum) {
            return true;
        } else {
            return false;
        }
    }

    public function clear($name = false)
    {
        if (!$name) {
            unset($_SESSION["cache"][$this->target]);
        } else {
            unset($_SESSION["cache"][$this->target][$name]);
        }
    }

}
