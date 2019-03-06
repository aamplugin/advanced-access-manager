<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend content settings
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Backend_Feature_Settings_Content extends AAM_Backend_Feature_Abstract {
    
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
        return 'settings/content.phtml';
    }
    
    /**
     * 
     * @return type
     */
    protected function getList() {
        $settings = array(
            'core.settings.mediaAccessControl' => array(
                'title' => __('Media Files Access Control', AAM_KEY),
                'descr' => sprintf(AAM_Backend_View_Helper::preparePhrase('Allow AAM to manage a physically access to all media files located in the defined by the system [uploads] folder. [Note!] This feature requires additional steps as described in %sthis article%s.', 'strong', 'strong'), '<a href="https://aamplugin.com/article/how-to-manage-wordpress-media-access" target="_blank">', '</a>'),
                'value' => AAM_Core_Config::get('core.settings.mediaAccessControl', false)
            ),
            'core.settings.manageHiddenPostTypes' => array(
                'title' => __('Manage Hidden Post Types', AAM_KEY),
                'descr' => __('By default AAM allows you to manage access only to public post types on Posts & Terms tab. By enabling this feature, you also will be able to manage access to hidden post types like revisions, navigation menus or any other custom post types that are not registered as public.', AAM_KEY),
                'value' => AAM_Core_Config::get('core.settings.manageHiddenPostTypes', false)
            )
        );
        
        return apply_filters('aam-settings-filter', $settings, 'post');
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
            'uid'        => 'settings-content',
            'position'   => 5,
            'title'      => __('Content Settings', AAM_KEY),
            'capability' => 'aam_manage_settings',
            'type'       => 'settings',
            'view'       => __CLASS__
        ));
    }

}