<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM shortcode strategy interface
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
interface AAM_Shortcode_Strategy_Interface {
    
    /**
     * Initialize shortcode strategy
     * 
     * @param type $args
     * @param type $content
     */
    public function __construct($args, $content);
    
    /**
     * Process strategy
     */
    public function run();
    
}