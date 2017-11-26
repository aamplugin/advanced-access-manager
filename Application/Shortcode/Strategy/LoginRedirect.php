<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM shortcode strategy for login button
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Shortcode_Strategy_LoginRedirect implements AAM_Shortcode_Strategy_Interface {
    
    /**
     *
     * @var type 
     */
    protected $args;
    
    /**
     *
     * @var type 
     */
    protected $content;
    
    /**
     * Initialize shortcode decorator
     * 
     * Expecting attributes in $args are:
     *   "class"    => CSS class for login button
     *   "label"    => Login button label
     *   "callback" => callback function that returns the login button
     * 
     * @param type $args
     * @param type $content
     */
    public function __construct($args, $content) {
        $this->args    = $args;
        $this->content = $content;
    }
    
    /**
     * Process shortcode
     * 
     */
    public function run() {
        $redirect = AAM_Core_Request::server('REQUEST_URI');
        $class    = (isset($this->args['class']) ? $this->args['class'] : '');
        $label    = (isset($this->args['label']) ? $this->args['label'] : 'Login');
        
        if (isset($this->args['callback'])) {
            $button = call_user_func($this->args['callback'], $this);
        } else {
            $url = add_query_arg(
                    'reason',
                    'access-denied',
                    wp_login_url($redirect)
            );
            
            $button  = '<a href="' . $url . '" ';
            $button .= 'class="' . $class . '">' . $label . '</a>';
        }
        
        return $button;
    }
    
    /**
     * 
     * @return type
     */
    public function getArgs() {
        return $this->args;
    }
    
    /**
     * 
     * @return type
     */
    public function getContent() {
        return $this->content;
    }
    
}