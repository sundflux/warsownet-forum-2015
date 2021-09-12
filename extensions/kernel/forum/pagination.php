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
 * Forum module core
 * @package       Kernel
 * @subpackage    Forum
 */

class forum_pagination
{
    /**
     * Get pagination
     *
     * @access      public
     * @param  int    $page  Current page
     * @param  int    $limit Entries per page
     * @param  int    $total Total number of entries
     * @return object $pages Page counts n stuff
     */
    public static function pages($page, $limit, $total)
    {
        $pages = new stdClass;
        // First page if not defined
        if (!$page) {
            $page = 1;
        }

        // Total number of entries
        $pages->entries = $total;

        // Count total number of pages
        $pages->pages = ceil($total / ($limit));

        // Take in account case of having last post
        // 20 of 20 posts for example, so it doesn't
        // leak to nonexisting page.
        if ($page > $pages->pages) {
            $page = $pages->pages;
        }

        // Current page
        $pages->page = $page;

        // Previous page
        if ($page > 1) {
            $pages->pagePrev = $page - 1;
        }

        // Next page
        if ((($page) * $limit) < $total) {
            $pages->pageNext = $page + 1;
        }

        if ($pages->page == $pages->pages) {
            $pages->last = true;
        }

        // Offset.
        $pages->offset = 0;
        if ($page > 0) {
            $pages->offset = (int) ($page * $limit) - $limit;
        }

        $pages->limit = (int) $limit;

        return $pages;
    }

}
