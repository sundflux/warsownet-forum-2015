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
 * 2005,2006,2007,2011 Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * The Initial Developer of the Original Code is
 * Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * Portions created by the Initial Developer are Copyright (C) 2005,2006,2007,2011
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
 * Change password -module.
 *
 * @package       Module
 * @subpackage    Passwd
 */

class Module_Forum_Passwd extends Main
{
    public function __construct()
    {
        $this->ui->addJS("forum/global");

        Forum::init();
        $this->view->unreadCount = Forum_Unread::getUnreadCount();
        $this->view->unreadPMCount = Forum_Unread::getUnreadPrivateMessageCount();

        if (!isset($_SESSION["UserID"])) {
            Controller_Redirect::to("/");
        }
    }

    public function index()
    {
        $auth = new Auth;
        $this->ui->setPageRoot("index");
    }

    public function change()
    {
        Security::requireSessionID();
        Security::verifyReferer();

        if (isset($_POST["oldpass"]) && isset($_POST["newpass"]) && isset($_POST["newpass2"])) {
            if (!empty($_POST["oldpass"]) && !empty($_POST["newpass"]) && !empty($_POST["newpass2"])) {
                if ($_POST["newpass"] != $_POST["newpass2"]) {
                    $this->ui->addError("Error: Passwords do not match.");
                } else {
                    $auth = new Auth_FAuth;
                    if ($auth->authenticate($_SESSION["User"], $_POST["oldpass"])) {
                        $this->updatePassword($_POST["newpass"]);
                        unset($_POST["oldpass"], $_POST["newpass"], $_POST["newpass2"]);
                    } else {
                        $this->ui->addError("Old password doesn't match");
                    }
                }
            } else {
                $this->ui->addError("Error: You must fill all fields.");
            }
            foreach ($_POST as $k => $v) {
                $this->view->$k = $v;
            }
        }
        $this->index();
    }

    /**
    * Update password using Auth::updatePassword().
    */
    private function updatePassword($newpass)
    {
        $user = new Common_User;
        $user = $user->getUserNameByID($_SESSION["UserID"]);
        $auth = new Auth;
        if ($auth->updatePassword($user, $newpass)) {
            $this->ui->addMessage("Password changed.");
        }
    }

    private function getOldPass()
    {
        $rs = db()->prepare("SELECT password FROM users WHERE userid = ?");
        $rs->set((int) $_SESSION["UserID"])->execute();
        $row = $rs->fetch();

        return $rs->password;
    }

}
