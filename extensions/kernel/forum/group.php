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
 * Forum module, forum groups
 * @package       Kernel
 * @subpackage    Forum
 */

class forum_group
{
    /**
     * Add new forum group
     *
     * @access      public
     * @param string $name   Group name
     * @param int    $public 0 or 1
     * @param int    $order
     */
    public static function add($title, $public = 1, $order = 0)
    {
        if (empty($title) || !is_numeric($public) || !is_numeric($order)) {
            throw new Exception("Malformed data");
        }

        $query = "
            INSERT INTO forum_group (`name`,`order`,`public`)
            VALUES (?,?,?)";

        try {
            $stmt = db()->prepare($query);
            $stmt->set($title);
            $stmt->set((int) $order);
            $stmt->set((int) $public);
            $stmt->execute();
        } catch (Exception $e) {

        }
    }

    /**
     * Rename forum
     *
     * @access      public
     * @param int    $group_id GroupID
     * @param string $title    New title
     */
    public static function rename($group_id, $title)
    {
        if (empty($title) || !is_numeric($group_id)) {
            throw new Exception("Title and GroupID required");
        }

        $query = "
            UPDATE forum_group
            SET name = ?
            WHERE id = ?";

        $stmt = db()->prepare($query);
        $stmt->set($title);
        $stmt->set((int) $group_id);
        $stmt->execute();
    }

    /**
     * Get all forum groups, return as object
     *
     * @access      public
     * @return object
     */
    public static function getGroups()
    {
        $query = "
            SELECT id, name, public, `order`
            FROM forum_group
            ORDER BY `order` ASC";

        $stmt = db()->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Update order number of the item
     *
     * @access      public
     * @param int $forum_id
     * @param int $order
     */
    public static function updateOrder($group_id, $order)
    {
        $query = "
            UPDATE forum_group
            SET `order` = ?
            WHERE id = ?";

        $stmt = db()->prepare($query);
        $stmt->set((int) $order);
        $stmt->set((int) $group_id);
        try {
            $stmt->execute();
        } catch (Exception $e) {

        }
    }

    /**
     * Get info about the group
     *
     * @access      public
     * @param  int    $group_id
     * @uses        Forum_ACL
     * @return object
     */
    public static function getGroupInfo($group_id)
    {
        $retval = new stdClass;

        // get forum group info
        $query = "
            SELECT id, name, `order`, public
            FROM forum_group
            WHERE id = ?";

        $stmt = db()->prepare($query);
        $stmt->set((int) $group_id);
        try {
            $stmt->execute();
            $retval->group = $stmt->fetch();
        } catch (Exception $e) {

        }

        // check which accessgroups have access to this group
        $query = "
            SELECT forum_accessgroup.id as accessgroup_id
            FROM forum_accessgroup,forum_group_acl
            WHERE forum_group_acl.accessgroup_id = forum_accessgroup.id
            AND forum_group_acl.group_id = ? ";

        $stmt = db()->prepare($query);
        $stmt->set((int) $group_id);
        try {
            $stmt->execute();
            $accessTo = $stmt->fetchAll();
            foreach ($accessTo as $_tmp) {
                $ids[] = $_tmp->accessgroup_id;
            }
            unset($_tmp);

            // get all accessgroups, merge the access information to the object
            $accessgroups = Forum_ACL::getAccessGroups();
            foreach ($accessgroups as $group) {
                if (isset($ids) && in_array($group->id, $ids)) {
                    $group->access = true;
                }
                $_tmp[] = $group;
            }

            $retval->access_level = Forum_ACL::getDefaultRights($group_id);

            if (isset($_tmp)) {
                $retval->groups = $_tmp;
            }
        } catch (Exception $e) {

        }

        return $retval;
    }

}
