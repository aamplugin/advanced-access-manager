<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend feature abstract
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
abstract class AAM_Backend_Feature_Abstract {
    
    /**
     * Constructor
     * 
     * @return void
     * 
     * @access public
     * @throws Exception
     */
    public function __construct() {
        if (!current_user_can('aam_manager')) {
            AAM_Core_API::reject(
                'backend', array('hook' => 'aam_manager')
            );
        }
    }
    
    /**
     * Get HTML content
     * 
     * @return string
     * 
     * @access public
     */
    public function getContent() {
        ob_start();
        require_once(dirname(__FILE__) . '/../phtml/' . $this->getTemplate());
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }
    
    /**
     * Get template filename
     * 
     * This function exists only to support implementation for PHP 5.2 cause later
     * static binding has been introduced only in PHP 5.3.0
     * 
     * @return string
     * 
     * @access public
     */
    public static function getTemplate() { 
        return '';
    }
    
    /**
     * Register feature
     * 
     * @return void
     * 
     * @access public
     */
    public static function register() { }
    
}