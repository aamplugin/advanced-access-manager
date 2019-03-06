<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend security settings
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Feature_Settings_Security extends AAM_Backend_Feature_Abstract {
    
    /**
     * Construct
     */
    public function __construct() {
        parent::__construct();
        
        if (!current_user_can('aam_manage_settings')) {
            AAM::api()->denyAccess(array('reason' => 'aam_manage_settings'));
        }
    }
    
    /**
     * @inheritdoc
     */
    public static function getTemplate() {
        return 'settings/security.phtml';
    }
    
    /**
     * 
     * @return type
     */
    protected function getList() {
        $settings = array(
            'core.settings.loginTimeout' => array(
                'title' => __('Login Timeout', AAM_KEY),
                'descr' => sprintf(AAM_Backend_View_Helper::preparePhrase('Delay the login process for 1 second (the value is configurable) to significantly reduce the chance for brute force or dictionary attack. For more information about this option please refer to %sHow does AAM Secure Login works%s.', 'strong', 'strong'), '<a href="https://aamplugin.com/article/how-does-aam-secure-login-works" target="_blank">', '</a>'),
                'value' => AAM_Core_Config::get('core.settings.loginTimeout', false)
            ),
            'core.settings.loginTimeout' => array(
                'title' => __('Login Timeout', AAM_KEY),
                'descr' => sprintf(AAM_Backend_View_Helper::preparePhrase('Delay the login process for 1 second (the value is configurable) to significantly reduce the chance for brute force or dictionary attack. For more information about this option please refer to %sHow does AAM Secure Login works%s.', 'strong', 'strong'), '<a href="https://aamplugin.com/article/how-does-aam-secure-login-works" target="_blank">', '</a>'),
                'value' => AAM_Core_Config::get('core.settings.loginTimeout', false)
            ),
            'core.settings.singleSession' => array(
                'title' => __('One Session Per User', AAM_KEY),
                'descr' => sprintf(AAM_Backend_View_Helper::preparePhrase('Automatically destroy all other sessions for a user if he/she tries to login from different location. For more information about this option please refer to %sHow does AAM Secure Login works%s.', 'strong', 'strong'), '<a href="https://aamplugin.com/article/how-does-aam-secure-login-works" target="_blank">', '</a>'),
                'value' => AAM_Core_Config::get('core.settings.singleSession', false)
            ),
            'core.settings.bruteForceLockout' => array(
                'title' => __('Brute Force Lockout', AAM_KEY),
                'descr' => sprintf(AAM_Backend_View_Helper::preparePhrase('Automatically reject login attempts if number of unsuccessful login attempts is more than 20 over the period of 2 minutes (both values are configurable). For more information about this option please refer to %sHow does AAM Secure Login works%s.', 'strong', 'strong'), '<a href="https://aamplugin.com/article/how-does-aam-secure-login-works" target="_blank">', '</a>'),
                'value' => AAM_Core_Config::get('core.settings.bruteForceLockout', false)
            ),
        );
        
        return apply_filters('aam-settings-filter', $settings, 'security');
    }
    
    /**
     * Register Contact/Hire feature
     * 
     * @return void
     * 
     * @access public
     */
    public static function register() {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'settings-security',
            'position'   => 6,
            'title'      => __('Security Settings', AAM_KEY),
            'capability' => 'aam_manage_settings',
            'type'       => 'settings',
            'view'       => __CLASS__
        ));
    }

}