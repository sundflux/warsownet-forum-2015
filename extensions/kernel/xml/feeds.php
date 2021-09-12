<?php
/*
 * Copyright (c) 2004-2006 Jorma Tuomainen <jt@wiza.fi> All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 *  1. Redistributions of source code must retain the above copyright notice,
 *     this list of conditions and the following disclaimer.
 *  2. Redistributions in binary form must reproduce the above copyright notice,
 *     this list of conditions and the following disclaimer in the documentation
 *     and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY JORMA TUOMAINEN ``AS IS'' AND ANY EXPRESS
 * OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT
 * SHALL JORMA TUOMAINEN OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 */

/**
* This has needed methods for every feed plugin
*/
interface FeedPlugin
{
    public function create($feed);
    public function getHeaders();
}

/**
* FeedBase has only one method(for now) and all plugins extend this
*/
class FeedBase
{
    /**
    * This makes easier to create text element in DOM
    * @param string $name Name of the element
    * @param string $value value of the element
    * @return object text element DOM object
    */
    protected function createTextElement($name, $value)
    {
        $obj = $this->xml->createElement($name);
        if ($name == "description") {
            $obj->appendChild($this->xml->createCDATASection($value));
        } else {
            $obj->appendChild($this->xml->createTextNode($value));
        }

        return $obj;
    }
}

/**
* Main class that includes most of the methods and loads appropriate plugin
*/
class XML_Feeds
{
    public $feed;
    private $plugin;
    private $contenttype;

    // change following variables to match your software if you wish
    private $generator = "libvaloa";
    private $version;

    /**
    * @param string $type Type of feed, currently either RSS or Atom
    */
    public function __construct($type = false)
    {
        if ($type) {
            $this->initFeed($type);
        }
    }

    public function initFeed($type, $read = false)
    {
        $this->feed = new stdClass;
        $this->feed->items = array();
        $this->feed->ttl = 60;
        if (!$read) {
            $this->feed->pubDate = date("r",time());
            $this->feed->swversion = $this->generator;
        }
        if ($type) {
            $class = $type."Feed";
            if (!class_exists($class)) {
                throw new Exception("Selected XML feeds -driver does not exists.");
            }
            $this->plugin = new $class;
        }
        $this->sendheaders = true;
    }

    public function getFeedByURL($url)
    {
        $dom = new DomDocument;
        if (!$dom->load($url)) {
            throw new Exception("Failed to load selected feed.");
        }
        $type = false;
        $version = false;

        if (($item = $dom->getElementsByTagName("rss")->item(0)) !== NULL) {
            $type = "RSS";
            if ($item->hasAttribute("version")) {
                $version = $item->getAttribute("version");
            }
        } elseif (($item = $dom->getElementsByTagName("RDF")->item(0)) !== NULL) {
            // hoping that it's Atom
            $type = "RSS";
            $version = "1.0";
        } elseif (($item = $dom->getElementsByTagName("feed")->item(0)) !== NULL) {
            // hoping that it's Atom
            $type = "Atom";
            $version = "1.0";
        }
        if ($type === false) {
            die("no valid feed");
        }
        echo "Type: {$type} Version: {$version}\n";
        $this->initFeed($type, true);
        $this->plugin->read($dom, $this);
        print_r($this->feed);
    }

    /**
    * Setter, do we automatically send corrent content-type header at the execution of __toString()
    * @param bool $value true=let's send, false=let's not
    */
    public function setSendHeaders($value)
    {
        $this->sendheaders = $value;
    }

    /**
    * Setter for author
    * @param string $value Name of the author
    */
    public function setAuthor($value)
    {
        $this->feed->author = $value;
    }

    /**
    * Getter for author
    * @return string Name of the feed's author
    */
    public function getAuthor()
    {
        return $this->feed->author;
    }

    /**
    * Setter for title
    * @param string $value Title of the feed
    */
    public function setTitle($value)
    {
        $this->feed->title = $value;
    }

    /**
    * Getter for title
    * @return string Title of the feed
    */
    public function getTitle()
    {
        return $this->feed->title;
    }

    /**
    * Setter for link
    * @param string $value Link to the feed
    */
    public function setLink($value)
    {
        $this->feed->link = $value;
    }

    /**
    * Getter for link
    * @return string Link to the feed
    */
    public function getLink()
    {
        return $this->feed->link;
    }

    /**
    * Setter for description
    * @param string $value Description of the feed
    */
    public function setDescription($value)
    {
        $this->feed->description = $value;
    }

    /**
    * Getter for description
    * @return string Description of the feed
    */
    public function getDescription()
    {
        return $this->feed->description;
    }

    /**
    * Setter for copyright
    * @param string $value Copyright message of the feed
    */
    public function setCopyright($value)
    {
        $this->feed->copyright = $value;
    }

    /**
    * Getter for copyright
    * @return string Copyright message of the feed
    */
    public function getCopyright()
    {
        return $this->feed->copyright;
    }

