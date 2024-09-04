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
class AAM_Framework_Utility_Cache
{

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
    private static $_cache = null;

    /**
     * Get cache value
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     *
     * @access public
     * @version 7.0.0
     */
    public static function get($key, $default = null)
    {
        // Lazy bootstrap
        if (self::$_cache === null) {
            self::_bootstrap();
        }

        if (array_key_exists($key, self::$_cache)) {
            $response = self::$_cache[$key]['value'];
        }

        return (empty($response) ? $default : $response);
    }

    /**
     * Set cache value
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public static function set($key, $value, $ttl = 86400)
    {
        // Lazy bootstrap
        if (self::$_cache === null) {
            self::_bootstrap();
        }

        self::$_cache[$key] = array(
            'value' => $value,
            'ttl'   => time() + $ttl
        );

        $capacity = AAM_Framework_Manager::configs()->get_config(
            'core.settings.cache.capability',
            self::DEFAULT_CACHE_CAPACITY
        );

        if (count(self::$_cache) > $capacity) {
            array_shift(self::$_cache);
        }

        // Save cache to database
        return self::_update(self::$_cache);
    }

    /**
     * Update cache value & ttl
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public static function update($key, $value, $ttl = null)
    {
        // Lazy bootstrap
        if (self::$_cache === null) {
            self::_bootstrap();
        }

        if (array_key_exists($key, self::$_cache)) {
            self::$_cache[$key]['value'] = $value;

            if ($ttl !== null) {
                self::$_cache[$key]['ttl'] = time() + $ttl;
            }
        }

        // Save cache to database
        return self::_update(self::$_cache);
    }

    /**
     * Reset cache
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public static function reset()
    {
        self::$_cache = [];

        if (is_multisite()) {
            delete_blog_option(get_current_blog_id(), self::DB_OPTION);
        } else {
            delete_option(self::DB_OPTION);
        }
    }

    /**
     * Load AAM cache
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    private static function _bootstrap()
    {
        self::$_cache = [];
        $cache        = self::_read_cache();
        $cleared      = false;

        // Self-cleaning
        if (is_array($cache)) {
            foreach($cache as $key => $value) {
                if ($value['ttl'] >= time()) {
                    self::$_cache[$key] = $value;
                } else {
                    $cleared = true;
                }
            }
        }

        if ($cleared) {
            self::_update(self::$_cache);
        }
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
    private static function _read_cache($blog_id = null)
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
     * @param int   $blog_id
     *
     * @return bool
     *
     * @access private
     * @version 7.0.0
     */
    private static function _update($data, $blog_id = null)
    {
        $old_value = self::_read_cache($blog_id);

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