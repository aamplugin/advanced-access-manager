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
 * Implementing its own cache because WP core cache clones object and this causes
 * data integrity issue between objects
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Utility_ObjectCache implements AAM_Framework_Utility_Interface
{

    use AAM_Framework_Utility_BaseTrait;

    /**
     * Default object cache capacity
     *
     * @version 7.0.0
     */
    const DEFAULT_CAPACITY = 250;

    /**
     * Internal cache
     *
     * @var array
     * @access private
     *
     * @version 7.0.0
     */
    private $_cache = [];

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
        $cache_key = $this->_prepare_cache_key($key);

        return !empty($this->_cache[$cache_key]) ? $this->_cache[$cache_key] : null;
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
        // Determine if we can use cache
        $enabled = AAM_Framework_Manager::_()->config->get(
            'core.settings.object_cache.enabled',
            defined('AAM_OBJECT_CACHE_ENABLED') ? AAM_OBJECT_CACHE_ENABLED : true
        );

        if ($enabled) {
            $cache_key = $this->_prepare_cache_key($key);

            $this->_cache[$cache_key] = $obj;

            if (count($this->_cache) > self::DEFAULT_CAPACITY) {
                array_shift($this->_cache);
            }
        }

        return true;
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
        $this->_cache = [];

        return true;
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
                    } elseif (is_array($k)) {
                        array_push($bits, serialize($k));
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