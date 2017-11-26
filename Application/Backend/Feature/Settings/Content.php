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
            'media-access-control' => array(
                'title' => __('Media Files Access Control', AAM_KEY),
                'descr' => sprintf(AAM_Backend_View_Helper::preparePhrase('Allow AAM to manage a physically access to all media files located in the defined by the system [uploads] folder. [Note!] This feature requires additional steps as described in %sthis article%s.', 'strong', 'strong'), '<a href="https://aamplugin.com/help/how-to-manage-wordpress-media-access" target="_blank">', '</a>'),
                'value' => AAM_Core_Config::get('media-access-control', false)
            ),
            'check-post-visibility' => array(
                'title' => __('Check Post Visibility', AAM_KEY),
                'descr' => __('For performance reasons, keep this option uncheck if do not use LIST or LIST TO OTHERS access options on Posts & Pages tab. When it is checked, AAM will filter list of posts that are hidden for a user on both frontend and backend.', AAM_KEY),
                'value' => AAM_Core_Config::get('check-post-visibility', true)
            ),
            'manage-hidden-post-types' => array(
                'title' => __('Manage Hidden Post Types', AAM_KEY),
                'descr' => __('By default AAM allows you to manage access only to public post types on Posts & Pages tab. By enabling this feature, you also will be able to manage access to hidden post types like revisions, navigation menus or any other custom post types that are not registered as public.', AAM_KEY),
                'value' => AAM_Core_Config::get('manage-hidden-post-types', false)
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