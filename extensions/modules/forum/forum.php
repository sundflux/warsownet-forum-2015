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
 * Forum module
 *
 * @package       Module
 * @subpackage    Forum
 */
class Module_Forum extends Main
{
    public function __construct()
    {
        $this->ui->addJS("forum/global");
        $this->ui->addJS("forum/jquery.simplemodal");
        $this->ui->addJS("forum/jquery.waitforimages");
        $this->ui->addCSS("forum/modal");

        Forum::init();

        // Forum settings to this object
        $this->view->forum = new stdClass;
        $this->view->unreadCount = Forum_Unread::getUnreadCount();
        $this->view->unreadPMCount = Forum_Unread::getUnreadPrivateMessageCount();
        $this->view->forumNavi = true;

        // admins
        if (isset($_SESSION["moderator"])) {
            $this->view->moderator = $_SESSION["moderator"];
        }

        if (isset($_SESSION["admin"])) {
            $this->view->admin = $_SESSION["admin"];
        }

        // Show edit dialog by default
        if (isset($_GET["e"])) {
            $this->view->edit = true;
        }
    }

    /**
     * Forum main page, get groups and forums.
     */
    public function index()
    {
        $this->ui->addJS("forum/jquery.bbcode");
        $this->ui->addXSL("forum/pagination");

        try {
            if ($this->request->getParam(0)) {
                // Get requested forum
                $this->view->forum_id = $this->request->getParam(0);
                $this->view->forum = Forum::getForum($this->request->getParam(0), $this->request->getParam(1));

                // Save latest page to session where we were
                $_SESSION["__forum_page"][$this->request->getParam(0)] = $this->request->getParam(1);
                if (!$this->request->getParam(1) || $this->request->getParam(1) == 0) {
                    unset($_SESSION["__forum_page"]);
                }

                $this->ui->setPageRoot("forum");
            } else {
                // Get forums (grouped)
                $this->view->groups = Forum::getForums();
                $this->view->forum->sitetitle = SITETITLE. " / ".i18n("Forum index");

                // Get last posters and get their info
                foreach ($this->view->groups as $tmp) {
                    if (isset($tmp->forums)) {
                        foreach ($tmp->forums as $forum) {
                            $users[] = $forum->last_post_by;
                        }
                    }
                }
                if (isset($users)) {
                    $this->view->users = Forum_User::getProfile($users, array("user_id", "username", "alias"));
                }
            }
        } catch (Auth_Exception $e) {
            // Whoopsie, no access
            $this->ui->addError("Access denied.");
        } catch (Common_Exception $e) {
            // Whoopsie, query error
            $this->ui->addError("Fetching forums failed: ".$e->getMessage());
        }
    }

    /**
     * Display thread
     */
    public function thread()
    {
        $this->ui->addJS("forum/jquery.bbcode");
        $this->ui->addJS("forum/jquery.resize");
        $this->ui->addXSL("forum/pagination");

        // Default title for posters who don't have any custom one
        $this->view->default_title = FORUM_DEFAULT_TITLE;

        // Avatar base url
        $this->view->avatarurl = AVATARURL;

        try {
            if ($this->request->getParam(0)) {
                $this->view->pageuri = $this->request->getUri();
                // Get thread
                if ($this->request->getParam(0) == "t" && $this->request->getParam(1)) {
                    // Get thread by detecting correct page + topic by the post ID.
                    // More performance heavy, used for permalinks for example.
                    $this->view->forum = Forum::getThread(false, false, $this->request->getParam(1));

                    // Mark thread as read when using detection
                    if (isset($this->view->forum->thread_id) && is_numeric($this->view->forum->thread_id) && isset($_SESSION["UserID"])) {
                        Forum_Unread::markAsRead($this->view->forum->thread_id);
                    }
                } else {
                    // Get thread by regular paging
                    $this->view->forum = Forum::getThread($this->request->getParam(0), $this->request->getParam(1));
                    if (isset($this->view->forum->pages->last) && isset($this->view->forum->thread_id) && is_numeric($this->view->forum->thread_id) && isset($_SESSION["UserID"])) {
                        Forum_Unread::markAsRead($this->view->forum->thread_id);
                    }
                }

                if (isset($this->view->forum->thread_id)) {
                    // increase forum view counter
                    new Forum_Views($this->view->forum->thread_id);

                    // Get owner
                    if (isset($_SESSION["UserID"]) && Forum_Topic::getTopicOwner($this->view->forum->thread_id) == $_SESSION["UserID"]) {
                        $this->view->isowner = 1;
                    }
                }

                // Go back to correct page, where we came from
                if (isset($this->view->forum->id) && isset($_SESSION["__forum_page"][$this->view->forum->id])) {
                    $this->view->backToPage = $_SESSION["__forum_page"][$this->view->forum->id];
                }
            } else {
                throw new Exception("Thread not found");
            }
        } catch (Auth_Exception $e) {
            // Whoopsie, no access
            $this->ui->addError("Access denied.");
        } catch (Exception $e) {
            // Whoopsie, error
            $this->ui->addError("Could not open thread: ".$e->getMessage());
        }

        // Check for bookmarks
        if (isset($this->view->forum->thread_id) && Forum_Bookmark::isBookmarked($this->view->forum->thread_id)) {
            $this->view->isBookmarked = true;
        }
    }

    public function renameThread()
    {
        Security::requireSessionID();
        Security::verifyReferer();

        $owner = $admin = $moderator = 0;

        if (isset($_SESSION["admin"])) {
            $admin = $_SESSION["admin"];
        }
        if (isset($_SESSION["moderator"])) {
            $moderator = $_SESSION["moderator"];
        }
        if (isset($_SESSION["UserID"]) && Forum_Topic::getTopicOwner($this->request->getParam(0)) == $_SESSION["UserID"]) {
            $owner = 1;
        }

        if (isset($_POST["topic_name"]) && !empty($_POST["topic_name"]) && ($moderator == 1 || $admin == 1 || $owner == 1)) {
            Forum_Topic::rename($this->request->getParam(0), $_POST["topic_name"]);
            $this->ui->addMessage("Topic renamed.");
        }

        Controller_Redirect::to("forum/thread/".$this->request->getParam(0));
    }

    // ping every 15 mins to keep session alive
    public function ping()
    {
        $_SESSION["kernelok"] = time();
        exit;
    }

}
