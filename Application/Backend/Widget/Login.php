<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

class AAM_Backend_Widget_Login extends WP_Widget {

    public $args = array();
    
    /**
     * 
     */
    public function __construct() {
        $options = array(
            'description' => __( "AAM Secure Login Widget", AAM_KEY) 
        );
        
        parent::__construct(false, 'AAM Secure Login', $options);
    }
    
    /**
     * 
     * @param type $args
     * @param type $instance
     */
    public function widget($args, $instance) {
        $this->args = array_merge($args, $this->normalize($instance));
        
        require AAM_Core_Config::get(
            'feature.secureLogin.widget.template',
            realpath(dirname(__FILE__) . '/../phtml/widget/login-frontend.phtml')
        );
    }
    
    /**
     * 
     * @param type $instance
     */
    public function form($instance) {
        $instance = $this->normalize($instance);
        
        require dirname(__FILE__) . '/../phtml/widget/login-backend.phtml';
    }
    
    /**
     * 
     * @param type $instance
     * @return type
     */
    protected function normalize($instance) {
        $instance['login-title'] = AAM_Core_Config::get('login-title');
        
        if (empty($instance['login-title'])) {
            $instance['login-title'] = __('Login', AAM_KEY);
        }
        
        if (empty($instance['user-title'])) {
            $instance['user-title'] = __('Howdy, %username%', AAM_KEY);
        }
        
        $instance['redirect'] = AAM_Core_Request::get('redirect_to');
        
        return $instance;
    }
    
}