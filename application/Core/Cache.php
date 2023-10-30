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
 * @version 6.9.17
 */
class AAM_Core_Cache
{

    /**
     * Maximum number of keys to keep in cache
     *
     * @version 6.9.17
     */
    const DEFAULT_CACHE_CAPACITY = 1000;

    /**
     * Core AAM cache db option
     *
     * @version 6.9.17
     */
    const DB_OPTION = 'aam_cache';

    /**
     * Core cache
     *
     * @var array
     *
     * @access protected
     * @version 6.9.17
     */
    private static $_cache = null;

    /**
     * Load AAM cache
     *
     * @return void
     *
     * @access public
     * @version 6.9.17
     */
    public static function bootstrap()
    {
        self::$_cache = array();
        $cache        = AAM_Core_API::getOption(self::DB_OPTION, array());
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
            AAM_Core_API::updateOption(self::DB_OPTION, self::$_cache);
        }
    }

    /**
     * Get cache value
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     *
     * @access public
     * @version 6.9.17
     */
    public static function get($key, $default = null)
    {
        // Lazy bootstrap
        if (self::$_cache === null) {
            self::bootstrap();
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
     * @version 6.9.17
     */
    public static function set($key, $value, $ttl = 86400)
    {
        self::$_cache[$key] = array(
            'value' => $value,
            'ttl'   => time() + $ttl
        );

        $capacity = AAM_Core_Config::get(
            'core.settings.cache.capability',
            self::DEFAULT_CACHE_CAPACITY
        );

        if (count(self::$_cache) > $capacity) {
            array_shift(self::$_cache);
        }

        // Save cache to database
        return AAM_Core_API::updateOption(self::DB_OPTION, self::$_cache);
    }

    /**
     * Reset cache
     *
     * @return void
     *
     * @access public
     * @version 6.9.17
     */
    public static function reset()
    {
        self::$_cache = array();

        AAM_Core_API::deleteOption(self::DB_OPTION);
    }

}