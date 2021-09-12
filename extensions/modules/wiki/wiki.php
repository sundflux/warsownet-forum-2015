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
 * 2012 Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * The Initial Developer of the Original Code is
 * Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * Portions created by the Initial Developer are Copyright (C) 2012
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

DEFINE('WIKI_READ', 1);
DEFINE('WIKI_EDIT', 2);
DEFINE('WIKI_WRITE', 4);
DEFINE('WIKI_HISTORY', 8);
DEFINE('WIKI_DIFF', 16);
DEFINE('WIKI_REVERT', 32);
DEFINE('WIKI_UPLOAD', 64);
DEFINE('WIKI_DELETE', 128);

class Module_Wiki extends Main
{
    private $document;
    private $mode;
    public $documentContent;

    public function __construct()
    {
        $this->ui->addJS("forum/jquery.resize");
        $this->ui->addJS("forum/global");
        $this->ui->addJS("forum/jquery.simplemodal");
        $this->ui->addJS("forum/jquery.waitforimages");
        $this->ui->addCSS("forum/modal");

        $this->timer = new Common_Timer;
        $this->view->forum = new stdClass;
        $this->view->forum->sitetitle = SITETITLE." / Wiki";
        try {
            Forum::init();
            $this->view->unreadCount = Forum_Unread::getUnreadCount();
            $this->view->unreadPMCount = Forum_Unread::getUnreadPrivateMessageCount();
        } catch (Exception $e) {
        }

        // Document to load
        $this->documentContent = false;
        $this->document = $this->request->getParam(0);
        if (!$this->document) {
            $this->document = "IndexDocument";
        }

        // Document info for sitetitle etc
        $this->view->document = $this->document;
        $this->view->forum->sitetitle .= " / ". $this->document;

        // Read/Write/View history- mode
        $this->mode = WIKI_READ;

        if ($this->request->getParam(1) == "edit") {
            $this->mode = WIKI_EDIT;
        }

        // Little extra validation for non-verified accounts
        $this->view->notVerified = true;
        if (isset($_SESSION["verified"]) && $_SESSION["verified"] == 1) {
            unset($this->view->notVerified);
        }

        if ($this->request->getParam(1) == "save") {
            $this->mode = WIKI_WRITE;
        }
        if ($this->request->getParam(1) == "history") {
            $this->mode = WIKI_HISTORY;
        }
        if ($this->request->getParam(1) == "diff") {
            $this->mode = WIKI_DIFF;
        }
        if ($this->request->getParam(1) == "revert") {
            $this->mode = WIKI_REVERT;
        }
        if ($this->request->getParam(1) == "upload") {
            $this->mode = WIKI_UPLOAD;
        }
        if ($this->request->getParam(1) == "delete") {
            $this->mode = WIKI_DELETE;
        }

        // Table of contents default view mode
        $this->view->show = 1;

        if (isset($_SESSION["UserID"])) {
            $this->view->user_id = $_SESSION["UserID"];
        }
        if (isset($_SESSION["admin"])) {
            $this->view->admin = $this->admin = $_SESSION["admin"];
        }

        // Little extra validation for non-verified accounts
        $this->view->notVerified = true;
        if (isset($_SESSION["verified"]) && $_SESSION["verified"] == 1) {
            unset($this->view->notVerified);
        }
    }

