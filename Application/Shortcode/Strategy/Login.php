<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM shortcode strategy for login form
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Shortcode_Strategy_Login implements AAM_Shortcode_Strategy_Interface {
    
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
     *   "id"          => unique form Id
     *   "user-title"  => Logged in user title
     *   "redirect"    => Redirect to URL
     *   "callback"    => callback function that returns the login button
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
     */
    public function run() {
        $this->args['id'] = isset($this->args['id']) ? $this->args['id'] : uniqid();
        
        if (empty($this->args['user-title'])) {
            $this->args['user-title'] = __('Howdy, %username%', AAM_KEY);
        }
        
        if (empty($this->args['redirect'])) {
            $this->args['redirect'] = AAM_Core_Request::get('redirect_to');
        }
        
        if (isset($this->args['callback'])) {
            $content = call_user_func($this->args['callback'], $this);
        } else {
            ob_start();
            require AAM::api()->getConfig(
                'feature.secureLogin.shortcode.template', 
                realpath(AAM_BASEDIR . '/Application/Frontend/phtml/login.phtml')
            );
            $content = ob_get_contents();
            ob_end_clean();
        }
        
        return $content;
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