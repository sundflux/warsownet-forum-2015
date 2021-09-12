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

class Wiki
{
    public static function loadDocument($document, $revision = false)
    {
        $documentContent = false;

        // Load latest revision by default
        if (!$revision) {
            $query = "
                SELECT id, document_title, document_content, user_id, revision, created, locked
                FROM wiki
                WHERE document_title = ?
                ORDER BY revision DESC
                LIMIT 1";

        }

        // Load defined revision
        if (is_numeric($revision)) {
            $query = "
                SELECT id, document_title, document_content, user_id, revision, created, locked
                FROM wiki
                WHERE document_title = ?
                AND revision = ?";

        }

        try {
            $stmt = db()->prepare($query);
            $stmt->set($document);
            if (is_numeric($revision)) {
                $stmt->set((int) $revision);
            }
            $stmt->execute();
            $documentContent = $stmt->fetch();
            $documentContent->attachments = self::loadFiles($documentContent->id);

            // Parent document
            $documentContent->parent = strstr($documentContent->document_title, ":", true);
        } catch (Exception $e) {

        }

        return $documentContent;
    }

    public static function saveDocument($document, $content, $UserID = false, $lock = 0)
    {
        $check = self::loadDocument($document);
        $oldID = $check->id;
        $revision = 1;
        if (isset($check->revision)) {
            $revision = $revision + $check->revision;
        }
        unset($check);

        if (!$UserID) {
            $UserID = @ $_SESSION["UserID"];
        }
        if (!$UserID || !is_numeric($UserID)) {
            throw new Exception("UserID required");
        }
        if (empty($document)) {
            throw new Exception("Document required");
        }
        $query = "
            INSERT INTO wiki (`id`, `document_title`, `document_content`, `user_id`, `revision`, `locked`, `created`)
            VALUES (NULL, ?,?,?,?,?,NOW())";

        try {
            $stmt = db()->prepare($query);
            $stmt->set($document);
            $stmt->set($content);
            $stmt->set((int) $UserID);
            $stmt->set((int) $revision);
            $stmt->set($lock);
            $stmt->execute();
            $newID = db()->lastInsertID();

            // Move attachments for new revision
            if (is_numeric($oldID) && $newID) {
                self::updateAttachmentDocumentID($newID, $oldID);
            }
        } catch (Exception $e) {

        }
    }

    public static function gatherTOC($document)
    {
        $rows = explode("\n", $document);
        $h1 = $h2 = $h3 = $h4 = $i = 0;
        foreach ($rows as $k => $row) {
            $row = trim($row);
            $pos = strpos($row, '= ');
            if ($pos !== false) {
                $tmp[$i] = new stdClass;
                if (substr($row, 0, 5) == "==== ") {
                    $tmp[$i]->class = "sub3";
                    $tmp[$i]->link = str_replace("=", "", $row);
                    $h4++;
                    $tmp[$i]->pos = $h4;
                } elseif (substr($row, 0, 4) == "=== ") {
                    $tmp[$i]->class = "sub2";
                    $tmp[$i]->link = str_replace("=", "", $row);
                    $h3++;
                    $tmp[$i]->pos = $h3;
                    $h4 = 0;
                } elseif (substr($row, 0, 3) == "== ") {
                    $tmp[$i]->class = "sub";
                    $tmp[$i]->link = str_replace("=", "", $row);
                    $h2++;
                    $tmp[$i]->pos = $h2;
                    $h3 = 0;
                    $h4 = 0;
                } elseif (substr($row, 0, 2) == "= ") {
                    $tmp[$i]->class = "parent";
                    $tmp[$i]->link = str_replace("=", "", $row);
                    $h1++;
                    $tmp[$i]->pos = $h1;
                    $h2 = 0;
                    $h4 = 0;
                }
                $tmp[$i]->h1 = $h1;
                $tmp[$i]->h2 = $h2;
                $tmp[$i]->h3 = $h3;
                $tmp[$i]->h4 = $h4;
                if (isset($tmp[$i]->link)) {
                    $tmp[$i]->link = trim($tmp[$i]->link);
                }
                $i++;
            }
        }

        if (isset($tmp)) {
            return $tmp;
        }

        return false;
    }

    public static function numberedHeaders($document)
    {
        $rows = explode("\n", $document);
        $h1 = $h2 = $h3 = $h4 = $i = 0;
        $content = "";
        foreach ($rows as $k => $row) {
            $row = trim($row);
            $pos = strpos($row, '= ');
            if ($pos !== false) {
                $tmp[$i] = new stdClass;
                if (substr($row, 0, 5) == "==== ") {
                    $h4++;
                    $newrow = str_replace("==== ", "==== {$h1}.{$h2}.{$h3}.{$h4}. ", $row);;
                } elseif (substr($row, 0, 4) == "=== ") {
                    $h3++;
                    $newrow = str_replace("=== ", "=== {$h1}.{$h2}.{$h3}. ", $row);;
                    $h4 = 0;
                } elseif (substr($row, 0, 3) == "== ") {
                    $h2++;
                    $newrow = str_replace("== ", "== {$h1}.{$h2}. ", $row);;
                    $h3 = 0;
                    $h4 = 0;
                } elseif (substr($row, 0, 2) == "= ") {
                    $h1++;
                    $newrow = str_replace("= ", "= {$h1}. ", $row);;
                    $h2 = 0;
                    $h4 = 0;
                }
                $i++;
            }
            if (isset($newrow)) {
                $content .= $newrow. "\n";
                unset($newrow);
            } else {
                $content .= $row. "\n";
            }
        }

        return $content;
    }

