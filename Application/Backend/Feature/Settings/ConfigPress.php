<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend ConfigPress
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Feature_Settings_ConfigPress extends AAM_Backend_Feature_Abstract {
    
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
        return 'settings/configpress.phtml';
    }
    
    /**
     * Save config
     * 
     * @return boolean
     * 
     * @access protected
     */
    public function save() {
        $blog   = (defined('BLOG_ID_CURRENT_SITE') ? BLOG_ID_CURRENT_SITE : 1);
        $config = filter_input(INPUT_POST, 'config');
        
        //normalize
        $data = str_replace(array('“', '”'), '"', $config);
        
        return AAM_Core_API::updateOption('aam-configpress', $data, $blog);
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
            'uid'        => 'configpress',
            'position'   => 90,
            'title'      => __('ConfigPress', AAM_KEY),
            'capability' => 'aam_manage_settings',
            'type'       => 'settings',
            'subjects'   => array(
                AAM_Core_Subject_Role::UID, 
                AAM_Core_Subject_User::UID, 
                AAM_Core_Subject_Visitor::UID, 
                AAM_Core_Subject_Default::UID
            ),
            'view'       => __CLASS__
        ));
    }

}