    /**
    * Setter for Time-To-Live
    * @param string $value Time-To-Live value of the feed
    */
    public function setTTL($value)
    {
        $this->feed->ttl = $value;
    }

    /**
    * Getter for Time-To-Live
    * @return string Time-To-Live value of the feed
    */
    public function getTTL()
    {
        return $this->feed->ttl;
    }

    /**
    * Setter for encoding
    * @param string $value Encoding of the feed
    */
    public function setEncoding($value)
    {
        $this->feed->encoding = $value;
    }

    /**
    * Getter for encoding
    * @return string Encoding of the feed
    */
    public function getEncoding()
    {
        return $this->feed->encoding;
    }

    /**
    * Add's FeedItem's to the feed
    * @param object $item instance of FeedItem
    */
    public function addItem(FeedItem $item)
    {
        $this->feed->items[] = $item;
    }

    /**
    * Sends headers(if not requested otherwise) and returns feed as text
    * @return string Feed as a text
    */
    public function __toString()
    {
        if ($this->sendheaders) {
            $this->sendHeaders();
        }

        return $this->plugin->create($this->feed);
    }

    /**
    * Accessor for plugin's getHeaders()
    * @return array Headers
    */
    public function getHeaders()
    {
        return $this->plugin->getHeaders();
    }

    /**
    * Sends headers
    */
    public function sendHeaders()
    {
        $headers = $this->getHeaders();
        foreach ($headers as $key=>$val) {
            header($key.": ".$val);
        }
    }

}

class FeedItem
{
    private $title;
    private $link;
    private $created;
    private $updated;
    private $category = array();
    private $content;

    /**
    * Setter for title
    * @param string $value Title of the item
    */
    public function setTitle($value)
    {
        $this->title = $value;
    }

    /**
    * Getter for title
    * @return string Title of the item
    */
    public function getTitle()
    {
        return $this->title;
    }

    /**
    * Setter for link
    * @param string $value Link to the item
    */
    public function setLink($value)
    {
        $this->link = $value;
    }

    /**
    * Getter for link
    * @return string Link to the item
    */
    public function getLink()
    {
        return $this->link;
    }

    /**
    * Setter for created time
    * @param string $value Time when item was created
    */
    public function setTimeCreated($value)
    {
        $this->created = $value;
    }

    /**
    * Getter for created time
    * @return string Time when item was created
    */
    public function getTimeCreated()
    {
        return $this->created;
    }

    /**
    * Setter for updated time
    * @param string $value Time when item was updated
    */
    public function setTimeUpdated($value)
    {
        $this->updated = $value;
    }

    /**
    * Getter for updated time
    * @return string Time when item was last updated
    */
    public function getTimeUpdated()
    {
        return $this->updated;
    }

    /**
    * Adder for categories
    * @param string $value Category where item belongs
    */
    public function addCategory($value)
    {
        $this->category[] = $value;
    }

    /**
    * Getter for categories
    * @return array Categories where item belongs
    */
    public function getCategories()
    {
        return $this->category;
    }

    /**
    * Setter for content
    * @param string $value Content of item
    */
    public function setContent($value)
    {
        $this->content = $value;
    }

    /**
    * Getter for content
    * @return string Content of item
    */
    public function getContent()
    {
        return $this->content;
    }

}

/**
* RSSFeed plugin class
* extends FeedBase
* implements FeedPlugin
*/
class RSSFeed extends FeedBase implements FeedPlugin
{
    private $contenttype = "application/rss+xml";

    /**
    * Creates the feed
    * @param object $feed Feed data
    * @return string Feed as XML
    */
    public function create($feed)
    {
        $this->feed = $feed;
        $xml = new DomDocument("1.0", $this->feed->encoding);
        $this->xml = $xml;
        $rss = $xml->createElement("rss");
        $rss->setAttribute("version", "2.0");
        $channel = $xml->createElement("channel");
        $channel->appendChild($this->createTextElement("title", $this->feed->title));
        $channel->appendChild($this->createTextElement("link", $this->feed->link));
        $channel->appendChild($this->createTextElement("description", $this->feed->description));
        $channel->appendChild($this->createTextElement("copyright", $this->feed->copyright));
        $channel->appendChild($this->createTextElement("pubDate", $this->feed->pubDate));
        $channel->appendChild($this->createTextElement("ttl", $this->feed->ttl));
        $channel->appendChild($this->createTextElement("generator", $this->feed->swversion));
        foreach ($this->feed->items as $val) {
            $item = $xml->createElement("item");
            $item->appendChild($this->createTextElement("title", $val->getTitle()));
            $item->appendChild($this->createTextElement("link", $val->getLink()));
            $item->appendChild($this->createTextElement("guid", $val->getLink()));
            $item->appendChild($this->createTextElement("pubDate",date("r", $val->getTimeCreated())));
            foreach ($val->getCategories() as $v) {
                $item->appendChild($this->createTextElement("category",$v));
            }
            $item->appendChild($this->createTextElement("description", trim($val->getContent())));
            $channel->appendChild($item);
        }
        $rss->appendChild($channel);
        $xml->appendChild($rss);

        return $xml->saveXML();
    }

