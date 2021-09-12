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
 * Forum module, handle updating thread views
 * @package       Kernel
 * @subpackage    Forum
 */

// maximum number of read topics to keep in session
if(!defined('FORUM_MAX_VIEWED_TOPICS')) DEFINE('FORUM_MAX_VIEWED_TOPICS', 256);

class forum_views
{
    /**
     * Update forum view count
     *
     * @access      public
     * @param int $thread GroupID
     */
    public function __construct($topic_id)
    {
        if (!isset($_SESSION["__viewed_topics"])) {
            $_SESSION["__viewed_topics"] = array();
        }

        // check if topic is read already in this session
        if (in_array($topic_id, $_SESSION["__viewed_topics"])) {
            return;
        }

        // flood session with maximum of X threads, drop first from array if it is
        if (count($_SESSION["__viewed_topics"]) >= FORUM_MAX_VIEWED_TOPICS) {
            foreach ($_SESSION["__viewed_topics"] as $k => $v) {
                unset($_SESSION["__viewed_topics"][$k]);
                break;
            }
        }

        self::update($topic_id);
    }

    /**
     * Update count
     *
     * @access      private static
     * @param int $id Topic ID
     */
    private static function update($topic_id)
    {
        $_SESSION["__viewed_topics"][] = $topic_id;

        $query = "
            UPDATE forum_topic
            SET views = views + 1
            WHERE id = ?";

        $stmt = db()->prepare($query);
        $stmt->set((int) $topic_id);
        try {
            $stmt->execute();
        } catch (Exception $e) {

        }
    }

}
