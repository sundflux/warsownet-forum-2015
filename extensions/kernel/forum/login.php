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
 * Login actions
 * @package       Forum
 */

class forum_login
{
    /**
     * Check if user or host is banned.
     *
     * @access      public
     */
    public static function checkBan()
    {
        if (!is_numeric($_SESSION["UserID"])) {
            throw new Exception("UserID is required.");
        }

        // Check if user is banned
        try {
            $query = "
                SELECT banned
                FROM forum_profile
                WHERE user_id=?";

            $stmt = db()->prepare($query);
            $stmt->set($_SESSION["UserID"]);
            $stmt->execute();
            $row = $stmt->fetch();
        } catch (Exception $e) {

        }

        if (isset($row) && $row->banned == 1) {
            throw new Exception("This account is banned.");
        }
    }

    /**
     * Check if user meets the 'verified' status for extra features.
     * Configured with FORUM_VERIFIED_ACCOUNT_AGE and FORUM_VERIFIED_ACCOUNT_POSTS
     *
     * @access      public
     */
    public static function checkVerified()
    {
        if (!is_numeric($_SESSION["UserID"])) {
            throw new Exception("UserID is required.");
        }

        // Check if user is banned
        try {
            $query = "
                SELECT UNIX_TIMESTAMP(joined) as joined, posts
                FROM forum_profile
                WHERE user_id = ?";

            $stmt = db()->prepare($query);
            $stmt->set($_SESSION["UserID"]);
            $stmt->execute();
            $row = $stmt->fetch();
        } catch (Exception $e) {

        }

        // User without profile, non-forum user.
        if (!isset($row)) {
            return false;
        }

        // should not exist, it's a hack for warsow mm stuff :/
        if (class_exists('WMM')) {
            $tmp = WMM::getMMIDByUserID($_SESSION["UserID"]);
            if ($tmp && is_numeric($tmp)) {
                $_SESSION["verified"] = 1;

                return true;
            }
        }

        // This is the age we expect
        $expectedDays = (60 * 60 * 24) * FORUM_VERIFIED_ACCOUNT_AGE;
        $age = $row->joined;
        $expectedAge = time() - $expectedDays;

        // Verified account if enough posts and old enough
        if (($age <= $expectedAge) && $row->posts >= FORUM_VERIFIED_ACCOUNT_POSTS) {
            $_SESSION["verified"] = 1;
        }
    }

    /**
     * Load users access groups to session. Should be called at login or after
     * updating permissions.
     *
     * @access      public
     */
    public static function loadAccessGroups()
    {
        if (!is_numeric($_SESSION["UserID"])) {
            throw new Exception("UserID is required.");
        }

        // Load admin/moderator info
        $_SESSION["admin"] = $_SESSION["moderator"] = 0;
        try {
            $query = "
                SELECT admin,moderator
                FROM forum_profile
                WHERE user_id=?";

            $stmt = db()->prepare($query);
            $stmt->set($_SESSION["UserID"]);
            $stmt->execute();
            $row = $stmt->fetch();
            $_SESSION["admin"] = $row->admin;
            $_SESSION["moderator"] = $row->moderator;
        } catch (Exception $e) {

        }

        $_SESSION["forum_allowed_groups"] = false;

        // Get access groups for profile
        $query="
            SELECT accessgroup_id
            FROM forum_profile_acl, forum_profile
            WHERE forum_profile_acl.profile_id=forum_profile.id
                AND forum_profile.user_id = ? ";

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $_SESSION["UserID"]);
            $stmt->execute();
            foreach ($stmt as $row) {
                if (!isset($accessgroups)) {
                    $accessgroups = array();
                }

                if (is_numeric($row->accessgroup_id)) {
                    array_push($accessgroups, $row->accessgroup_id);
                }
            }
        } catch (Exception $e) {
            throw new Exception("1: Loading access groups failed.");
        }

        // No accessgroups, skip
        if (!isset($accessgroups)) {
            return;
        }

        // Get forum groups for the access group
        $query="
            SELECT group_id
            FROM forum_group_acl
            WHERE accessgroup_id IN (".implode(",",$accessgroups).")";

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $_SESSION["UserID"]);
            $stmt->execute();
            foreach ($stmt as $row) {
                if (!$_SESSION["forum_allowed_groups"]) {
                    $_SESSION["forum_allowed_groups"] = array();
                }

                if (is_numeric($row->group_id)) {
                    array_push($_SESSION["forum_allowed_groups"], $row->group_id);
                }
            }
        } catch (Exception $e) {
            throw new Exception("2: Loading access groups failed.");
        }

        // Get permissions for groups
        if (!isset($_SESSION["forum_permissions"]) || !$_SESSION["forum_permissions"]) {
            $_SESSION["forum_permissions"] = array();
        }

        // $_SESSION["forum_permissions"][$k] = $v
        // $k = forum group id, $v = permission level.

        // Forums that require verified accounts
        $requireVerified = explode(",", FORUM_REQUIRE_VERIFIED_IDS);

        foreach ($_SESSION["forum_allowed_groups"] as $k=>$v) {
            foreach ($accessgroups as $_tmp => $_accessgroup_id) {
                $perm = Forum_ACL::getRights($_accessgroup_id, $v);

                // Needs verified account, revert back to read-only
                if (isset($requireVerified) && is_array($requireVerified) && in_array($v, $requireVerified) && !isset($_SESSION["verified"])) {
                    $perm = 4;
                }

                // Set permissions
                if (is_numeric($perm)) {
                    if (isset($_SESSION["forum_permissions"][$v]) && ($_SESSION["forum_permissions"][$v] < $perm) ) {
                        // Update only if this permission level was higher than existing
                        $_SESSION["forum_permissions"][$v] = $perm;
                    } elseif (!isset($_SESSION["forum_permissions"][$v])) {
                        // None found, so add this
                        $_SESSION["forum_permissions"][$v] = $perm;
                    }
                }

            }//foreach
        }//foreach

    }

}
