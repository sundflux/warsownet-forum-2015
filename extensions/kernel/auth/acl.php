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
 * 2008 Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * The Initial Developer of the Original Code is
 * Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * Portions created by the Initial Developer are Copyright (C) 2008
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
 * Common ACL.
 *
 * Examples:
 *
 * Creating new access control list item:
 *
 * $acl=new Auth_ACL;
 * $acl->setBinding("news");
 * $acl->setvalue("10");
 * $acl->commit();
 *
 * Creating new access control with multiple list items for defined userid:
 *
 * $acl=new Auth_ACL;
 * $acl->setBinding("news");
 * $acl->setUserID("7");
 * $acl->setvalue(array("1","2","3","4"));
 * $acl->commit();
 *
 * Checking access control rights to list item:
 *
 * $acl=new Auth_ACL;
 * $acl->setBinding("news");
 * $acl->setvalue("10");
 * try {
 * 	if (!$acl->hasRights()) {
 *   throw new Exception("Access denied");
 *  }
 * } catch (Exception $e) {
 * 	...
 * }
 *
 * @package       Kernel
 * @subpackage    Auth
 * @uses          Controller_Request
 */

class auth_acl
{
    private $obj;

    public function __construct()
    {
        $this->obj = new stdClass;
        $this->obj->module = Controller_Request::getInstance()->getModule(false);
        $this->obj->binding = "";
        $this->obj->value = "";
        if (isset($_SESSION["UserID"])) {
            $this->obj->userid = $_SESSION["UserID"];
        } else {
            $this->obj->userid = "";
        }
        $this->obj->groupid = "";
    }

    /**
     * Set target module for ACL entry
     * @param string $val Module
     */
    public function setModule($val)
    {
        $this->obj->module = $val;
    }

    /**
     * Set target binding for ACL entry, for example "news" or "blogentry"
     * @param string $val Binding
     */
    public function setBinding($val)
    {
        $this->obj->binding = $val;
    }

    /**
     * Set value for ACL entry
     * @param string $val ACL
     */
    public function setValue($val)
    {
        $this->obj->value = $val;
    }

    /**
     * Sets UserID for ACL entry
     * @param int $val UserID
     */
    public function setUserID($val)
    {
        $this->obj->userid = $val;
    }

    /**
     * Sets GroupID for ACL entry
     * @param int $val UserID
     */
    public function setGroupID($val)
    {
        $this->obj->groupid = $val;
    }

    /**
     * Save ACL entry to DB
     */
    public function commit()
    {
        if (empty($this->obj->module)) {
            throw new Exception("Module must be set.");
        }
        if (empty($this->obj->binding)) {
            throw new Exception("Binding must be set.");
        }
        if (empty($this->obj->value)) {
            throw new Exception("Value must be set.");
        }
        if (empty($this->obj->userid) && empty($this->obj->groupid)) {
            throw new Exception("Either UserID or GroupID must be set.");
        }

        $query = "
            INSERT INTO acl (module,binding,value,userid,groupid,updated,created)
            values (?,?,?,?,?,?,?)";

        $stmt = db()->prepare($query);
        $time = time();
        if (is_array($this->obj->value)) {
            // Insert multiple values if value was array
            foreach ($this->obj->value as $k => $v) {
                $stmt->bind($this->obj->module);
                $stmt->bind($this->obj->binding);
                $stmt->bind($v);
                $stmt->set($this->obj->userid ? $this->obj->userid : NULL);
                $stmt->set($this->obj->groupid ? $this->obj->groupid : NULL);
                $stmt->bind($time);
                $stmt->bind($time);
                $stmt->execute();
            }
        } else {
            // Single row
            $stmt->bind($this->obj->module);
            $stmt->bind($this->obj->binding);
            $stmt->bind($this->obj->value);
            $stmt->set($this->obj->userid ? $this->obj->userid : NULL);
            $stmt->set($this->obj->groupid ? $this->obj->groupid : NULL);
            $stmt->bind($time);
            $stmt->bind($time);
            $stmt->execute();
        }
    }

    /**
     * Check rights for given values
     */
    public function hasRights()
    {
        if ((!empty($this->obj->userid) || !empty($this->obj->groupid) && !empty($this->obj->binding) && !empty($this->obj->value))) {
            if (is_array($this->obj->value)) {
                throw new Exception("Array can be used only when inserting ACL data.");
            }
            if (empty($this->obj->groupid)) {
                $this->obj->groupid = implode(",",$_SESSION["Groups"]);
            }

            $query = "
                SELECT COUNT(*)
                FROM acl
                WHERE module=? AND binding=? AND value=? AND (userid=? or groupid=?)"

            $stmt = db()->prepare($query);
            $stmt->bind($this->obj->module);
            $stmt->bind($this->obj->binding);
            $stmt->bind($this->obj->value);
            $stmt->bind($this->obj->userid);
            $stmt->bind($this->obj->groupid);
            $stmt->execute();

            return ($stmt->fetchColumn>0);
        } else {
            throw new Exception("Insufficient data, need userid or groupid with binding and value.");
        }
    }

}
