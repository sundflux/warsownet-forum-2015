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
 * View/edit profile
 *
 * @package       Module
 * @subpackage    Forum
 */

class Module_Forum_Profile extends Main
{
    public function __construct()
    {
        $this->ui->addJS("forum/forum");
        $this->ui->addJS("forum/global");
        $this->ui->addJS("forum/jquery.simplemodal");
        $this->ui->addJS("forum/jquery.waitforimages");
        $this->ui->addCSS("modal");

        Forum::init();
        $this->view->forum = new stdClass;
        $this->view->forum->sitetitle = SITETITLE;

        $this->view->unreadCount = Forum_Unread::getUnreadCount();
        $this->view->unreadPMCount = Forum_Unread::getUnreadPrivateMessageCount();
        $this->view->forumNavi = true;
        if (isset($_SESSION["moderator"])) {
            $this->view->moderator = $_SESSION["moderator"];
        }

        if (isset($_SESSION["admin"])) {
            $this->view->admin = $_SESSION["admin"];
        }
    }

    public function index()
    {
        $this->view->avatarurl = AVATARURL;
        if (!is_numeric($this->request->getParam(0)) && !isset($_SESSION["UserID"])) {
            throw new Common_Exception(i18n("Profile not found."));
        }

        // note: we update profile by _userid_
        $profile = $this->request->getParam(0);
        if (!is_numeric($this->request->getParam(0)) && isset($_SESSION["UserID"])) {
            $profile = $_SESSION["UserID"];
        }

        $this->view->user_id = $profile;

        // whole custom profile thing is ugly
        // and should not exist, it's a hack for warsow mm stuff :/
        if (FORUM_CUSTOM_PROFILE_REDIRECT && !$this->request->isAjax()) {
            $tmp = WMM::getMMIDByUserID($this->view->user_id);
            // Only redirect if user has matchmaker profile
            if ($tmp && is_numeric($tmp)) {
                Controller_Redirect::to(FORUM_CUSTOM_PROFILE_REDIRECT."/".$this->request->getParam(0));
            }
        }
        if (FORUM_CUSTOM_PROFILE_REDIRECT) {
            $this->view->customProfile = FORUM_CUSTOM_PROFILE_REDIRECT;
        }

        // show edit function for own profile and for admins
        if ((isset($_SESSION["UserID"]) && $profile == $_SESSION["UserID"]) || $this->view->admin == 1) {
            $this->view->showEdit = true;
            if ($profile == $_SESSION["UserID"]) {
                $this->view->isOwner = true;
            }
        }
        foreach (Forum_User::getProfile($profile, false, true) as $p) {
            $this->view->profile = $p;
            $this->view->default_title = FORUM_DEFAULT_TITLE;
        }

        if (isset($this->view->profile->username)) {
            $this->view->forum->sitetitle = SITETITLE. " - ".$this->view->profile->username."'s profile";
        }
    }

    public function edit()
    {
        Security::requireSessionID();
        Security::verifyReferer();

        // note: we update profile by _userid_
        $profile = $this->request->getParam(0);
        if (!is_numeric($this->request->getParam(0)) && isset($_SESSION["UserID"])) {
            $profile = $_SESSION["UserID"];
        }

        // Allow editing of other profiles only for admins
        if ($this->view->admin != 1 && (!empty($_SESSION["UserID"]) && $this->request->getParam(0) != $_SESSION["UserID"])) {
            $profile = $_SESSION["UserID"];
        }

        $this->view->user_id = $profile;
        $this->view->avatarurl = AVATARURL;
        if (!isset($_SESSION["UserID"])) {
            throw new Common_Exception(i18n("Access denied."));
        }

        foreach (Forum_User::getProfile($profile, false, true) as $p) {
            $this->view->profile = $p;
            $this->view->default_title = FORUM_DEFAULT_TITLE;
        }

        if (isset($_SESSION["admin"]) && $_SESSION["admin"] == 1) {
            $this->view->accessgroups = Forum_User::getAccessGroups($profile);
        }
    }

