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
 * Portions created by the Initial Developer are Copyright (C) 2013
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
 * @package       Kernel
 * @subpackage    Forum
 */

// Maximum number of discussions to load
if(!defined('FORUM_PM_MAX_DISCUSSIONS')) DEFINE('FORUM_PM_MAX_DISCUSSIONS', 64);

// Maximum number of messages to load
if(!defined('FORUM_PM_MAX_MESSAGES'))    DEFINE('FORUM_PM_MAX_MESSAGES', 32);

class Forum_PM
{
    private $UserID;

    public function __construct($UserID = false)
    {
        if (is_numeric($UserID)) {
            $this->UserID = $UserID;
        }
        if (!$this->UserID) {
            throw new Exception("UserID missing. {$this->UserID}");
        }
    }

    private function getOpenDiscussionUserIDs()
    {
        $query = "
            SELECT DISTINCT receiver_id,sender_id FROM forum_pm_messages
            WHERE ((sender_id = ? AND inbox=1) or (receiver_id = ? AND outbox = 1))
            ORDER BY id
            DESC
            LIMIT ".FORUM_PM_MAX_DISCUSSIONS;

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $this->UserID);
            $stmt->set((int) $this->UserID);
            $stmt->execute();
            foreach ($stmt as $row) {
                $ids[] = $row->receiver_id;
                $ids[] = $row->sender_id;
            }

            if (!isset($ids) || empty($ids)) {
                return false;
            }

            $ids = array_unique($ids);

            // Drop self out of the discussions list
            foreach ($ids as $id) {
                if ($id == $this->UserID) {
                    continue;
                }
                $tmp[] = $id;
            }
            if (isset($tmp)) {
                $ids = $tmp;
            }
        } catch (Exception $e) {

        }

        if (isset($ids)) {
            return $ids;
        }

        return false;
    }

    public function discussions()
    {
        $ids = $this->getOpenDiscussionUserIDs();
        foreach ($ids as $k => $id) {
            $discussion = new Forum_PM_Discussion($this->UserID, $id);
            $discussions[$k] = new stdClass;
            $discussions[$k]->user_id = $id;
            $discussions[$k]->unreadCount = $discussion->unread();
        }
        if (isset($discussions)) {
            $retval = new stdClass;
            $retval->discussions = $discussions;
            $retval->users = Forum_User::getProfile($ids);

            return $retval;
        }

        return false;
    }

    public function getUnreadCount()
    {
        $ids = $this->getOpenDiscussionUserIDs();
        $i = 0;
        foreach ($ids as $k => $id) {
            $discussion = new Forum_PM_Discussion($this->UserID, $id);
            $i += $discussion->unread();
        }

        return $i;
    }

    public function archive($UserID)
    {
        $discussion = new Forum_PM_Discussion($this->UserID, $UserID);
        $discussion->archive();
        Controller_Redirect::to("forum_pm");
    }

    public function loadDiscussion($UserID)
    {
        $discussion = new Forum_PM_Discussion($this->UserID, $UserID);

        return $discussion->messages();
    }

    public function add($UserID, $message)
    {
        $discussion = new Forum_PM_Discussion($this->UserID, $UserID);
        $discussion->add($message);
    }

    public function markMessagesRead($UserID)
    {
        $discussion = new Forum_PM_Discussion($this->UserID, $UserID);
        $discussion->markMessagesRead();
    }

}

class Forum_PM_Discussion
{
    public function __construct($UserID, $targetUserID)
    {
        $this->UserID = $UserID;
        $this->targetUserID = $targetUserID;

        $this->loaded = false;
        $this->objCache = false;
    }

    public function archive()
    {
        $query = "
            UPDATE forum_pm_messages SET inbox = 0
            WHERE sender_id = ? AND receiver_id = ? AND inbox=1";

        $query2 = "
            UPDATE forum_pm_messages SET outbox = 0
            WHERE sender_id = ? AND receiver_id = ? AND outbox = 1";

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $this->UserID);
            $stmt->set((int) $this->targetUserID);
            $stmt->execute();

