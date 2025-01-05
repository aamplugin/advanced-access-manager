<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * In-memory object cache
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Utility_ObjectCache implements AAM_Framework_Utility_Interface
{

    use AAM_Framework_Utility_BaseTrait;

    /**
     * @inheritDoc
     */
    protected function __construct() { }

    /**
     * Get cached object
     *
     * @param string|array $key
     *
     * @return mixed
     * @access public
     *
     * @version 7.0.0
     */
    public function get($key)
    {
        $result = wp_cache_get($this->_prepare_cache_key($key), 'aam');

        return !empty($result) ? $result : null;
    }

    /**
     * Set cache value
     *
     * @param string|array $key
     * @param object       $obj
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function set($key, $obj)
    {
        $result = true;

        // Determine if we can use cache
        $enabled = AAM_Framework_Manager::_()->config->get(
            'core.settings.object_cache.enabled',
            defined('AAM_OBJECT_CACHE_ENABLED') ? AAM_OBJECT_CACHE_ENABLED : true
        );

        if ($enabled) {
            $result = wp_cache_set($this->_prepare_cache_key($key), $obj, 'aam');
        }

        return $result;
    }

    /**
     * Reset cache
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function reset()
    {
        return wp_cache_flush_group('aam');
    }

    /**
     * Prepare cache key
     *
     * @param string|array $key
     *
     * @return string
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_cache_key($key)
    {
        // Prepare object cache key
        if (is_array($key)) {
            $bits = [];

            foreach($key as $k) {
                if (!empty($k)) {
                    if (is_object($k)) {
                        if (function_exists('spl_object_hash')) {
                            array_push($bits, spl_object_hash($k));
                        } else {
                            array_push($bits, serialize($k));
                        }
                    } elseif (is_scalar($k)) {
                        array_push($bits, $k);
                    }
                }
            }

            $result = md5(implode('_', $bits));
        } else {
            $result = md5($key);
        }

        return $result;
    }

}