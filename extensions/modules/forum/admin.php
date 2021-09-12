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
 * Administration module
 * @package       Module
 * @subpackage    Login
 */

class Module_Forum_Admin extends Main
{
    private $moderator, $admin;

    public function __construct()
    {
        $this->ui->addJS("forum/global");
        $this->ui->addJS("forum/jquery.waitforimages");
        $this->ui->addJS("forum/jquery.ui.sortable");
        $this->ui->addJS("forum/jquery.simplemodal");
        $this->ui->addCSS("modal");

        Forum::init();

        // Check for permissions
        if (!isset($_SESSION["admin"]) && !isset($_SESSION["moderator"])) {
            throw new Exception("Only administrators and moderators may perform this task.");
        }
        if ($_SESSION["admin"] == 0 && $_SESSION["moderator"] == 0) {
            throw new Exception("Only administrators and moderators may perform this task.");
        }

        if (isset($_SESSION["admin"])) {
            $this->admin = $_SESSION["admin"];
        }

        if (isset($_SESSION["moderator"])) {
            $this->moderator = $_SESSION["moderator"];
        }
    }

    /* Index for forum/thread admin */

    public function index()
    {
        $this->view->topic_id = $this->request->getParam(0);
        $this->view->forum_id = $this->request->getParam(1);
        $this->view->post_id = $this->request->getParam(2);
        $this->view->page = $this->request->getParam(3);

        // Get forums if we got topic, for move dropdown etc)
        if (is_numeric($this->view->topic_id)) {
            $this->view->groups = Forum::getForums();
            $this->view->topic = Forum_Topic::getTopicInfo($this->view->topic_id);
        }
    }

    /* Administrative actions */

    public function actions()
    {
    }

    /* Users */

    public function users()
    {
        $this->ui->addXSL("pagination");

        $page = $this->request->getParam(0);
        $this->view->s = false;
        if (isset($_GET["s"])) {
            $this->view->s = $_GET["s"];
        }

        $this->view->users = Forum_User::getUsers($page, $this->view->s);
        $this->view->users->url = "forum_admin/users/";

        $controller = new Controller_Request;
        $_SESSION["__referer"] = $controller->getUri();
    }

    /* Forum group handling */

    public function forumactions()
    {
        $this->view->forum = Forum_Forum::getForumInfo($this->request->getParam(0));
    }

    public function groupactions()
    {
        $this->view->group = Forum_Group::getGroupInfo($this->request->getParam(0));
    }

    public function savegroup()
    {
        Security::requireSessionID();
        Security::verifyReferer();

        $groupID = $this->request->getParam(0);

        $db = new DB_Row("forum_group");
        $db->byID($groupID);
        $db->public = $_POST["public"];

        try {
            $db->save();

            Forum_ACL::clearAccessGroupByGroupID($groupID);
            if (isset($_POST["forum_group"])) {
                foreach ($_POST["forum_group"] as $k => $v) {
                    Forum_ACL::addAccessToGroup($v, $groupID);
                }
            }

            // Add default permissions
            if (isset($_POST["forum_access"]) && is_numeric($_POST["forum_access"])) {
                Forum_ACL::clearDefaultPermissionsByForumGroupID($groupID);
                Forum_ACL::addDefaultPermissionToGroup($groupID, $_POST["forum_access"]);
            }

        } catch (Exception $e) {

        }

        $this->ui->addMessage("Saved");
        Controller_Redirect::to("forum_admin/forums");
    }

    public function forums()
    {
//		$this->ui->addTab("Forums", "forum_admin/forums");
//		$this->ui->addTab("Reorder forums", "forum_admin/forumgroups");

        $this->view->groups = Forum_Group::getGroups();
        foreach ($this->view->groups as $group) {
            $group->forums = Forum_Forum::getForums($group->id);
            $tmp[] = $group;
        }
        if (isset($tmp)) {
            $this->view->groups = $tmp;
        }
    }

    public function forumgroups()
    {
//		$this->ui->addTab("Forums", "forum_admin/forums");
//		$this->ui->addTab("Reorder forums", "forum_admin/forumgroups");

        // Save reordering
        if (isset($_POST["save"]) && isset($_POST["order"])) {
            // Update group order
            foreach ($_POST["order"] as $v => $group_id) {
                Forum_Group::updateOrder($group_id, $v);
            }
            // Update forum order
            foreach ($_POST["forum-order"] as $v => $forum_id) {
                Forum_Forum::updateOrder($forum_id, $v);
            }

            $this->ui->addMessage("Saved");
            Controller_Redirect::to("forum_admin/forumgroups");
        }

        $this->view->groups = Forum_Group::getGroups();
        foreach ($this->view->groups as $group) {
            $group->forums = Forum_Forum::getForums($group->id);
            $tmp[] = $group;
        }
        if (isset($tmp)) {
            $this->view->groups = $tmp;
        }
    }

