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
 * Search topics and posts
 *
 * @package       Module
 * @subpackage    Forum
 */

class Module_Forum_Search extends Main
{
    public function __construct()
    {
        $this->ui->addJS("forum/global");
        $this->ui->addCSS("modal");

        Forum::init();
        $this->view->forum = new stdClass;
        $this->view->forum->sitetitle = SITETITLE." - Search";

        $this->timer = new Common_Timer;
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
        if (!$this->request->isAjax()) {
            Controller_Redirect::to("forum_search/advanced");
        }

        if (!$this->request->getParam(0)) {
            $this->view->searchScope = i18n("all forums");
        } else {
            $this->view->forum_id = $this->request->getParam(0);
            $this->view->searchScope = Forum_Forum::getForumName($this->request->getParam(0));
        }
    }

    public function doSearch()
    {
        if (isset($_POST["search"])) {
            try {
                $this->timer->trigger();
            } catch (Exception $e) {
                $this->view->wait = $e->getMessage();

                return;
            }
            $this->view->searchResult = Forum_Search::search($_POST["search"], $this->request->getParam(0), 1);
            $this->view->query = $_POST["search"];
            if (is_numeric($this->request->getParam(0))) {
                $this->view->forum_id = $this->request->getParam(0);
            }

            if ($this->view->searchResult == null) {
                unset($this->view->searchResult);
            }
        }
    }

    public function advanced()
    {
        $this->ui->addXSL("pagination");

        $this->view->groups = Forum::getForums();
        $this->view->forum_id = $targetForum = false;
        $this->view->page = 1;

        if (isset($_GET["page"]) && is_numeric($_GET["page"])) {
            $this->view->page = (int) $_GET["page"];
        }

        if (isset($_GET["forum_id"]) && !empty($_GET["forum_id"])) {
            $targetForum = $this->view->forum_id = (int) $_GET["forum_id"];
        }

        if (isset($_GET["forums"]) && is_array($_GET["forums"])) {
            unset($targetForum, $this->view->forum_id);
            $this->view->multiforum = true;
            foreach ($_GET["forums"] as $k=>$v) {
                $targetForum[] = $k;
                $this->view->selected[$k] = new stdClass;
                $this->view->selected[$k]->val = $k;
            }
        }
        if (isset($_GET["query"]) && !empty($_GET["query"]) && strlen($_GET["query"]) > 2) {
            $this->view->query = $_GET["query"];
            if (isset($_GET["submit"])) {
                try {
                    $this->timer->trigger();
                } catch (Exception $e) {
                    $this->view->wait = $e->getMessage();

                    return;
                }
            }
            $this->view->searchResult = Forum_Search::search($_GET["query"], $targetForum, $this->view->page);
            if ($this->view->searchResult->result == null) {
                unset($this->view->searchResult);

                return;
            }
            if (isset($_GET["rss"]) && !empty($_GET["rss"])) {
                $this->asRSS($this->view->searchResult->result, $_GET["query"]);
            }
        }
    }

    private function asRSS($rs, $query)
    {
        $feedType = "RSS";
        if (isset($_GET["feedType"]) && ($_GET["feedType"]=="RSS" || $_GET["feedType"] == "Atom")) {
            $feedType = $_GET["feedType"];
        }

        $feed = new XML_Feeds($feedType);
        $feed->setAuthor(FORUM_AUTHOR);
        $feed->setCopyright(FORUM_AUTHOR);
        $feed->setTitle(SITETITLE);
        foreach ($rs as $row) {
            $item = new FeedItem;
            $item->setTitle("{$row->username} on {$row->topic_title}");
            $item->setLink($this->request->getBaseUri()."forum/thread/{$row->topic_id}");
            $item->setTimeCreated($row->created);
            $item->addCategory($row->forum_name);
            $feed->addItem($item);
        }
        $feed->setDescription("RSS feed for search query: {$query}");
        echo (string) $feed;
        exit;
    }

}
