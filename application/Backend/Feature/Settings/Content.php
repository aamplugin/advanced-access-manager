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
 * @version 7.0.0
 */
class AAM_Backend_Feature_Settings_Content extends AAM_Backend_Feature_Abstract
{

    /**
     * Default access capability to the collection of settings
     *
     * @version 7.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_settings';

    /**
     * HTML template to render
     *
     * @version 7.0.0
     */
    const TEMPLATE = 'settings/content.php';

    /**
     * Get list of content options
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public static function getList()
    {
        return apply_filters('aam_settings_list_filter', [], 'content');
    }

    /**
     * Register service UI
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object)array(
            'uid'        => 'settings-content',
            'position'   => 5,
            'title'      => __('Content Settings', 'advanced-access-manager'),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'settings',
            'view'       => __CLASS__
        ));
    }

}