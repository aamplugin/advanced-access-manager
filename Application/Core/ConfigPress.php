<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * ConfigPress layer
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
final class AAM_Core_ConfigPress {
    
    /**
     * Instance of itself
     * 
     * @var AAM_Core_ConfigPress 
     * 
     * @access private
     */
    protected static $instance = null;
    
    /**
     * Parsed config
     * 
     * @var array
     * 
     * @access protected 
     */
    protected $config = null;
    
    /**
     * Raw config text
     * 
     * @var string
     * 
     * @access protected 
     */
    protected $rawConfig = null;
    
    /**
     * Constructor
     * 
     * @return void
     * 
     * @access protected
     */
    protected function __construct() {
        try {
            $reader = new AAM_Core_ConfigPress_Reader;
            $this->config = $reader->parseString($this->read());
        } catch (Exception $e) {
            AAM_Core_Console::add($e->getMessage());
            $this->config = array();
        }
    }
    
    /**
     * Read config from the database
     * 
     * @return string
     * 
     * @access protected
     */
    public function read() {
        $blog   = (defined('BLOG_ID_CURRENT_SITE') ? BLOG_ID_CURRENT_SITE : 1);
        $config = AAM_Core_API::getOption('aam-configpress', 'null', $blog);

        return ($config === 'null' ? '' : $config);
    }

    /**
     * Get configuration option/setting
     * 
     * If $option is defined, return it, otherwise return the $default value
     * 
     * @param string $option
     * @param mixed  $default
     * 
     * @return mixed
     * 
     * @access public
     */
    public static function get($option = null, $default = null) {
        //init config only when requested and only one time
        $instance = self::getInstance();
        
        if (is_null($option)) {
            $value = $instance->config;
        } else {
            $chunks = explode('.', $option);
            $value = $instance->config;
            foreach ($chunks as $chunk) {
                if (isset($value[$chunk])) {
                    $value = $value[$chunk];
                } else {
                    $value = $default;
                    break;
                }
            }
        }
        
        return $value;
    }
    
    /**
     * Get single instance of itself
     * 
     * @return AAM_Core_ConfigPress
     * 
     * @access public
     * @static
     */
    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }
        
        return self::$instance;
    }
    
}