    public static function loadHistory($document, $page = 1)
    {
        // Pagination
        $pages = Forum_Pagination::pages($page, FORUM_THREADS, self::historyCount($document));
        $retval = new stdClass;
        $retval->pages = $pages;

        // Get history
        $query = "
            SELECT wiki.id, wiki.document_title, wiki.user_id, wiki.revision, wiki.created, users.username as author
            FROM wiki, users
            WHERE wiki.user_id = users.userid
            AND wiki.document_title = ?
            ORDER BY revision DESC
            LIMIT {$retval->pages->limit}
            OFFSET {$retval->pages->offset}";

        try {
            $stmt = db()->prepare($query);
            $stmt->set($document);
            $stmt->execute();
            $i = 0;
            foreach ($stmt as $row) {
                $history[$i] = new stdClass;
                $history[$i]->id = $row->id;
                $history[$i]->document_title = $row->document_title;
                $history[$i]->revision = $row->revision;
                $history[$i]->author_id = $row->user_id;
                $history[$i]->author = $row->author;
                $history[$i]->created = $row->created;
                $i++;
            }
            if (isset($history)) {
                $retval->history = $history;
            }

            return $retval;
        } catch (Exception $e) {

        }

        return $documentContent;
    }

    public static function historyCount($document)
    {
        $documentContent = false;
        $query = "
            SELECT COUNT(wiki.id) as total
            FROM wiki
            WHERE wiki.document_title = ?";

        try {
            $stmt = db()->prepare($query);
            $stmt->set($document);
            $stmt->execute();
            $row = $stmt->fetch();

            return $row->total;
        } catch (Exception $e) {

        }

        return $documentContent;
    }

    public static function attachFile($document, $filename)
    {
        $documentInfo = self::loadDocument($document);

        $query = "
            INSERT INTO `wiki_files` (`id` ,`document_id` ,`uploader` ,`filename` ,`deleted`)
            VALUES (NULL , ?, ?, ?, ?)";

        try {
            $stmt = db()->prepare($query);
            $stmt->set($documentInfo->id);
            // Allow anonymous file-uploads at least code-wise.
            $u = 0;
            if (isset($_SESSION["UserID"])) {
                $u = $_SESSION["UserID"];
            }
            $stmt->set($u);
            $stmt->set($filename);
            $stmt->set(0);
            $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Attaching file {$filename} to document {$document} failed.");
        }
    }

    public static function deleteFile($document, $fileInfo)
    {
        $documentInfo = self::loadDocument($document);

        $query = "
            UPDATE wiki_files
            SET deleted = ?
            WHERE id = ?";

        try {
            $stmt = db()->prepare($query);
            $stmt->set($documentInfo->id);
            $stmt->set($fileInfo->id);
            $stmt->execute();

            if (WIKI_DELETE_FILES) {
                // Delete the actual file
                if ($p = self::getPath($fileInfo->filename_nopath)) {
                    @unlink($p);
                }
            } else {
                // Just rename the file
                if ($p = self::getPath($fileInfo->filename_nopath)) {
                    @rename($p, $p.".deleted");
                }
            }

        } catch (Exception $e) {

        }
    }

    public static function updateAttachmentDocumentID($newDocumentID, $oldDocumentID)
    {
        $query = "
            UPDATE wiki_files
            SET document_id = ?
            WHERE document_id = ?";

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $newDocumentID);
            $stmt->set((int) $oldDocumentID);
            $stmt->execute();
        } catch (Exception $e) {

        }
    }

    public static function loadFiles($documentID)
    {
        $query = "
            SELECT id, filename, uploader
            FROM wiki_files
            WHERE document_id = ? and deleted = 0";

        try {
            $stmt = db()->prepare($query);
            $stmt->set($documentID);
            $stmt->execute();

            $i = 0;
            foreach ($stmt as $row) {
                if (self::fileExists($row->filename)) {
                    $retval[$i] = new stdClass;
                    $retval[$i]->id = $row->id;
                    $retval[$i]->uploader_userid = $row->uploader;
                    $retval[$i]->filename_nopath = $row->filename;
                    $retval[$i]->filename = WIKI_FILES_PUBLIC_PATH."/".$row->filename;
                    $i++;
                }
            }

            if (isset($retval)) {
                return $retval;
            }

            return false;
        } catch (Exception $e) {

        }
    }

    private static function getPath($filename)
    {
        if (self::fileExists($filename)) {
            return WIKI_FILES_PATH."/".$filename;
        }

        return false;
    }

    public static function fileExists($filename)
    {
        if (file_exists(WIKI_FILES_PATH."/".$filename)) {
            return true;
        }

        return false;
    }

}