    public function index()
    {
        switch ($this->mode) {
            case WIKI_READ:
                // Read/view a document
                $this->ui->setPageRoot("index");
                $this->documentContent = Wiki::loadDocument($this->document);

                if ($this->documentContent) {
                    // Attachments
                    if ($this->documentContent->attachments) {
                        $this->view->attachments = $this->documentContent->attachments;
                    }

                    // Parent document
                    if ($this->documentContent->parent) {
                        $this->view->parent = $this->documentContent->parent;
                    }

                    $this->view->locked = $this->documentContent->locked;
                    $this->view->documentContent = Format_Tidy::validate(new Format_Wiki(Wiki::numberedHeaders(htmlspecialchars($this->documentContent->document_content, ENT_NOQUOTES))), Format_Tidy::$repair);
                    $this->view->documentTOC = Wiki::gatherTOC($this->documentContent->document_content);
                }

            break;
            case WIKI_EDIT:
                // Edit a document
                $this->ui->setPageRoot("edit");
                $this->view->documentContent = Wiki::loadDocument($this->document);
            break;
            case WIKI_WRITE:
                if (!isset($_SESSION["UserID"])) {
                    throw new Exception("Not logged in.");
                }

                if (isset($this->view->notVerified)) {
                    throw new Exception("Only verified accounts can save documents.");
                }

                // Post flood timer
                try {
                    $this->timer->trigger();
                } catch (Exception $e) {
                    $this->view->wait = $e->getMessage();

                    return;
                }

                // Lock document?
                $lock = 0;
                if (isset($_POST["save_lock"]) && $this->admin == 1) {
                    $lock = 1;
                }

                // Check for lock
                if ($this->admin != 1) {
                    $tmp = Wiki::loadDocument($this->document);
                    if ($tmp->locked == 1) {
                        throw new Exception("This document is locked.");
                    }
                }

                // Save the document
                if (isset($_POST["content"]) && !empty($this->document) && isset($_SESSION["UserID"])) {
                    Wiki::saveDocument($this->document, $_POST["content"], $_SESSION["UserID"], $lock);
                }

                $this->ui->addMessage("Saved");
                Controller_Redirect::to("wiki/{$this->document}");
            break;
            case WIKI_HISTORY:
                // View document history
                $this->ui->setPageRoot("history");
                $this->ui->addXSL("forum/pagination");

                $page = 1;
                if ($this->request->getParam(2)) {
                    $page = $this->request->getParam(2);
                }

                $this->view->history = Wiki::loadHistory($this->document, $page);
                $this->view->history_url = "wiki/{$this->document}/history/";
            break;
            case WIKI_DIFF:
                // View document diff
                $this->ui->setPageRoot("diff");

                if (isset($_GET["revision-current"])) {
                    $this->view->compare_revision = $_GET["revision-current"];
                }

                if (isset($_GET["revision-prev"])) {
                    $this->view->previous_revision = $_GET["revision-prev"];
                }

                if (!isset($this->view->compare_revision) || !isset($this->view->previous_revision)) {
                    throw new Exception("Need 2 revisions to compare.");
                }

                $fineDiff = new FineDiff(
                    Wiki::loadDocument($this->document, $this->view->previous_revision)->document_content,
                    Wiki::loadDocument($this->document, $this->view->compare_revision)->document_content,
                    FineDiff::$wordGranularity);

                $source = $fineDiff->renderDiffToHTML();
                $this->view->diffRaw = $source;
                //$this->view->diff = Format_Tidy::validate(new Format_Wiki(new Format_BBCode(Wiki::numberedHeaders(htmlspecialchars($source)))), Format_Tidy::$repair);
            break;
            case WIKI_REVERT:
                if (!isset($_SESSION["UserID"])) {
                    throw new Exception("Not logged in.");
                }

                if (isset($this->view->notVerified)) {
                    throw new Exception("Only verified accounts can revert documents.");
                }

                // Post flood timer
                try {
                    $this->timer->trigger();
                } catch (Exception $e) {
                    $this->view->wait = $e->getMessage();

                    return;
                }

                // Lock document?
                $lock = 0;
                if (isset($_POST["save_lock"]) && $this->admin == 1) {
                    $lock = 1;
                }

                // Check for lock
                if ($this->admin != 1) {
                    $tmp = Wiki::loadDocument($this->document);
                    if ($tmp->locked == 1) {
                        throw new Exception("This document is locked.");
                    }
                }

                // Restore to this revision
                if ($this->request->getParam(2)) {
                    $toRevision = $this->request->getParam(2);
                }

                if (!isset($toRevision) || !is_numeric($toRevision)) {
                    throw new Exception("Revision not found");
                }

                // Load old revision..
                $oldRevision = wiki::loadDocument($this->document, $toRevision);

                // And save it again.
                Wiki::saveDocument($this->document, $oldRevision->document_content, $_SESSION["UserID"], $oldRevision->locked);

                $this->ui->addMessage("Reverted");
                Controller_Redirect::to("wiki/{$this->document}");
            break;
                case WIKI_UPLOAD:
                $this->ui->setPageRoot("upload");

                if (!isset($_SESSION["UserID"])) {
                    throw new Exception("Not logged in.");
                }

                if (isset($this->view->notVerified)) {
                    throw new Exception("Only verified accounts can revert documents.");
                }

                // Post flood timer
                try {
                    $this->timer->trigger();
                } catch (Exception $e) {
                    $this->view->wait = $e->getMessage();

                    return;
                }

                if (isset($_FILES["upload"]["tmp_name"]) && !empty($_FILES["upload"]["tmp_name"])) {

                    $maxSize = 2048;
                    // Allow uploading only images for now
                    if ((($_FILES["upload"]["type"] == "image/gif")
                        || ($_FILES["upload"]["type"] == "image/jpeg")
                        || ($_FILES["upload"]["type"] == "image/pjpeg")
                        || ($_FILES["upload"]["type"] == "image/png"))
                        && ($_FILES["upload"]["size"] < (1024 * $maxSize))) {
                        if ($_FILES["upload"]["error"] > 0) {
                            $this->ui->addError(i18n("Error uploading file: ").$_FILES["upload"]["error"]);
                        } else {
                            $n = $_FILES["upload"]["name"];

                            // Attempt to rename the file automatically if file already exists
                            if (Wiki::fileExists($n)) {
                                $tmpi = 0;
                                while (Wiki::fileExists($n)) {
                                    $tmpi++;
                                    $ext = false;
                                    if (substr($n, -4, 1) == ".") {
                                        $l = strlen($n) - 4;
                                        $app = substr($n, 0, $l);
                                        $ext = substr($n, -3);
                                        $rep = "-{$tmpi}.";
                                        $n = $app.$rep.$ext;
                                    }
                                    if ($ext == false) {
                                        break;
                                    }
                                }
                            }

                            if (!Wiki::fileExists($n)) {
                                move_uploaded_file($_FILES["upload"]["tmp_name"], WIKI_FILES_PATH."/".$n);
                                Wiki::attachFile($this->document, $n);
                                $this->ui->addMessage("Uploaded file {$n}");
                            } else {
                                $this->ui->addError("File {$n} already exists.");
                            }
                        }
                    } else {
                        $this->ui->addError("Uploading file failed. Make sure your file is less than {$maxSize}kb.");
                    }
                    Controller_Redirect::to("wiki/{$this->document}");
                }
            break;
            case WIKI_DELETE:
                $this->ui->setPageRoot("upload");

                if (!isset($_SESSION["UserID"])) {
                    throw new Exception("Not logged in.");
                }

                if (isset($this->view->notVerified)) {
                    throw new Exception("Only verified accounts can revert documents.");
                }

                // Post flood timer
                try {
                    $this->timer->trigger();
                } catch (Exception $e) {
                    $this->view->wait = $e->getMessage();

                    return;
                }

                // Locate file
                $target = false;
                $document = Wiki::loadDocument($this->document);
                foreach ($document->attachments as $attachment) {
                    if ($attachment->id == $this->request->getParam(2)) {
                        $target = $attachment;
                    }
                }

                // Only admin or owner can delete files
                if ($target && ($this->view->admin == 1 || ($this->view->user_id == $target->uploader_userid))) {
                    Wiki::deleteFile($document, $target);
                    $this->ui->addMessage("File deleted.");
                }

                Controller_Redirect::to("wiki/{$this->document}");
            break;
        }
    }

    public function help()
    {
    }

}
