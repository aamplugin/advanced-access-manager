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
 * @todo - Remove May 2019
 */
class AAM_Core_Cache {
    
    /**
     * Get cached option
     * 
     * @param string $option
     * 
     * @return mixed
     * 
     * @access public
     */
    public static function get() {
        return null;
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
    public static function set() {
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
    public static function has() {
        return null;
    }
    
    /**
     * 
     */
    public static function clear() {
        AAM_Core_API::clearCache();
    }
}