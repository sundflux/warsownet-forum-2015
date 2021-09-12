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
 * Forum module, access control
 * @package       Kernel
 * @subpackage    Forum
 */

class forum_acl
{
    /**
     * Add new accessgroup
     *
     * @access      public
     * @param string $name Accessgroup name
     */
    public static function add($name)
    {
        $query = "
            INSERT INTO forum_accessgroup (name)
            VALUES (?)";

        $stmt = db()->prepare($query);
        $stmt->set($name);
        try {
            $stmt->execute();
        } catch (Exception $e) {

        }
    }

    /**
     * Delete accessgroup
     *
     * @access      public
     * @param int $id forum_accessgroup_id
     */
    public static function delete($id)
    {
        if (!is_numeric($id)) {
            throw new Exception("Group not found");
        }

        // delete access group
        $query = "
            DELETE FROM forum_accessgroup
            WHERE id = ?";

        $stmt = db()->prepare($query);
        $stmt->set((int) $id);

        // delete access group from group preferences
        $query2 = "
            DELETE FROM forum_group_acl
            WHERE accessgroup_id = ?";

        $stmt2 = db()->prepare($query2);
        $stmt2->set((int) $id);

        // delete access group from user preferences
        $query3 = "
            DELETE FROM forum_profile_acl
            WHERE accessgroup_id = ?";

        $stmt3 = db()->prepare($query3);
        $stmt3->set((int) $id);

        try {
            $stmt->execute();
            $stmt2->execute();
            $stmt3->execute();
        } catch (Exception $e) {

        }
    }

