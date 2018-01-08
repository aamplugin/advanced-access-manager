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
     * DB Cache option
     */
    const CACHE_OPTION = 'aam-cache';
    
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
        return (isset(self::$cache[$option]) ? self::$cache[$option] : $default);
    }
    
    /**
     * Set cache option
     * 
     * @param string           $subject
     * @param string           $option
     * @param mixed            $data
     * 
     * @return void
     * 
     * @access public
     */
    public static function set($subject, $option, $data) {
        if (!isset(self::$cache[$option]) || (self::$cache[$option] != $data)) {
            self::$cache[$option] = $data;
            self::$updated        = true;
        }
    }
    
    /**
     * 
     * @param type $option
     * @return type
     */
    public static function has($option) {
        return (isset(self::$cache[$option]));
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
            $oquery = "DELETE FROM {$wpdb->options} WHERE `option_name` = %s";
            $wpdb->query($wpdb->prepare($oquery, 'aam_visitor_cache' ));

            //clear all users cache
            $mquery = "DELETE FROM {$wpdb->usermeta} WHERE `meta_key` = %s";
            $wpdb->query($wpdb->prepare($mquery, self::CACHE_OPTION));
        } else {
            $query  = "DELETE FROM {$wpdb->usermeta} WHERE (`user_id` = %d) AND ";
            $query .= "`meta_key` = %s";
            $wpdb->query($wpdb->prepare($query, $user, self::CACHE_OPTION));
        }
        
        self::$cache = array();
        
        //clear updated flag
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
        if (self::$updated) {
            update_user_meta(AAM::getUser()->ID, self::CACHE_OPTION, self::$cache);
        }
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
            $cache = get_user_meta(AAM::getUser()->ID, self::CACHE_OPTION, true);
            self::$cache = (is_array($cache) ? $cache : array());
            
            add_action('shutdown', 'AAM_Core_Cache::save');
        }
    }
    
}