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

if(!defined('MEMCACHE_USE_PERSISTENT_CONNECTION')) DEFINE('MEMCACHE_USE_PERSISTENT_CONNECTION', 0);
if(!defined('MEMCACHE_HOST')) throw new Exception("MEMCACHE_HOST must be defined.");
if(!defined('MEMCACHE_PORT')) throw new Exception("MEMCACHE_PORT must be defined.");

class cache_memcache implements Cache_IFace
{
    private $target;

    // Cache data
    private $cacheObj = false;

    // Connection
    private $connection = false;

    // Is memcache server actually available?
    private $isAvailable = false;

    public function __construct($target)
    {
        $this->target = $target;

        // Connect to memcache serv
        $this->connection = new Memcache();
        if (MEMCACHE_USE_PERSISTENT_CONNECTION == 1) {
            $this->connection->addServer(MEMCACHE_HOST, MEMCACHE_PORT, true);
        } else {
            $this->connection->addServer(MEMCACHE_HOST, MEMCACHE_PORT, false);
        }

        // Check if memache is available WITHOUT waiting for the connection to timeout.
        $stats = @$this->connection->getExtendedStats();
        $host = MEMCACHE_HOST;
        $port = MEMCACHE_PORT;
        $available = (bool) $stats["$host:$port"];
        if ($available && @$this->connection->connect($host, $port)) {
            $this->isAvailable = true;
        }

        // Read object from cache
        if ($this->isAvailable && !$this->cacheObj) {
            $this->cacheObj = $this->connection->get("Cache_Memcache::{$this->target}");
        }
    }

    public function setTarget($value)
    {
        if (!$this->isAvailable) {
            return false;
        }
        $this->target = $value;

        // Read object from cache (targed changed, reload)
        if ($this->isAvailable && !$this->cacheObj) {
            $this->cacheObj = $this->connection->get("Cache_Memcache::{$this->target}");
        }
    }

    public function update($name, $data, $checksum)
    {
        if (!$this->isAvailable) {
            return false;
        }
        $this->cacheObj[$this->target][$name] = new stdClass;
        $this->cacheObj[$this->target][$name]->checksum = $checksum;
        $this->cacheObj[$this->target][$name]->data = $data;
        $this->connection->set("Cache_Memcache::{$this->target}", $this->cacheObj, false, MEMCACHE_CACHE_TIME);
    }

    public function read($name)
    {
        if (!$this->isAvailable) {
            return false;
        }
        if (isset($this->cacheObj[$this->target][$name])) {
            return $this->cacheObj[$this->target][$name]->data;
        } else {
            return false;
        }
    }

    public function compare($name, $checksum)
    {
        if (!$this->isAvailable) {
            return false;
        }
        if (isset($this->cacheObj[$this->target][$name]) && $this->cacheObj[$this->target][$name]->checksum == $checksum) {
            return true;
        } else {
            return false;
        }
    }

    public function clear($name=false)
    {
        if (!$this->isAvailable) {
            return false;
        }
        if (!$name) {
            unset($this->cacheObj[$this->target]);
        } else {
            unset($this->cacheObj[$this->target][$name]);
        }
    }

}