    /**
    * Returns headers
    * @return array Headers
    */
    public function getHeaders()
    {
        $headers["Content-Type"] = $this->contenttype;

        return $headers;
    }

    public function read($dom, $parent)
    {
        $feed = $parent;
        $feed->setLink($dom->getElementsByTagName("link")->item(0)->nodeValue);
        $feed->setTitle($dom->getElementsByTagName("title")->item(0)->nodeValue);
        if ($dom->getElementsByTagName("ttl")->item(0) !== NULL) {
            $feed->setTTL($dom->getElementsByTagName("ttl")->item(0)->nodeValue);
        }
        $items = $dom->getElementsByTagName("item");
        $tmps = array();
        foreach ($items as $item) {
            if ($item->hasChildNodes()) {
                $tmp = new FeedItem;
                $tmp->setLink($item->getElementsByTagName("link")->item(0)->nodeValue);
                $tmp->setTitle($item->getElementsByTagName("title")->item(0)->nodeValue);
            }
            $parent->addItem($tmp);
        }
    }

}

/**
* AtomFeed plugin class
* extends FeedBase
* implements FeedPlugin
*/
class AtomFeed extends FeedBase implements FeedPlugin
{
    private $contenttype = "application/atom+xml";

    /**
    * Creates the feed
    * @param object $feed Feed data
    * @return string Feed as XML
    */
    public function create($feed)
    {
        $this->feed = $feed;
        $xml = new DomDocument("1.0", $this->feed->encoding);
        $this->xml = $xml;
        $channel = $xml->createElementNS("http://www.w3.org/2005/Atom","feed");

        $atomlink = $xml->createElement("link");
        $atomlink->setAttribute("rel", "self");
        $atomlink->setAttribute("href", $_SERVER["REQUEST_URI"]);
        $channel->appendChild($atomlink);

        $author = $xml->createElement("author");
        $author->appendChild($this->createTextElement("name", $this->feed->author));
        $channel->appendChild($author);
        $channel->appendChild($this->createTextElement("title", $this->feed->title));
        $channel->appendChild($this->createTextElement("id", $this->feed->link));
        $channel->appendChild($this->createTextElement("subtitle", $this->feed->description));
        $channel->appendChild($this->createTextElement("rights", $this->feed->copyright));
        $channel->appendChild($this->createTextElement("updated", gmdate("Y-m-d\TH:i:s\Z",time())));
        $channel->appendChild($this->createTextElement("generator", $this->feed->swversion));
        foreach ($this->feed->items as $val) {
            $item=$xml->createElement("entry");
            $item->appendChild($this->createTextElement("title", $val->getTitle()));

            foreach ($val->getCategories() as $v) {
                $category = $xml->createElement("category");
                $category->setAttribute("term", $v);
                $item->appendChild($category);
            }

            $item->appendChild($this->createTextElement("id", $val->getLink()));
            $item->appendChild($this->createTextElement("published", gmdate("Y-m-d\TH:i:s\Z",$val->getTimeCreated())));
            $item->appendChild($this->createTextElement("updated", gmdate("Y-m-d\TH:i:s\Z",$val->getTimeUpdated())));

            $div = $xml->createElementNS("http://www.w3.org/1999/xhtml","div");
            $div->appendChild($xml->createTextNode(trim($val->getContent())));
            $description = $xml->createElement("content");
            $description->appendChild($div);
            $description->setAttribute("type", "xhtml");

            $item->appendChild($description);
            $channel->appendChild($item);
        }
        $xml->appendChild($channel);

        return $xml->saveXML();
    }

    /**
    * Returns headers
    * @return array Headers
    */
    public function getHeaders()
    {
        $headers["Content-Type"] = $this->contenttype;

        return $headers;
    }

    public function read($dom, $parent)
    {
        $feed = $parent;
        $tmp = $dom->getElementsByTagName("link");
        foreach ($tmp as $val) {
            if ($val->getAttribute("type") == "text/html" && $val->getAttribute("rel") == "alternate") {
                $feed->setLink($val->getAttribute("href"));
                $feed->setTitle($val->getAttribute("title"));
                break;
            }
        }

        $items = $dom->getElementsByTagName("entry");
        $tmps = array();
        foreach ($items as $item) {
            if ($item->hasChildNodes()) {
                $tmp = new FeedItem;
                foreach ($item->getElementsByTagName("link") as $val) {
                    if ($val->getAttribute("rel") == "alternate") {
                        $tmp->setLink($val->getAttribute("href"));
                        break;
                    }
                }
                $tmp->setTitle($item->getElementsByTagName("title")->item(0)->nodeValue);
            }
            $parent->addItem($tmp);
        }
    }

}
