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
 * 2012 Toni Lähdekorpi <toni@lygon.net>
 *
 * The Initial Developer of the Original Code is
 * Toni Lähdekorpi <toni@lygon.net>
 *
 * Portions created by the Initial Developer are Copyright (C) 2012
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * 2012 Tarmo Alexander Sundström <ta@sundstrom.im>
 * 2012 Victor Luchitz <vic@warsow.net>
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
 * "Wiki" syntax parser
 *
 * @package       Kernel
 * @subpackage    Format
 * @uses          Controller_Request
 * @uses          Format_BBCode
 */

class format_wiki implements Format_ParserInterface
{
    public $patterns;
    public $replacements;
    private $text;

    public function __construct($text = "")
    {
        $this->text = $text;

        $this->patterns = array(
            // Styling
            "/\[div([^]]*?\s+style=\"([^\"]+)\".*?)?\]/i",	            // Divs
            "/\[\/div\]/i",

            // Headings
            "/^==== ([\d\.]+?) (.+?) ====/m",							// Subsubheading
            "/^=== ([\d\.]+?) (.+?) ===/m",								// Subheading
            "/^== ([\d\.]+?) (.+?) ==/m",								// Heading
            "/^= ([\d\.]+?) (.+?) =/m",									// Heading

            // Formatting
            "/\'\'\'\'\'(.+?)\'\'\'\'\'/s",								// Bold-italic
            "/\'\'\'(.+?)\'\'\'/s",										// Bold
            "/\'\'(.+?)\'\'/s",											// Italic

            // Special
            "/\[\[(.+?)\|(.+)\]\]/i", 									// Wiki pages with different title
            "/\[\[(.+)\]\]/i", 											// Wiki pages
            "/^----+(\s*)$/m",											// Horizontal line
            "/\[\[(file|img):((ht|f)tp(s?):\/\/(.+?))( (.+))*\]\]/i",	// (File|img):(http|https|ftp) aka image
            "/\[\[img:(.+\.(gif|png|jpg|jpeg))(\|([^]]+))?\]\]/i",      // Same as above but simply [[img:foo.jpg]] for internal images
            "/\[((news|(ht|f)tp(s?)|irc):\/\/(.+?))( (.+))\]/i",		// Other urls with text
            "/\[((news|(ht|f)tp(s?)|irc):\/\/(.+?))\]/i",				// Other urls without text

            // Indentations
            "/[\n]: *.+([\n]:+.+)*/",									// Indentation first pass
            "/^:(?!:) *(.+)$/m",										// Indentation second pass
            "/([\n\r]:: *.+)+/",										// Subindentation first pass
            "/^:: *(.+)$/m",											// Subindentation second pass

            // Ordered list
            "/[\n\r]#.+([\n|\r]#.+)+/",									// First pass, finding all blocks
            "/[\n\r]#(?!#) *(.+)(([\n\r]#{2,}.+)+)/",					// List item with sub items of 2 or more
            "/[\n\r]#{2}(?!#) *(.+)(([\n\r]#{3,}.+)+)/",				// List item with sub items of 3 or more
            "/[\n\r]#{3}(?!#) *(.+)(([\n\r]#{4,}.+)+)/",				// List item with sub items of 4 or more

            // Unordered list
            "/[\n\r]\*.+([\n|\r]\*.+)+/",								// First pass, finding all blocks
            "/[\n\r]\*(?!\*) *(.+)(([\n\r]\*{2,}.+)+)/",				// List item with sub items of 2 or more
            "/[\n\r]\*{2}(?!\*) *(.+)(([\n\r]\*{3,}.+)+)/",				// List item with sub items of 3 or more
            "/[\n\r]\*{3}(?!\*) *(.+)(([\n\r]\*{4,}.+)+)/",				// List item with sub items of 4 or more

            // List items
            "/^[#\*]+ *(.+)$/m",										// Wraps all list items to <li/>

            // Newlines (TODO: make it smarter and so that it groupd paragraphs)
            "/^(?!<li|dd).+(?=(<a|strong|em|img)).+$/mi",				// Ones with breakable elements (TODO: Fix this crap, the li|dd comparison here is just stupid)
            "/^[^><\n\r]+$/m",											// Ones with no elements

            // These are merely for diffs
            "/\[(\/?)ins\]/i",
            "/\[(\/?)del\]/i",

            // pre
            "/\[(\/?)pre\]/i",
            "/\[(\/?)blockquote\]/i"
        );

        $controller = new Controller_Request;
        $url = $controller->getCurrentRoute();

        // Drop the last / from the url
        $url = substr($url, 0, -1);

        $path = WIKI_FILES_PUBLIC_PATH."/";
        $this->replaces = array(
            // Styling
            "<div$1>",
            "</div>",

            // Headings
            "<h4 id=\"$1\">$1 $2 <a href=\"/{$url}#toc\" class=\"r\"><img src=\"/forum-images/icons/set/arrow_up_12x12.png\" alt=\"back to top\"/></a></h4>",
            "<h3 id=\"$1\">$1 $2 <a href=\"/{$url}#toc\" class=\"r\"><img src=\"/forum-images/icons/set/arrow_up_12x12.png\" alt=\"back to top\"/></a></h3>",
            "<h2 id=\"$1\">$1 $2 <a href=\"/{$url}#toc\" class=\"r\"><img src=\"/forum-images/icons/set/arrow_up_12x12.png\" alt=\"back to top\"/></a></h2>",
            "<h1 id=\"$1\">$1 $2 <a href=\"/{$url}#toc\" class=\"r\"><img src=\"/forum-images/icons/set/arrow_up_12x12.png\" alt=\"back to top\"/></a></h1>",

            //Formatting
            "<strong><em>$1</em></strong>",
            "<strong>$1</strong>",
            "<em>$1</em>",

            // Special
            "<a href=\"wiki/$1\">$2</a>", // wiki pages with title
            "<a href=\"wiki/$1\">$1</a>", // wiki pages
            "<hr class=\"nicehr\"/>",
            "<img src=\"$2\" alt=\"$6\"/>",
            "<div class=\"wiki-image padded rounded alt3\"><img src=\"{$path}$1\" alt=\"\"/><br/><a href=\"{$path}$1\">$1</a></div>",
            "<a href=\"$1\">$7</a>",
            "<a href=\"$1\">$1</a>",

            // Indentations
            "\n<dl>$0\n</dl>", // Newline is here to make the second pass easier
            "<dd>$1</dd>",
            "\n<dd><dl>$0\n</dl></dd>",
            "<dd>$1</dd>",

            // Ordered list
            "\n<ol>$0\n</ol>",
            "\n<li>$1\n<ol>$2\n</ol>\n</li>",
            "\n<li>$1\n<ol>$2\n</ol>\n</li>",
            "\n<li>$1\n<ol>$2\n</ol>\n</li>",

            // Unordered list
            "\n<ul>$0\n</ul>",
            "\n<li>$1\n<ul>$2\n</ul>\n</li>",
            "\n<li>$1\n<ul>$2\n</ul>\n</li>",
            "\n<li>$1\n<ul>$2\n</ul>\n</li>",

            // List items
            "<li>$1</li>",

            // Newlines
            "$0<br />",
            "$0<br />",

            // These are merely for diffs
            "<$1ins>",
            "<$1del>",

            "<$1pre>",
            "<$1blockquote>"
        );

        // Also include replacements from BBCode driver
        $tmp = new Format_BBCode;
        $this->patterns = array_merge($this->patterns, $tmp->patterns);
        $this->replaces = array_merge($this->replaces, $tmp->replaces);
    }

    public function __toString()
    {
        $s = (string) preg_replace($this->patterns, $this->replaces, $this->text);

        // Don't allow any references to forum_* modules in bbcode
        while (stripos($s, "forum_") !== false) {
            $s = str_ireplace("forum_", "", $s);
        }

        return $s;
    }

}