    public function hideforum($val = 0)
    {
        Security::requireSessionID();
        Security::verifyReferer();

        $db = new DB_Row("forum_forum");
        $db->byID($this->request->getParam(0));
        $db->visible = $val;
        $db->save();

        $this->ui->addMessage("Saved");
        Controller_Redirect::to("forum_admin/forums");
    }

    public function showforum()
    {
        $this->hideforum(1);
    }

    public function deleteforum()
    {
        Security::requireSessionID();
        Security::verifyReferer();
        // not implemented

        $this->ui->addMessage("Deleted");
        Controller_Redirect::to("forum_admin/forums");
    }

    /* Access group handling */

    public function access()
    {
        $groups = Forum_ACL::getAccessGroups();
        foreach ($groups as $group) {
            $this->view->groups[] = $group;
        }

        // preload given group
        if (isset($_GET["p"])) {
            $this->view->preload = $_GET["p"];
        }
    }

    public function accessgroup()
    {
        $accessgroupID = $this->request->getParam(0);
        if (!is_numeric($accessgroupID)) {
            throw new Exception("Accessgroup not found");
        }
        // get access group info
        $this->view->group = Forum_ACL::getAccessGroupByID($accessgroupID);
    }

    public function saveaccessgroup()
    {
        Security::requireSessionID();
        Security::verifyReferer();

        $accessgroupID = $this->request->getParam(0);
        if (!is_numeric($accessgroupID)) {
            throw new Exception("Accessgroup not found");
        }

        try {
            Forum_ACL::clearAccessGroupByID($accessgroupID);
            if (isset($_POST["forum_group"])) {
                foreach ($_POST["forum_group"] as $k => $v) {
                    Forum_ACL::addAccessToGroup($accessgroupID, $v);
                }
            }
            // add fine-graned permissions for access group
            Forum_ACL::clearPermissionsByAccessGroupID($accessgroupID);
            if (isset($_POST["forum_access"])) {
                foreach ($_POST["forum_access"] as $k => $v) {
                    Forum_ACL::addPermissionToGroup($accessgroupID, $k, $v);
                }
            }
        } catch (Exception $e) {

        }

        $this->ui->addMessage("Saved");
        Controller_Redirect::to("forum_admin/access?p={$accessgroupID}");
    }

    public function addgroup()
    {
        Security::requireSessionID();
        Security::verifyReferer();

        if (isset($_POST["groupname"])) {
            Forum_ACL::add($_POST["groupname"]);
        }

        $this->ui->addMessage("Added");
        Controller_Redirect::to("forum_admin/access");
    }

    public function deletegroup()
    {
        Security::requireSessionID();
        Security::verifyReferer();

        if (is_numeric($this->request->getParam(0)) && $this->admin == 1) {
            Forum_ACL::delete($this->request->getParam(0));
        }

        $this->ui->addMessage("Deleted");
        Controller_Redirect::to("forum_admin/access");
    }

    /* Topic actions */

    public function sticky()
    {
        Security::requireSessionID();
        Security::verifyReferer();

        if (is_numeric($this->request->getParam(0))) {
            Forum_Topic::sticky($this->request->getParam(0));
        }

        $forumID = $this->request->getParam(1);
        $this->ui->addMessage("Topic stickied");
        Controller_Redirect::to("forum/".$forumID);
    }

    public function unsticky()
    {
        Security::requireSessionID();
        Security::verifyReferer();

        if (is_numeric($this->request->getParam(0))) {
            Forum_Topic::unsticky($this->request->getParam(0));
        }

        $forumID = $this->request->getParam(1);
        $this->ui->addMessage("Topic unstickied");
        Controller_Redirect::to("forum/".$forumID);
    }

    public function opentopic()
    {
        Security::requireSessionID();
        Security::verifyReferer();

        if (is_numeric($this->request->getParam(0))) {
            Forum_Topic::opentopic($this->request->getParam(0));
        }

        $forumID = $this->request->getParam(1);
        $this->ui->addMessage("Topic opened");
        Controller_Redirect::to("forum/".$forumID);
    }

    public function closetopic()
    {
        Security::requireSessionID();
        Security::verifyReferer();

        if (is_numeric($this->request->getParam(0))) {
            Forum_Topic::closetopic($this->request->getParam(0));
        }

        $forumID = $this->request->getParam(1);
        $this->ui->addMessage("Topic closed");
        Controller_Redirect::to("forum/".$forumID);
    }

    /* Users */

    /* Ban user */

