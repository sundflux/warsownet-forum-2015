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
 * Check for unenclosed tags
 *
 * @package       Kernel
 * @subpackage    Format
 */

class format_tidy
{
    public static $repair = true;

    /**
     * Validate xhtml well-formed-ness with Tidy.
     *
     * @access      public
     * @param  string $string Input html to check
     * @param  bool   $repair Attempt to repair the html?
     * @return mixed
     */
    public static function validate($string = "", $repair = false)
    {
        // We fake the input to tidy as a 'full' xhtml document and strip everything
        // but body off later on.
        $tidy = tidy_parse_string('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."<html><head><title>v</title></head><body>{$string}</body></html>",
        array(
            'doctype'        => 'transitional',
            'input-xml'      => 'yes',
            'output-xhtml'   => 'yes',
            'char-encoding'  => 'utf8',
            'input-encoding' => 'utf8'
        ), 'UTF8');

        // repair the string
        if ($repair) {
            $tidy->cleanRepair();

            // XHTML correctness. Tidy should do this but somehow
            // these things leak through both output-xhtml and output-xml.
            // Probably bunch of things leak through these common cases too
            // but this covers most of it.

            // Replace <br>
            $tidy->value = str_replace("<br>", "<br/>", $tidy->value);

            // Fix unenclosed image tags
            $tidy->value = preg_replace("/<(img.+?[^\/])>/si","<$1 />", $tidy->value);

            // Add alt="" where missing.
            $tidy->value = preg_replace("/<(img(?![^>]+?\s+alt\s*=[^>]+?\>)[^>]+)\/>/si",'<$1 alt=""/>', $tidy->value);

            // strip everything else than body off after tidy has done its job
            preg_match("'<body>(.*?)</body>'si", $tidy->value, $bodyOnly);
            if ($bodyOnly) {
                return $bodyOnly[1];
            }

            return $tidy->value;
        }

        // diagnose only
        $tidy->diagnose();
        $messages = explode("\n", $tidy->errorBuffer);
        foreach ($messages as $k => $message) {
            if (strpos($message, "missing") !== false) {
                return false;
            }
        }

        return true;
    }

}
