<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM Core Cache
 *
 * AAM own caching solution to avoid using WP core transients. Some plugins disable
 * WP transients, so this is a work around.
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Utility_Cache implements AAM_Framework_Utility_Interface
{

    use AAM_Framework_Utility_BaseTrait;

    /**
     * Maximum number of keys to keep in cache
     *
     * @version 7.0.0
     */
    const DEFAULT_CACHE_CAPACITY = 1000;

    /**
     * Core AAM cache db option
     *
     * @version 7.0.0
     */
    const DB_OPTION = 'aam_cache';

    /**
     * Core cache
     *
     * @var array
     *
     * @access protected
     * @version 7.0.0
     */
    private $_cache = null;

    /**
     * @inheritDoc
     */
    protected function __construct()
    {
       $this->_cache = []; // Reset the cache

        $cache   = $this->_read_cache();
        $cleared = false;

        // Self-cleaning
        if (is_array($cache)) {
            foreach($cache as $key => $value) {
                if ($value['ttl'] >= time()) {
                    $this->_cache[$key] = $value;
                } else {
                    $cleared = true;
                }
            }
        }

        if ($cleared) {
            $this->_update($this->_cache);
        }
    }

    /**
     * Get cache value
     *
     * @param string $key
     * @param mixed  $default [Optional]
     *
     * @return mixed
     * @access public
     *
     * @version 7.0.0
     */
    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->_cache)) {
            $result = $this->_cache[$key]['value'];
        } else {
            $result = null;
        }

        return (is_null($result) ? $default : $result);
    }

    /**
     * Set cache value
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl   [Optional]
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function set($key, $value, $ttl = 86400)
    {
       $this->_cache[$key] = array(
            'value' => $value,
            'ttl'   => time() + $ttl
        );

        $capacity = AAM::api()->config->get(
            'core.settings.cache.capability',
            self::DEFAULT_CACHE_CAPACITY
        );

        if (count($this->_cache) > $capacity) {
            array_shift($this->_cache);
        }

        // Save cache to database
        return $this->_update($this->_cache);
    }

    /**
     * Update cache value & ttl
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl   [Optional]
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function update($key, $value, $ttl = null)
    {
        if (array_key_exists($key, $this->_cache)) {
            $this->_cache[$key]['value'] = $value;

            if ($ttl !== null) {
                $this->_cache[$key]['ttl'] = time() + $ttl;
            }
        }

        // Save cache to database
        return $this->_update($this->_cache);
    }

    /**
     * Reset cache
     *
     * @return bool
     *
     * @access public
     * @version 7.0.0
     */
    public function reset()
    {
        $this->_cache = [];

        if (is_multisite()) {
            $result = delete_blog_option(get_current_blog_id(), self::DB_OPTION);
        } else {
            $result = delete_option(self::DB_OPTION);
        }

        return $result;
    }

    /**
     * Get cache from the database
     *
     * @param int $blog_id
     *
     * @return mixed
     *
     * @access private
     * @version 7.0.0
     */
    private function _read_cache($blog_id = null)
    {
        if (is_multisite()) {
            $result = get_blog_option(
                ($blog_id ? $blog_id : get_current_blog_id()), self::DB_OPTION, []
            );
        } else {
            $result = get_option(self::DB_OPTION, []);
        }

        return $result;
    }

    /**
     * Update cache in the DB
     *
     * @param mixed $data
     * @param int   $blog_id [Optional]
     *
     * @return bool
     *
     * @access private
     * @version 7.0.0
     */
    private function _update($data, $blog_id = null)
    {
        $old_value = $this->_read_cache($blog_id);

        if (maybe_serialize($old_value) !== maybe_serialize($data)) {
            if (is_multisite()) {
                $result = update_blog_option(
                    ($blog_id ? $blog_id : get_current_blog_id()),
                    self::DB_OPTION,
                    $data
                );
            } else {
                $result = update_option(self::DB_OPTION, $data, false);
            }
        } else {
            $result = true;
        }

        return $result;
    }

}