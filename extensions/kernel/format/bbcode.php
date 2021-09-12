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
 * Renders [bbcode] tags as HTML.
 *
 * @package       Kernel
 * @subpackage    Format
 */

class format_bbcode implements Format_ParserInterface
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
        $this->text = $text;
        $this->addMatch('/\[br\s*\]/i', '<br />');
        $this->addMatch('/\[(\/?)code\]/i', '<$1pre>');
        $this->addMatch('/\[(\/?)quote\]/i', '<$1blockquote>');
        $this->addMatch('/\[(\/?)ins\]/i', '<$1ins>');
        $this->addMatch('/\[(\/?)del\]/i', '<$1del>');
        $this->addMatch('/\[quote=([^\]]+?)\]/i', '<blockquote><b><i>$1 wrote:</i></b><br/>$2');
        $this->addMatch('/\[(\/?)(b|i|u)\]/i', '<$1$2>');
        $this->addMatch('/\[(\/?)h\]/i', '<$1h3>');
        $this->addMatch('/\[email\](.+?)\[\/email\]/i', '<a href="mailto:$1">$1</a>');
        $this->addMatch('/\[img(=([^\]]*?))?\](.*?)\[\/img\]/i', '<br/><img src="$3" alt="$2"/><br/>');
        $this->addMatch('/\[s\](.*?)\[\/s\]/i', '<span style="text-decoration: line-through;">$1</span>');
        $this->addMatch('/\[color=(#?)(\w+?)\](.*?)\[\/color\]/i', '<span style="color: $1$2;">$3</span>');
        $this->addMatch('/\[url=(.+?)\](.+?)\[\/url\]/i', '<a href="$1" rel="nofollow">$2</a>');
        $this->addMatch('/\[url\](.+?)\[\/url\]/i', '<a href="$1" rel="nofollow">$1</a>');
        $this->addMatch('/\[video=([^\]]+)\]([^\[]+)\[\/video\]/ie', 'Format_BBCode::handleVideoTag(\'$0\',\'$1\',\'$2\')');
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

    /**
     * Handle videotag for several different video services
     *
     * @access      public
     * @param string $sSource   Source
     * @param string $videoType Video type
     * @param string $videoId   Video ID
     *
     * @return string
     */
    public static function handleVideoTag($sSource, $videoType, $videoId)
    {
        // the services list
        $services = array(
            'youtube'    => array(
//				'uri'    => 'http://www.youtube.com/embed/%s?vq=hd1080',
                'uri'    => 'http://www.youtube.com/v/%s&amp;rel=0&amp;fs=1',
//				'uri'    => 'http://www.youtube.com/embed/%s',
                'width'  => 640,
                'height' => 400
//				'width'  => 425,
//				'height' => 344
            ),

            'dailymotion'=> array(
                'uri'    => 'http://www.dailymotion.com/swf/%s&amp;amp;related=0&amp;amp;canvas=medium',
                'width'  => 480,
                'height' => 381
            ),

            'vimeo'      => array(
                'uri'    => 'http://www.vimeo.com/moogaloop.swf?clip_id=%s&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;fullscreen=1',
                'width'  => 400,
                'height' => 302
            ),

            'google'     => array(
                'uri'    => 'http://video.google.com/googleplayer.swf?docId=%s',
                'width'  => 425,
                'height' => 364
            ),

            'moddb'      => array(
                'uri'    => 'http://www.moddb.com/media/embed/%s',
                'width'  => 432,
                'height' => 263
            ),

            'megavideo'  => array(
                'uri'    => 'http://www.megavideo.com/v/%s',
                'width'  => 432,
                'height' => 351
            )
        );

        // extract service's name and check for support
        if (empty($videoType) || !array_key_exists($videoType, $services)) {
            return $sSource;
        }

        $s = $services[$videoType];

        // extract videoId
        $playerUri = sprintf($s['uri'], $videoId);

        // display flash player
        return
            '<div style="min-height:'.$s['height'].';min-width:'.$s['width'].';"><object type="application/x-shockwave-flash" data="'.$playerUri.'" width="'.$s['width'].'" height="'.$s['height'].'">'.
            '<param name="movie" value="'.$playerUri.'" />'.
            '<param name="wmode" value="transparent" />'.
            '<param name="allowfullscreen" value="true" />'.
            '</object><br/></div>';

    }

}
