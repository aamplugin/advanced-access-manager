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
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Cache {
    
    /**
     * DB post cache option
     */
    const POST_CACHE= 'aam_post_cache_user';
    
    /**
     * Core config
     * 
     * @var array
     * 
     * @access protected 
     */
    protected static $cache = array();
    
    /**
     * Update cache flag
     * 
     * @var boolean
     * 
     * @access protected 
     */
    protected static $updated = false;
    
    /**
     * Get cached option
     * 
     * @param string $option
     * 
     * @return mixed
     * 
     * @access public
     */
    public static function get($option, $default = null) {
        return (self::has($option) ? self::$cache[$option] : $default);
    }
    
    /**
     * Set cache option
     * 
     * @param string $option
     * @param mixed  $data
     * @param mixed  $legacy Deprecated as the first arg was subject
     * 
     * @return void
     * 
     * @access public
     */
    public static function set($option, $data, $legacy = null) {
        // TODO - Compatibility. Remove Apr 2019
        $key = (is_scalar($option) ? $option : $data);
        $val = (is_scalar($option) ? $data : $legacy);
        
        if (!self::has($key) || (self::$cache[$key] != $val)) {
            self::$cache[$key] = $val;
            self::$updated     = true;
        }
    }
    
    /**
     * Check if key exists
     * 
     * @param string $option
     * 
     * @return boolean
     * 
     * @access public
     */
    public static function has($option) {
        return is_array(self::$cache) && array_key_exists($option, self::$cache);
    }
    
    /**
     * Clear cache
     * 
     * @return void
     * 
     * @access public
     * @global WPDB $wpdb
     */
    public static function clear($user = null) {
        global $wpdb;
        
        if (is_null($user)) {
            //clear visitor cache
            $query = "DELETE FROM {$wpdb->options} WHERE `option_name` LIKE %s";
            $wpdb->query($wpdb->prepare($query, '_transient_aam_%' ));
        } else {
            delete_transient(self::getCacheOption($user));
        }
        
        //reset cache
        self::$cache = array();
        self::$updated = false;
    }
    
    /**
     * Save cache
     * 
     * Save aam cache but only if changes deleted
     * 
     * @return void
     * 
     * @access public
     */
    public static function save() {
        if (self::$updated === true) {
            set_transient(self::getCacheOption(), self::$cache);
        }
    }
    
    /**
     * 
     * @return type
     */
    protected static function getCacheOption($id = null) {
        $option = self::POST_CACHE . '_';
        
        if (is_null($id)) {
            $option .= AAM::getUser()->isVisitor() ? 'visitor' : AAM::getUser()->ID;
        } else {
            $option .= $id;
        }
        
        return $option;
    }
    
    /**
     * Bootstrap cache
     * 
     * Do not load cache if user is on AAM page
     * 
     * @return void
     * 
     * @access public
     */
    public static function bootstrap() {
        if (!AAM::isAAM()) {
            $cache = get_transient(self::getCacheOption());
            self::$cache = (is_array($cache) ? $cache : array());
            
            add_action('shutdown', 'AAM_Core_Cache::save');
            add_filter('aam-get-cache-filter', 'AAM_Core_Cache::get', 10, 2);
            add_action('aam-set-cache-action', 'AAM_Core_Cache::set', 10, 2);
            add_action('aam-clear-cache-action', 'AAM_Core_Cache::clear');
        }
    }
    
}