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
 * 2005,2007 Tarmo Alexander Sundström <ta@sundstrom.im>
 * 2005 Joni Halme <jontsa@angelinecms.info>
 *
 * The Initial Developer of the Original Code is
 * Tarmo Alexander Sundström <ta@sundstrom.im>
 *
 * Portions created by the Initial Developer are Copyright (C) 2005
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * 2007 Mikko Ruohola <polarfox@polarfox.net>
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
 * Cache library.
 *
 * @todo          timed cache purge
 * @package       Kernel
 * @subpackage    Common
 */

class Cache
{
    private $driver=false;

    /**
     * Constructor.
     *
     * Creates cache array in session and stores pointer to self::cache.
     *
     * @access public
     * @param string $target Cache target name
     */
    public function __construct($target = false, $driver = false)
    {
        if (!defined('CACHE_DRIVER')) {
            return;
        }
        if (!$driver) {
            $driver = "Cache_".CACHE_DRIVER;
        }
        if (!class_exists($driver)) {
            return;
        }
        $target = $target ? $target : "__DEFAULT__";
        $this->driver = new $driver($target);
    }

    /**
     * Set cache target.
     *
     * Target is used to distinct caches so that if two components that use cache library store
     * cache named 'foo', they do not overwrite each other.
     * Target can also be passed to constructor as parameter.
     *
     * @access public
     * @param string $target Cache target name
     */
    public function setTarget($value)
    {
        if ($this->driver) {
            $this->driver->setTarget($value);
        }
    }

    /**
     * Updates/inserts data to cache.
     *
     * Adds data to cache. If data with same name allready exists, it will be overwritten.
     *
     * @access public
     * @param string $name     Cache name fe 'mymp3database'
     * @param mixed  $data     Data to cache
     * @param mixed  $checksum Checksum can be anything that you can use to fe check that cached data is up-to-date
     */
    public function update($name, $data, $checksum)
    {
        if ($this->driver) {
            return $this->driver->update($name, $data, $checksum);
        }
    }

    /**
     * Reads data from cache.
     *
     * @access public
     * @param  string $name Cache name fe 'mymp3database'
     * @return mixed  Cache contents or false if not found
     */
    public function read($name)
    {
        if ($this->driver) {
            return $this->driver->read($name);
        }
    }

    /**
     * Compare cache checksum.
     *
     * Use this method to check wether or not your checksum matches the one stored to cache.
     *
     * @access public
     * @param  string $name     Cache name
     * @param  mixed  $checksum Your checksum
     * @return bool   Either true if your checksum is the same as the one stored cache, otherwise false
     */
    public function compare($name, $checksum)
    {
        if ($this->driver) {
            return $this->driver->compare($name, $checksum);
        }
    }

    /**
     * Clears parts of cache or whole cache.
     * @access public
     * @param mixed $name Either cache name or false to clear entire target cache
     */
    public function clear($name = false)
    {
        if ($this->driver) {
            return $this->driver->clear($name);
        }
    }

}
