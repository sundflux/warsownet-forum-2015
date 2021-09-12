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
/**
 * Antispam uses www.stopforumspam.com block lists.
 * See EXTENSIONSPATH/antispam/getspamlist.sh for fetching
 * up-to-date block lists. NOTE: you can only fetch once per day
 * from one IP.
 *
 * @package       Kernel
 * @subpackage    Antispam
 * @uses          Process
 */

class antispam_username
{
    private $enabled = false;

    /**
     * __construct
     *
     * @access      public
     * @param string $username Username
     * @uses        Process
     */
    public function __construct($username)
    {
        // could/should use case insensitive grep here but it's slow against this big index file
        if(file_exists(LIBVALOA_EXTENSIONSPATH."/antispam/listed_username_180.txt")
            && is_readable(LIBVALOA_EXTENSIONSPATH."/antispam/listed_username_180.txt")
            && FORUM_ENABLE_ANTISPAM_USERNAME) {
            $this->output = new Process("grep '{$username}' ".LIBVALOA_EXTENSIONSPATH."/antispam/listed_username_180.txt | /usr/bin/wc -l");
            $this->enabled = true;
        }
    }

    public function __toString()
    {
        if ($this->enabled) {
            return $this->output->stdout();
        }

        return 0;
    }

}
