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
 * Unread posts
 *
 * @package       Module
 * @subpackage    Forum
 */

class Module_Forum_Unread extends Main
{
    public function __construct()
    {
        $this->ui->addJS("forum/global");
        $this->ui->addJS("forum/forum");
        $this->ui->addJS("forum/jquery.simplemodal");
        $this->ui->addJS("forum/jquery.waitforimages");
        $this->ui->addCSS("modal");

        try {
            Forum::init();
            $this->view->unreadPMCount = Forum_Unread::getUnreadPrivateMessageCount();
        } catch (Exception $e) {
            $this->ui->addError(i18n("Session expired, please relogin"));
            Controller_Redirect::to("forum_login");
        }
        $this->view->forum = new stdClass;
        $this->view->forum->sitetitle = SITETITLE. " / ".i18n("Unread posts");
        $this->view->forumNavi = true;
        $this->unread = new Forum_Unread;

        if (isset($_SESSION["moderator"])) {
            $this->view->moderator = $_SESSION["moderator"];
        }
        if (isset($_SESSION["admin"])) {
            $this->view->admin = $_SESSION["admin"];
        }
    }

    public function index()
    {
        $this->view->unread = $this->unread->get();
        $this->view->unreadCount = Forum_Unread::getUnreadCount();
    }

    public function markAllRead()
    {
        Security::requireSessionID();
        Security::verifyReferer();

        Forum_Unread::markAllRead();
        Controller_Redirect::to("forum_unread");
    }

}