    public function setBan($ban = 1)
    {
        Security::requireSessionID();
        Security::verifyReferer();

        // Allowed for both admins and moderators
        $profile_id = $this->request->getParam(0);
        $user_id = $this->request->getParam(1);
        if (!is_numeric($profile_id) || !is_numeric($user_id)) {
            throw new Validation_Exception("Malformed profile id.");
        }

        $query = "UPDATE forum_profile SET banned = ? WHERE id = ? AND user_id = ?";
        try {
            $stmt = db()->prepare($query);
            $stmt->set($ban);
            $stmt->set($profile_id);
            $stmt->set($user_id);
            $stmt->execute();
            if ($ban == 1) {
                $this->ui->addMessage("User banned.");
            }

            if ($ban == 0) {
                $this->ui->addMessage("Ban removed.");
            }

        } catch (Exception $e) {
            $this->ui->addError("Changing user ban status failed.");
        }

        // We wanna get back to somewhere else
        if (isset($_SESSION["__referer"])) {
            $tmp = $_SESSION["__referer"];
            unset($_SESSION["__referer"]);
            Controller_Redirect::to($tmp, true);
        }
        Controller_Redirect::to("forum_profile/{$user_id}");
    }

    /* Remove ban */

    public function removeBan()
    {
        // Allowed for both admins and moderators
        $this->setBan(0);
    }

    /* Spam handling */

    public function topicAsSpam()
    {
        Security::requireSessionID();
        Security::verifyReferer();

        Forum_Spam::topicAsSpam($this->request->getParam(0));
        $this->ui->addMessage("Topic marked as spam.");
        Controller_Redirect::to("forum/".$this->request->getParam(1)."/".$this->request->getParam(2));
    }

    public function postAsSpam()
    {
        Security::requireSessionID();
        Security::verifyReferer();

        Forum_Spam::postAsSpam($this->request->getParam(0));
        $this->ui->addMessage("Post marked as spam.");
        Controller_Redirect::to("forum/thread/".$this->request->getParam(1)."/".$this->request->getParam(2));
    }

    public function topicAsSpamWithBan()
    {
        Security::requireSessionID();
        Security::verifyReferer();

        Forum_Spam::topicAsSpamWithBan($this->request->getParam(0));
        $this->ui->addMessage("Topic marked as spam.");
        Controller_Redirect::to("forum/".$this->request->getParam(1)."/".$this->request->getParam(2));
    }

    public function postAsSpamWithBan()
    {
        Security::requireSessionID();
        Security::verifyReferer();

        Forum_Spam::postAsSpamWithBan($this->request->getParam(0));
        $this->ui->addMessage("Post marked as spam.");
        Controller_Redirect::to("forum/thread/".$this->request->getParam(1)."/".$this->request->getParam(2));
    }

    /* Move topic */

    public function movetopic()
    {
        Security::requireSessionID();
        Security::verifyReferer();

        if (!is_numeric($this->request->getParam(0))) {
            throw new Exception("Data missing");
        }
        if (!is_numeric($_POST["moveto"])) {
            throw new Exception("Data missing");
        }
        Forum_Topic::move($this->request->getParam(0), $_POST["moveto"]);
        $this->ui->addMessage("Topic moved.");
        Controller_Redirect::to("forum/thread/".$this->request->getParam(0)."/1");
    }

    /* Rename topic */

    public function renametopic()
    {
        // TODO: make 'isowner' for editing titles for your own threads

        Security::requireSessionID();
        Security::verifyReferer();

        if (isset($_POST["topic_name"]) && !empty($_POST["topic_name"]) && ($this->moderator == 1 || $this->admin == 1)) {
            Forum_Topic::rename($this->request->getParam(0), $_POST["topic_name"]);
            $this->ui->addMessage("Topic renamed.");
        }
        Controller_Redirect::to("forum/thread/".$this->request->getParam(0));
    }

    /* Rename forum */

    public function renameforum()
    {
        Security::requireSessionID();
        Security::verifyReferer();

        if (isset($_POST["forum_name"]) && !empty($_POST["forum_name"]) && $this->admin == 1) {
            Forum_Forum::rename($this->request->getParam(0), $_POST["forum_name"]);
            $this->ui->addMessage("Forum renamed.");
        }
        Controller_Redirect::to("forum/".$this->request->getParam(0));
    }

    /* Rename group */

    public function renamegroup()
    {
        Security::requireSessionID();
        Security::verifyReferer();

        if (isset($_POST["group_name"]) && !empty($_POST["group_name"]) && $this->admin == 1) {
            Forum_Group::rename($this->request->getParam(0), $_POST["group_name"]);
            $this->ui->addMessage("Group renamed.");
        }
        Controller_Redirect::to("forum");
    }

    /* Add forum/group */

    public function addforumtogroup()
    {
        Security::requireSessionID();
        Security::verifyReferer();

        $this->view->group_id = $this->request->getParam(0);
        if (isset($_POST["forum_name"])) {
            Forum_Forum::add(trim($_POST["forum_name"]), $this->request->getParam(0));
            $this->ui->addMessage("New forum added.");
            Controller_Redirect::to("forum_admin/forums");
        }
    }

    public function addforumgroup()
    {
        Security::requireSessionID();
        Security::verifyReferer();

        if (isset($_POST["group_name"])) {
            Forum_Group::add(trim($_POST["group_name"]));
            $this->ui->addMessage("New forum group added.");
            Controller_Redirect::to("forum_admin/forums");
        }
    }

}
