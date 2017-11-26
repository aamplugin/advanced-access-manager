<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM Core Config
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Config {
    
    /**
     * Core settings database option
     * 
     * aam-utilities slug is used because AAM Utilities with v3.4 became a core
     * feature instead of independent extension.
     */
    const OPTION = 'aam-utilities';
    
    /**
     * Core config
     * 
     * @var array
     * 
     * @access protected 
     */
    protected static $config = array();
    
    /**
     * Load core AAM settings
     * 
     * @return void
     * 
     * @access public
     */
    public static function bootstrap() {
        if (is_multisite()) {
            self::$config = AAM_Core_API::getOption(self::OPTION, array(), 'site');
        } else {
            self::$config = AAM_Core_Compatibility::getConfig();
        }
    }
    
    /**
     * Get config option
     * 
     * @param string $option
     * @param mixed  $default
     * 
     * @return mixed
     * 
     * @access public
     * @static
     */
    public static function get($option, $default = null) {
        if (isset(self::$config[$option])) {
            $response = self::$config[$option];
        } else {
            $response = self::readConfigPress($option, $default);
        }
        
        return self::normalize(
                apply_filters('aam-filter-config-get', $response, $option
        ));
    }
    
    /**
     * 
     * @param type $setting
     * @return type
     */
    protected static function normalize($setting) {
        return str_replace(
                array('{ABSPATH}'),
                array(ABSPATH),
                $setting
        );
    }
    
    /**
     * Set config
     * 
     * @param string $option
     * @param mixed  $value
     * 
     * @return boolean
     * 
     * @access public
     */
    public static function set($option, $value) {
        self::$config[$option] = $value;
        
        //save config to database
        if (is_multisite()) {
            $result = AAM_Core_API::updateOption(self::OPTION, self::$config, 'site');
        } else {
            $result = AAM_Core_API::updateOption(self::OPTION, self::$config);
        }
        
        
        return $result;
    }
    
    /**
     * 
     * @param type $option
     */
    public static function delete($option) {
        if (isset(self::$config[$option])) {
            unset(self::$config[$option]);
            if (is_multisite()) {
                AAM_Core_API::updateOption(self::OPTION, self::$config, 'site');
            } else {
                AAM_Core_API::updateOption(self::OPTION, self::$config);
            }
        }
    }
    
    /**
     * Get ConfigPress parameter
     * 
     * @param string $param
     * @param mixed  $default
     * 
     * @return mixed
     * 
     * @access public
     * @static
     */
    protected static function readConfigPress($param, $default = null) {
        if (defined('AAM_CONFIGPRESS')) {
            $config = AAM_ConfigPress::get('aam.' . $param, $default);
        } elseif (class_exists('ConfigPress')) {
            $config = ConfigPress::get('aam.' . $param, $default);
        } else {
            $config = $default;
        }

        if (is_array($config) && isset($config['userFunc'])) {
            if (is_callable($config['userFunc'])) {
                $response = call_user_func($config['userFunc']);
            } else {
                $response = $default;
            }
        } else {
            $response = $config;
        }

        return $response;
    }

}