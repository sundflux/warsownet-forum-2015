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
 * Private messages
 *
 * @package       Module
 * @subpackage    Forum
 */

class Module_Forum_PM extends Main
{
    public function __construct()
    {
        $this->ui->addJS("forum/jquery.waitforimages");
        $this->ui->addJS("forum/forum");
        $this->ui->addJS("forum/global");
        $this->ui->addXSL("pagination");

        Forum::init();
        $this->view->unreadCount = Forum_Unread::getUnreadCount();
        $this->view->unreadPMCount = Forum_Unread::getUnreadPrivateMessageCount();

        if (!isset($_SESSION["UserID"])) {
            throw new Exception("Profile not found.");
        }

        // Little extra validation for non-verified accounts
        $this->view->notVerified = true;
        if (isset($_SESSION["verified"]) && $_SESSION["verified"] == 1) {
            unset($this->view->notVerified);
        }

        if (isset($this->view->notVerified)) {
            $this->ui->addError("Only verified accounts can use private messages.");
            Controller_Redirect::to("/forum");
        }

    }

    public function index()
    {
        if (isset($_GET["d"])) {
            $this->view->loadDiscussion = $_GET["d"];
        }

        if ($_SESSION["UserID"] == $_GET["d"]) {
            $this->ui->addError("Cannot send message to self.");
            Controller_Redirect::to("forum_pm");
        }
    }

    public function discussions()
    {
        $pm = new Forum_PM($_SESSION["UserID"]);
        $this->view->discussions = $pm->discussions();
        $this->view->avatarurl = AVATARURL;
    }

    public function discussion()
    {
        $this->view->avatarurl = AVATARURL;
        $this->view->UserID = $_SESSION["UserID"];
        $this->view->targetUserID = $this->request->getParam(0);
        $this->view->users = Forum_User::getProfile(array(0 => $this->view->UserID, 1 => $this->view->targetUserID));

        $pm = new Forum_PM($_SESSION["UserID"]);
        $this->view->discussion = $pm->loadDiscussion($this->view->targetUserID);
        $pm->markMessagesRead($this->view->targetUserID);
    }

    public function archive()
    {
        Security::requireSessionID();
        Security::verifyReferer();

        $UserID = $this->request->getParam(0);
        $pm = new Forum_PM($_SESSION["UserID"]);
        $pm->archive($UserID);
    }

    public function add()
    {
        if (!isset($_POST["message"]) || !isset($_POST["targetUserID"])) {
            return;
        }

        $pm = new Forum_PM($_SESSION["UserID"]);
        $pm->add($_POST["targetUserID"],$_POST["message"]);

        if (!$this->request->isAjax()) {
            Controller_Redirect::to("forum_pm?d={$_POST["targetUserID"]}");
        }
    }

    public function message()
    {
        $this->view->UserID = $_SESSION["UserID"];
        $this->view->targetUserID = $this->request->getParam(0);

        if (!is_numeric($this->view->targetUserID)) {
            throw new Exception("No message target");
        }
    }

    public function start()
    {
        if ($this->request->getParam(0) == "search" && isset($_GET["s"]) && strlen($_GET["s"]) > 2) {
            $this->ui->addXSL("pagination");

            $page = $this->request->getParam(1);
            $this->view->s = false;
            if (isset($_GET["s"])) {
                $this->view->s = $_GET["s"];
            }

            $this->view->users = Forum_User::getUsers($page, $this->view->s);
            $this->view->users->url = "forum_pm/start/search/";

            $controller = new Controller_Request;
            $_SESSION["__referer"] = $controller->getUri();

            $i = 0;
            foreach ($this->view->users as $user) {
                $id = $user->users->user_id;
                $i++;
            }

            if ($i == 1) {
                Controller_Redirect::to("forum_pm?d={$id}");
            }
        }
    }

}