    public function save()
    {
        Security::requireSessionID();
        Security::verifyReferer();

        // note: we update profile by _userid_
        $profile = $this->request->getParam(0);
        if (!is_numeric($this->request->getParam(0)) && isset($_SESSION["UserID"])) {
            $profile = $_SESSION["UserID"];
        }

        // Allow editing of other profiles only for admins
        if ($this->view->admin != 1 && (!empty($_SESSION["UserID"]) && $this->request->getParam(0) != $_SESSION["UserID"])) {
            $profile = $_SESSION["UserID"];
        }

        $this->view->user_id = $profile;
        if (!Common_Validate::email($_POST["email"])) {
            $this->ui->addError("Valid email is required");
            foreach ($_POST as $k=>$v) {
                $this->view->$k = $v;
            }
            $this->edit();
            $this->ui->setPageRoot("edit");

            return;
        }
        try {
            $gravatar = 0;
            // Enable gravatar support. see www.gravatar.com
            if ($_POST["gravatar"] == "on") {
                $gravatar = 1;
                $avatar = md5(strtolower(trim($_POST["email"])));
            }
            // Upload avatar image
            if (isset($_FILES["avatar"]["tmp_name"]) && !empty($_FILES["avatar"]["tmp_name"]) && $gravatar == 0) {
                if ((($_FILES["avatar"]["type"]=="image/gif")
                    || ($_FILES["avatar"]["type"]=="image/jpeg")
                    || ($_FILES["avatar"]["type"]=="image/pjpeg")
                    || ($_FILES["avatar"]["type"]=="image/png"))
                    && ($_FILES["avatar"]["size"] < 15000)) {
                    if ($_FILES["avatar"]["error"] > 0) {
                        $this->ui->addError(i18n("Error uploading avatar: ").$_FILES["file"]["error"]);
                    } else {
                        $name = "{$this->view->user_id}";
                        if($_FILES["avatar"]["type"] == "image/gif") $name.=".gif";
                        if($_FILES["avatar"]["type"] == "image/jpeg" || $_FILES["avatar"]["type"] == "image/pjpeg") $name .= ".jpg";
                        if($_FILES["avatar"]["type"] == "image/gif") $name.=".png";
                        move_uploaded_file($_FILES["avatar"]["tmp_name"], AVATARPATH."/".$name);
                        $avatar = $name;
                    }
                } else {
                    $this->ui->addError("Uploading avatar failed. Make sure your image file is less than 15kb and either jpg, gif or png format.");
                }
            }
            $user = new Forum_User($this->view->user_id);
            $user->gravatar = $gravatar;
            if (isset($avatar) && !empty($avatar)) {
                $user->avatar = $avatar;
            }

            // admin only settings
            if (isset($_SESSION["admin"]) && $_SESSION["admin"] == 1) {
                $user->moderator = 0;
                $user->admin = 0;
                if (isset($_POST["admin"])) {
                    $user->admin = 1;
                }

                if (isset($_POST["moderator"])) {
                    $user->moderator = 1;
                }

                // Update access groups
                Forum_User::clearAccessGroups($profile);
                if (isset($_POST["accessgroup"])) {
                    foreach ($_POST["accessgroup"] as $k => $accessgroup_id) {
                        Forum_User::addAccessGroup($profile, $accessgroup_id);
                    }
                }

            }

            foreach ($_POST as $k=>$v) {
                if (in_array($k, array("user_from", "real_name", "title", "www", "email", "signature", "bio"))) {
                    $user->$k = $v;
                }
            }

            $user->commit();
            $this->ui->addMessage("Profile saved.");

            // We wanna get back to somewhere else
            if (isset($_SESSION["__referer"])) {
                $tmp = $_SESSION["__referer"];
                unset($_SESSION["__referer"]);
                Controller_Redirect::to($tmp, true);
            }

            // Regular redirects
            if (FORUM_CUSTOM_PROFILE_REDIRECT) {
                Controller_Redirect::to(FORUM_CUSTOM_PROFILE_REDIRECT."/{$this->view->user_id}");
            } else {
                Controller_Redirect::to("forum_profile/{$this->view->user_id}");
            }
        } catch (Exception $e) {

        }
    }

}
