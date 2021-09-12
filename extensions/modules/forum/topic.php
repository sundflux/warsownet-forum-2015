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
 * Post new topic
 *
 * @package       Module
 * @subpackage    Forum
 */

class Module_Forum_Topic extends Main
{
    public function __construct()
    {
        $this->ui->addJS("forum/forum");
        $this->ui->addJS("forum/global");
        $this->ui->addJS("forum/jquery.waitforimages");
        try {
            Forum::init();
            $this->view->unreadCount = Forum_Unread::getUnreadCount();
            $this->view->unreadPMCount = Forum_Unread::getUnreadPrivateMessageCount();
        } catch (Exception $e) {
        }
        $this->view->forumNavi = true;
        $this->timer = new Common_Timer;

        // Little extra validation for non-verified accounts
        $this->view->notVerified = true;
        if (isset($_SESSION["verified"]) && $_SESSION["verified"] == 1) {
            unset($this->view->notVerified);
        }
    }

    public function index()
    {
        $this->ui->addJS("forum/jquery.bbcode");
        $this->ui->addJS("forum/jquery.resize");
        $this->ui->addXSL("pagination");

        // Check if we're logged in..
        if (!isset($_SESSION["UserID"])) {
            $this->view->notlogged = true;

            return;
        }

        // Post flood timer
        try {
            $this->timer->trigger();
        } catch (Exception $e) {
            $this->view->wait = $e->getMessage();

            return;
        }

        // Little extra validation for non-verified accounts
        if (isset($this->view->notVerified)) {
            $captcha = new Captcha;
            if (!Captcha::getCaptcha()) {
                $captcha->genCaptcha();
            }
            $this->view->captchaView = (string) $captcha;

            // Save post vars to view
            foreach ($_POST as $k=>$v) {
                if ($k != "captcha") {
                    $this->view->$k = $v;
                }
            }

            // XSL sets if these are not() set so unset empty values
            if (isset($this->view->post_id) && empty($this->view->post_id)) {
                unset($this->view->post_id);
            }

            if (isset($this->view->topic_id) && empty($this->view->topic_id)) {
                unset($this->view->topic_id);
            }
        }

        // Meep!
        if (!$this->request->getParam(0) || !is_numeric($this->request->getParam(0))) {
            throw new Exception(i18n("Forum ID required"));
        }

        // Include close link if loaded through ajax call
        if ($this->request->isAjax()) {
            $this->view->ajax = true;
        }

        $this->view->forum_id = $this->request->getParam(0);

        // Posting topic or to topic?
        $toTopic = false;
        if ($this->request->getParam(1)) {
            $topic_id = $this->view->topic_id = $this->request->getParam(1);
            $toTopic = true;
        }

        // Check for permissions
        Forum::hasPermission($this->view->forum_id);

        if ($toTopic) {
            // Check that this topic really belongs to this forum
            if (Forum_Topic::getForumID($this->view->topic_id) != $this->view->forum_id) {
                throw new Exception("Thread isn't child of given forum.");
            }

            // Get quoted comment
            if (is_numeric($this->request->getParam(2)) && !$this->request->getParam(3)) {
                $this->view->content = self::getQuotedPost($this->request->getParam(2));
            }

            // Get comment for editing
            if (is_numeric($this->request->getParam(2)) && $this->request->getParam(3) == "edit") {
                $this->view->content = self::getUnQuotedPost($this->request->getParam(2));
                $this->view->post_id = $this->request->getParam(2);
            }
        }

        if (isset($_POST["add"])) {
            Security::requireSessionID();
            Security::verifyReferer();

            // Check for bans
            Forum_Login::checkBan();

            // Little extra validation for non-verified accounts
            if (isset($this->view->notVerified)) {
                if (!isset($_POST["captcha"]) || empty($_POST["captcha"]) || !Captcha::validate($_POST["captcha"])) {
                    $captcha->genCaptcha();
                    $this->view->captchaView = (string) $captcha;
                    $this->ui->addError(i18n("Could not validate captcha. Please try again"));

                    return;
                }

/*				if (!$toTopic) {
                    $tmp = explode(" ", $_POST["title"]);
                    foreach ($tmp as $word) {
                        $w = new Antispam_Words($word);
                        $res = (string) $w;
                        $res = (int) $res;
                        if ($res > 1) {
                            $this->ui->addError(i18n("Spam content not allowed. Go away please."));

                            return;
                        }
                    }
                }

                $tmp = explode(" ", $_POST["content"]);
                foreach ($tmp as $word) {
                    $w = new Antispam_Words($word);
                    $res = (string) $w;
                    $res = (int) $res;
                    if ($res > 1) {
                        $this->ui->addError(i18n("Spam content not allowed. Go away please."));

                        return;
                    }
                }*/
            }

            if (!$toTopic) {
                if (!isset($_POST["title"]) || empty($_POST["title"])) {
                    throw new Exception(i18n("Title is required"));
                }
            }

            if (!isset($_POST["content"]) || empty($_POST["content"])) {
                throw new Exception(i18n("Content is required"));
            }

            // Check content for url tags for unverified accounts and prevent them.
            if (isset($this->view->notVerified) && FORUM_VERIFIED_DISABLE_URLS == 1) {
                // prevent these tags
                $check = array("[url", "[img");

                $tmp = $_POST["content"];
                foreach ($check as $c) {
                    $pos = strpos($tmp, $c);
                    if ($pos !== false) {
                        // Regenerate captcha again
                        $captcha->genCaptcha();
                        $this->view->captchaView = (string) $captcha;
                        $this->ui->addError(i18n("You cannot use url or img tags until your account is verified. Please post them as plain text."));

                        return;
                    }
                }
                unset($tmp);
            }

            // Check for permissions to post here.
            $tmp = Forum_Forum::getForumInfo($this->view->forum_id);
            $perm = Forum_ACL::getPermissionsFromSession($tmp->group_id);

            // Make sure unverified accounts don't post
            if (!$toTopic && is_numeric($this->view->forum_id) && !isset($_SESSION["verified"]) || (isset($_SESSION["verified"]) && $_SESSION["verified"] == 0)) {
                $tmp = explode(",", FORUM_REQUIRE_VERIFIED_IDS);
                foreach ($tmp as $tmpk => $tmpid) {
                    // Set permission to read only
                    if ($tmpid == $this->view->forum_id) {
                        $perm = 4;
                    }
                }
            }

            try {
                // Begin transaction...
                db()->beginTransaction();

                // Check for checksum for doubleposts
                $compareCRC = "";
                if (isset($_SESSION["__LASTPOST_CRC"]) && isset($_SESSION["__LASTPOST_CRC_TIMESTAMP"]) && $_SESSION["__LASTPOST_CRC_TIMESTAMP"] > time()) {
                    $compareCRC = $_SESSION["__LASTPOST_CRC"];
                }

                // This is doublepost
                $currentPostCRC = crc32($_POST["content"]);
                if (!empty($compareCRC) && $compareCRC == $currentPostCRC) {
                    $doublePost = true;
                }

                // Add new topic
                if (!$toTopic) {
                    // Only permissions to reply to topics
                    if ($perm == 4 || $perm == 6) {
                        throw new Exception(i18n("No permissions to create new topics."));
                    }
                    $topic_id = Forum_Topic::add($this->view->forum_id, $_POST["title"]);
                } else {
                    // Only permissions read
                    if ($perm == 4) {
                        throw new Exception(i18n("Read-only permissions."));
                    }
                }

                if (isset($_POST["post_id"]) && is_numeric($_POST["post_id"]) && Forum_Post::validateHasPermission($_POST["post_id"], $_SESSION["UserID"])) {
                    // Save edited post
                    Forum_Post::update($_POST["post_id"], $_POST["content"]);

                    $post_id = $_POST["post_id"];
                    $topic_id = $this->view->topic_id;
                } elseif (!isset($_POST["post_id"]) || empty($_POST["post_id"])) {
                    // Add new post to topic
                    $post_id = Forum_Post::add($topic_id,$_POST["content"], $_SESSION["UserID"]);

                    // Update poster infos and counts
                    Forum_Forum::update($this->view->forum_id);
                    Forum_User::update($_SESSION["UserID"]);

                    // Set first post id
                    if (!$toTopic) {
                        Forum_Topic::firstpost($topic_id, $post_id);
                    }
                } else {
                    throw new Exception("Unexpected error");
                }

                Forum_Topic::update($topic_id);

                // Commit transaction, or just skip it if this is doublepost.
                if (!isset($doublePost)) {
                    db()->commit();
                } else {
                    db()->rollBack();
                }

                // CRC check for doubleposts for next 5 seconds
                $_SESSION["__LASTPOST_CRC_TIMESTAMP"] = time() + 5;
                $_SESSION["__LASTPOST_CRC"] = $currentPostCRC;

                $page = Forum_Post::getPage($post_id);

                // Redirect to new thread
                if (!$toTopic) {
                    $this->ui->addMessage(i18n("New topic added."));
                } elseif (isset($_POST["post_id"]) && is_numeric($_POST["post_id"])) {
                    $this->ui->addMessage(i18n("Post saved."));
                } else {
                    $this->ui->addMessage(i18n("New reply added."));
                }

                // Reset catpcha if exists
                if (isset($_SESSION["captcha"])) {
                    unset($_SESSION["captcha"]);
                }

                Controller_Redirect::to("forum/thread/t/{$post_id}#post-{$post_id}");
            } catch (Exception $e) {
                // Something gone wrong, rollback
                db()->rollBack();
                if ($e->getMessage() == "Not an owner, no permissions to edit") {
                    $post_id = $_POST["post_id"];
                    $this->ui->addError(i18n("Not an owner, no permissions to edit"));
                } else {
                    $this->ui->addError(i18n("Adding topic failed."));
                }

                // Reset catpcha if exists
                if (isset($_SESSION["captcha"])) {
                    unset($_SESSION["captcha"]);
                }

                Controller_Redirect::to("forum/thread/t/{$post_id}#post-{$post_id}");
            }
        }
    }

    /**
     * Parse for preview
     */
    public function preview()
    {
        if (!isset($_POST["content"]) || empty($_POST["content"]) || !$this->request->isAjax()) {
            return;
        }
        $this->view->preview = Format_Tidy::validate(Format::parse(htmlspecialchars($_POST["content"]), FORUM_PARSER_INTERFACE), Format_Tidy::$repair);
    }

    /**
     * Get post content (for ajax call, quote/reply)
     */
    private static function getQuotedPost($id)
    {
        Forum_ACL::canAccessPost($id);
        if (is_numeric($id)) {
            $post = Forum_Post::getPost($id);

            return "[quote={$post->username}]{$post->content}\n[/quote]\n\n";
        }
    }

    /**
     * Get post content (for ajax, quote/reply)
     */
    private static function getUnQuotedPost($id)
    {
        Forum_ACL::canAccessPost($id);
        if (is_numeric($id)) {
            $post = Forum_Post::getPost($id);

            return $post->content;
        }
    }

    public function checkForTagErrors()
    {
        if (!Format_Tidy::validate(Format::parse(htmlspecialchars($_POST["content"]), FORUM_PARSER_INTERFACE))) {
            echo "1"; // has errors
        }

        echo "0"; // no errors
        exit;
    }

}