    /**
     * Get all accessgroups
     *
     * @access      public
     * @return object
     */
    public static function getAccessGroups()
    {
        $query = "
            SELECT id, name
            FROM forum_accessgroup";

        $stmt = db()->prepare($query);
        try {
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (Exception $e) {

        }
    }

    /**
     * Get accessgroup info, to which forum groups this group has access to, etc.
     *
     * @access      public
     * @param  int    $id forum_accessgroup.id
     * @uses        Forum_Group
     * @return object
     */
     public static function getAccessGroupByID($id)
     {
        if (!is_numeric($id)) {
            throw new Exception("Group not found");
        }

        $retval = new stdClass;
        $allowed_ids = false;

        // Info about access group
        $query = "
            SELECT id, name
            FROM forum_accessgroup
            WHERE id = ?";

        $stmt = db()->prepare($query);
        $stmt->set((int) $id);
        try {
            $stmt->execute();
            $retval->info = $stmt->fetch();
        } catch (Exception $e) {

        }

        // Get all forums which this access group can access
        $query = "
            SELECT group_id
            FROM forum_group_acl
            WHERE accessgroup_id = ?";

        $stmt = db()->prepare($query);
        $stmt->set((int) $id);
        try {
            $stmt->execute();

            // all forums which this access group can access
            $access = $stmt->fetchAll();
            foreach ($access as $_group) {
                $allowed_ids[] = $_group->group_id;
            }
            unset($_group);

            // Get all forum groups
            $tmp = Forum_Group::getGroups();

            foreach ($tmp as $_group) {
                $groups[$_group->id] = new stdClass;
                $groups[$_group->id]->id = $_group->id;
                $groups[$_group->id]->name = $_group->name;
                $groups[$_group->id]->public = $_group->public;

                // Get access level
                if ($_group->public == 0) {
                    $groups[$_group->id]->access_level = self::getRights($id, $_group->id);
                }

                // This group has access here
                if (is_array($allowed_ids) && in_array($_group->id, $allowed_ids)) {
                    $groups[$_group->id]->access = true;
                }
            }

            if(isset($groups))
                $retval->access = $groups;

        } catch (exception $e) {

        }

        return $retval;
    }

    /**
     * Delete all permissions from access group
     *
     * @access      public
     * param        int $id forum_accessgroup.id
     */
    public static function clearAccessGroupByID($id)
    {
        if (!is_numeric($id)) {
            throw new Exception("Group not found");
        }

        $query = "
            DELETE FROM forum_group_acl
            WHERE accessgroup_id = ?";

        $stmt = db()->prepare($query);
        $stmt->set((int) $id);
        try {
            $stmt->execute();
        } catch (Exception $e) {

        }
    }

    /**
     * Delete all permissions from forum group
     *
     * @access      public
     * param        int $id forum_group.id
     */
    public static function clearAccessGroupByGroupID($id)
    {
        if (!is_numeric($id)) {
            throw new Exception("Group not found");
        }

        $query = "
            DELETE FROM forum_group_acl
            WHERE group_id = ?";

        $stmt = db()->prepare($query);
        $stmt->set((int) $id);
        try {
            $stmt->execute();
        } catch (Exception $e) {

        }
    }

    /**
     * Link forum group to access group
     *
     * @access      public
     * param        int $accessGroupID forum_accessgroup.id
     * param        int $forumGroupID forum_group.id
     */
    public static function addAccessToGroup($accessGroupID, $forumGroupID)
    {
        if (!is_numeric($accessGroupID) || !is_numeric($forumGroupID)) {
            throw new Exception("Group not found");
        }

        $query = "
            INSERT INTO forum_group_acl (group_id, accessgroup_id)
            VALUES (?,?)";

        $stmt = db()->prepare($query);
        $stmt->set((int) $forumGroupID);
        $stmt->set((int) $accessGroupID);
        try {
            $stmt->execute();
        } catch (Exception $e) {

        }
    }

    /**
     * Add default permissions for forum group
     *
     * @access      public
     * param        int $forumGroupID forum_group.id
     * param        int $chmod 4,6 or 7
     */
    public static function addDefaultPermissionToGroup($forumGroupID, $chmod)
    {
        self::addPermissionToGroup($forumGroupID, $forumGroupID, $chmod, true);
    }

    /**
     * Add access permission for given accessgroup to forum group
     *
     * @access      public
     * param        int $accessGroupID forum_accessgroup.id
     * param        int $forumGroupID forum_group.id
     * param        int $chmod 4,6 or 7
     * param        bool $default Default value or for access group?
     */
    public static function addPermissionToGroup($accessGroupID, $forumGroupID, $chmod, $default = false)
    {
        try {
            $acl = new Auth_ACL;
            if (!$default) {
                $acl->setModule("forum-group-chmod");
            } else {
                $acl->setModule("forum-group-chmod-default");
            }
            $acl->setBinding($accessGroupID);
            $acl->setGroupID($forumGroupID);
            $acl->setUserID("");
            $acl->setValue($chmod);
            $acl->commit();
        } catch (Exception $e) {

        }
    }

    /**
     * Returns if accessgroup has required for given forum group
     *
     * @access      public
     * param        int $accessGroupID forum_accessgroup.id
     * param        int $forumGroupID forum_group.id
     * param        int $chmod 4,6 or 7
     */
    public static function hasRights($accessGroupID, $forumGroupID, $expectedRights)
    {
        try {
            $acl = new Auth_ACL;
            $acl->setModule("forum-group-chmod");
            $acl->setBinding($accessGroupID);
            $acl->setGroupID($forumGroupID);
            $acl->setUserID("");
            $acl->setValue($chmod);

            return $acl->hasRights();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get default access value for given forum group
     *
     * @access      public
     * param        int $forumGroupID forum_group.id
     */
    public static function getDefaultRights($forumGroupID)
    {
        $query = "
            SELECT value
            FROM acl
            WHERE module = 'forum-group-chmod-default'
            AND groupid = ?";

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $forumGroupID);
            $stmt->execute();
            $val = $stmt->fetchColumn();

            return $val;
        } catch (Exception $e) {

        }
    }

    /**
     * Get access value for given access group to target forum group
     *
     * @access      public
     * param        int $accessGroupID forum_accessgroup.id
     * param        int $forumGroupID forum_group.id
     */
    public static function getRights($accessGroupID, $forumGroupID)
    {
        $query = "
            SELECT value
            FROM acl
            WHERE module = 'forum-group-chmod'
            AND binding = ?
            AND groupid = ?";

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $accessGroupID);
            $stmt->set((int) $forumGroupID);
            $stmt->execute();
            $val = $stmt->fetchColumn();

            return $val;
        } catch (Exception $e) {

        }
    }

    /**
     * Get permission value from session for target forum group
     *
     * @access      public
     * param        int $forumGroupID forum_group.id
     */
    public static function getPermissionsFromSession($group_id)
    {
        // Return user-specific permission
        if (isset($_SESSION["forum_permissions"][$group_id])) {
            return $_SESSION["forum_permissions"][$group_id];
        }

        // If that fails, return default permissions
        return self::getDefaultRights($group_id);
    }

    /**
     * Clear default permissions for target forum group.
     * (that's one damned long function name too =P)
     *
     * @access      public
     * param        int $forumGroupID forum_group.id
     */
    public static function clearDefaultPermissionsByForumGroupID($forumGroupID)
    {
        try {
            $query = "
                DELETE FROM acl
                WHERE groupid = ?
                AND module = 'forum-group-chmod-default'";

            $stmt = db()->prepare($query);
            $stmt->set((int) $forumGroupID);
            $stmt->execute();
        } catch (Exception $e) {

        }
    }

    /**
     * Clear permissions for given access group
     *
     * @access      public
     * param        int $accessGroupID forum_accessgroup.id
     */
    public static function clearPermissionsByAccessGroupID($accessGroupID)
    {
        try {
            $query = "
                DELETE FROM acl
                WHERE binding = ?
                AND module = 'forum-group-chmod'";

            $stmt = db()->prepare($query);
            $stmt->set((int) $accessGroupID);
            $stmt->execute();
        } catch (Exception $e) {

        }
    }

    /**
     * Check if user has permissions to access this post
     *
     * @access      public
     * param        int $accessGroupID forum_accessgroup.id
     */
    public static function canAccessPost($PostID, $UserID = false)
    {
        if (!$UserID) {
            $UserID = $_SESSION["UserID"];
        }

        // TODO
        return true;
    }

}
