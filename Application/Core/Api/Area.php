<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM core API Area class
 * 
 * This class defines what area AAM is operating on. Can be backend, frontend, rest
 * etc.
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
final class AAM_Core_Api_Area {
    
    /**
     * 
     */
    const BACKEND = "backend";
    
    /**
     * 
     */
    const FRONTEND = "frontend";
    
    /**
     * 
     */
    const API = "api";
    
    /**
     * Get operating area
     * 
     * @return string
     * 
     * @access public
     * @static
     */
    public static function get() {
        if (defined('REST_REQUEST') && REST_REQUEST) {
            $area = self::API;
        } elseif (is_admin()) {
            $area = self::BACKEND;
        } else {
            $area = self::FRONTEND;
        }
        
        return $area;
    }
    
    /**
     * 
     * @return type
     */
    public static function isBackend() {
        return self::get() === self::BACKEND;
    }
    
    /**
     * 
     * @return type
     */
    public static function isFrontend() {
        return self::get() === self::FRONTEND;
    }
    
    /**
     * 
     * @return type
     */
    public static function isAPI() {
        return self::get() === self::API;
    }
}