            $stmt = db()->prepare($query2);
            $stmt->set((int) $this->targetUserID);
            $stmt->set((int) $this->UserID);
            $stmt->execute();
        } catch (Exception $e) {

        }

        $this->loaded = false; // something changed, reload cache object
    }

    public function messages()
    {
        // Only query once per object no matter what.
        if ($this->loaded) {
            return $this->objCache;
        }

        $query = "
            SELECT COUNT(id) as count FROM forum_pm_messages
            WHERE (
                (sender_id = ? AND receiver_id = ? AND inbox=1) or
                (sender_id = ? AND receiver_id = ? AND outbox = 1))
            ORDER BY id";

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $this->UserID);
            $stmt->set((int) $this->targetUserID);
            $stmt->set((int) $this->targetUserID);
            $stmt->set((int) $this->UserID);
            $stmt->execute();

            $count = $stmt->fetchColumn();
        } catch (Exception $e) {

        }

        $count -= FORUM_PM_MAX_MESSAGES;
        if ($count < 0) {
            $count = 0;
        }

        $query = "
            SELECT id, message, sent_date, read_date, sender_id, receiver_id, status, inbox, outbox FROM forum_pm_messages
            WHERE (
                (sender_id = ? AND receiver_id = ? AND inbox=1) or
                (sender_id = ? AND receiver_id = ? AND outbox = 1))
            ORDER BY id
            ASC
            LIMIT ".FORUM_PM_MAX_MESSAGES.
            " OFFSET {$count}";

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $this->UserID);
            $stmt->set((int) $this->targetUserID);
            $stmt->set((int) $this->targetUserID);
            $stmt->set((int) $this->UserID);
            $stmt->execute();
            foreach ($stmt as $row) {
                $i = $row->id;
                $messages[$i] = new stdClass;
                $messages[$i]->id = $i;
                $messages[$i]->message = Format_Tidy::validate(Format::parse(htmlspecialchars(Security::strip($row->message)), FORUM_PARSER_INTERFACE), Format_Tidy::$repair);
                $messages[$i]->sent_date = $row->sent_date;
                $messages[$i]->read_date = $row->read_date;
                $messages[$i]->sender_id = $row->sender_id;
                $messages[$i]->receiver_id = $row->receiver_id;
                $messages[$i]->status = $row->status;
                $messages[$i]->inbox = $row->inbox;
                $messages[$i]->outbox = $row->outbox;
            }
        } catch (Exception $e) {

        }

        if (isset($messages)) {
            $this->objCache = $messages;
            $this->loaded = true;

            return $messages;
        }

        return false;
    }

    public function add($message)
    {
        $query = "
            INSERT INTO forum_pm_messages
                (sent_date, sender_id, receiver_id, message, status, inbox, outbox)
            values
                (NOW(),?,?,?,'sent',1,1)";

        try {
            $stmt = db()->prepare($query);
            $stmt->set((int) $this->UserID);
            $stmt->set((int) $this->targetUserID);
            $stmt->set($message);
            $stmt->execute();
        } catch (Exception $e) {

        }
    }

    public function unread()
    {
        $msgs = $this->messages();
        $i = 0;
        foreach ($msgs as $message) {
            // logic: if sender is not me, and target is me,
            // and status is NOT read, i have received something
            // but haven't read it yet. or something =P
            if ($message->status == "sent" && ($message->receiver_id == $this->UserID)) {
                $i++;
            }
        }

        return $i;
    }

    public function markMessagesRead()
    {
        $msgs = $this->messages();
        foreach ($msgs as $message) {
            if ($message->status == "sent" && ($message->receiver_id == $this->UserID)) {
                $query = "
                    UPDATE forum_pm_messages SET status='read',read_date=NOW()
                    WHERE
                    receiver_id = ?
                    AND sender_id = ?
                    AND id = ?";

                $stmt = db()->prepare($query);
                $stmt->set((int) $this->UserID);
                $stmt->set((int) $this->targetUserID);
                $stmt->set((int) $message->id);

                try {
                    $stmt->execute();
                } catch (Exception $e) {

                }
            }
        }
    }

}
