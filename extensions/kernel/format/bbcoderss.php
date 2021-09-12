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
 * 2011 Victor Luchits <vic@warsow.net>
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
 * BBCode class.
 *
 * Renders [bbcode] tags as HTML, RSS edition.
 *
 * @package       Kernel
 * @subpackage    Format
 */

class format_bbcoderss implements Format_ParserInterface
{
    // This is what we're working on with
    private $text;

    // Hunt for these patterns
    public $patterns;

    // And replace with these
    public $replaces;

    /**
     * Initialize bbcode formatter with given text
     *
     * @access      public
     * @param string $text Input string
     */
    public function __construct($text = "")
    {
        $this->text = htmlentities($text);
        $this->addMatch('/\[br\s*\]/i', '<br />');
        $this->addMatch('/\[(\/?)code\]/i', '');
        $this->addMatch('/\[(\/?)quote\]/i', '');
        $this->addMatch('/\[quote=([^\]]+?)\](.*)\[\/quote\]/is', '');
        $this->addMatch('/\[quote\](.*)\[\/quote\]/is', '');
        $this->addMatch('/\[quote=([^\]]+?)\]/i', '');
        $this->addMatch('/\[(\/?)(b|i|u)\]/i', '');
        $this->addMatch('/\[(\/?)h\]/i', '');
        $this->addMatch('/\[email\](.+?)\[\/email\]/i', '<a href="mailto:$1">$1</a>');
        $this->addMatch('/\[img(=([^\]]*?))?\](.*?)\[\/img\]/i', '<a href="$3" rel="nofollow">$3</a>');
        $this->addMatch('/\[s\](.*?)\[\/s\]/i', '$1');
        $this->addMatch('/\[color=(#?)(\w+?)\](.*?)\[\/color\]/i', '$3');
        $this->addMatch('/\[url=(.+?)\](.+?)\[\/url\]/i', '<a href="$1" rel="nofollow">$2</a>');
        $this->addMatch('/\[url\](.+?)\[\/url\]/i', '<a href="$1" rel="nofollow">$1</a>');

        // Video tag is a special case, parse that by calling self::handleVideoTag
        $this->addMatch('/\[video=([^\]]+)\]([^\[]+)\[\/video\]/ie', '');
    }

    /**
     * Add regexp replace to match
     *
     * @access      private
     * @param string $pattern Input regexp pattern
     * @param string $replace Replace pattern
     */
    private function addMatch($pattern, $replace)
    {
        $this->patterns[] = $pattern;
        $this->replaces[] = $replace;
    }

    /**
     * Return formatted end-result
     *
     * @access      public
     * @return string
     */
    public function __toString()
    {
        $s = nl2br(preg_replace($this->patterns, $this->replaces, $this->text), true);

        // Don't allow any references to forum_* modules in bbcode
        while (stripos($s, "forum_") !== false) {
            $s = str_ireplace("forum_", "", $s);
        }

        return (string) $s;
    }

}
