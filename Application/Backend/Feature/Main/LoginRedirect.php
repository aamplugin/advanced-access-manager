<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Login redirect
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Feature_Main_LoginRedirect extends AAM_Backend_Feature_Abstract {
    
    /**
     * 
     * @return type
     */
    public function isDefault() {
        $subject = AAM_Backend_Subject::getInstance()->getUID();
        
        return ($subject == AAM_Core_Subject_Default::UID);
    }
    
    /**
     * Check inheritance status
     * 
     * Check if redirect settings are overwritten
     * 
     * @return boolean
     * 
     * @access protected
     */
    protected function isOverwritten() {
        $object = AAM_Backend_Subject::getInstance()->getObject('loginRedirect');
        
        return $object->isOverwritten();
    }
    
    /**
     * 
     * @param type $option
     * @return type
     */
    public function getOption($option, $default = null) {
        $object = AAM_Backend_Subject::getInstance()->getObject('loginRedirect');
        $value  = $object->get($option);
        
        return (!is_null($value) ? $value : $default);
    }
    
    /**
     * @inheritdoc
     */
    public static function getTemplate() {
        return 'main/login-redirect.phtml';
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
            'uid'        => 'login_redirect',
            'position'   => 40,
            'title'      => __('Login Redirect', AAM_KEY),
            'capability' => 'aam_manage_login_redirect',
            'type'       => 'main',
            'subjects'   => array(
                AAM_Core_Subject_Role::UID, 
                AAM_Core_Subject_User::UID, 
                AAM_Core_Subject_Default::UID
            ),
            'view'       => __CLASS__
        ));
    }

}