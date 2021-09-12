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
 * Events timer
 *
 * @package       Kernel
 * @subpackage    Common
 */

if(!defined('COMMON_TIMER'))         DEFINE('COMMON_TIMER', 10);
if(!defined('COMMON_TIMER_ACTIONS')) DEFINE('COMMON_TIMER_ACTIONS', 2);

class common_timer
{
    private $timer = 10;    // Time scope (seconds)
    private $actionsBefore = 2;  // Actions allowed within this time scope

    /**
     * Create timer ticker
     *
     * @access      public
     */
    public function __construct()
    {
        // Scope in seconds for what duration we should monitor the events
        if (COMMON_TIMER) {
            $this->timer = COMMON_TIMER;
        }

        // This is how many events we allow during the given time (common_timer ^)
        if (COMMON_TIMER_ACTIONS) {
            $this->actionsBefore = COMMON_TIMER_ACTIONS;
        }

        // Initialize timer if it doesn't exist
        if (!isset($_SESSION["Common_Timer"])) {
            $_SESSION["Common_Timer"] = new stdClass;
            $_SESSION["Common_Timer"]->counter = $this->actionsBefore;
            $_SESSION["Common_Timer"]->time = time() + $this->timer;
        }
    }

    /**
     * Trigger the ticker, throw exception if maximum amount of events is reached
     *
     * @access      public
     */
    public function trigger()
    {
        if ($_SESSION["Common_Timer"]->time <= time()) {
            $_SESSION["Common_Timer"]->counter = $this->actionsBefore;
            $_SESSION["Common_Timer"]->time = time() + $this->timer;
        }

        // Too many actions, throw exception
        $tmp = $_SESSION["Common_Timer"]->time - time();
        if ($_SESSION["Common_Timer"]->counter == 0) {
            throw new Exception("Please wait ".$tmp." seconds.");
        }

        $_SESSION["Common_Timer"]->counter--;
    }

}
