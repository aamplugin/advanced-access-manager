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
 * @since 6.9.28 https://github.com/aamplugin/advanced-access-manager/issues/366
 * @since 6.9.18 https://github.com/aamplugin/advanced-access-manager/issues/329
 * @since 6.9.17 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.28
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
     * @since 6.9.18 https://github.com/aamplugin/advanced-access-manager/issues/329
     * @since 6.9.17 Initial implementation of the method
     *
     * @access public
     * @version 6.9.18
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
        return AAM_Core_API::updateOption(self::DB_OPTION, self::$_cache, false);
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
     * @version 6.9.28
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
        return AAM_Core_API::updateOption(self::DB_OPTION, self::$_cache, false);
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

    /**
     * Load AAM cache
     *
     * @return void
     *
     * @access public
     * @version 6.9.17
     */
    private static function _bootstrap()
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
            AAM_Core_API::updateOption(self::DB_OPTION, self::$_cache, false);
        }
    }

}