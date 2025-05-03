<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Multisite settings
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Backend_Feature_Settings_Multisite extends AAM_Backend_Feature_Abstract
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
    const TEMPLATE = 'settings/multisite.php';

    /**
     * Get list of options
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public static function getList()
    {
        $configs  = AAM::api()->config;
        $settings = array(
            'core.settings.multisite.members_only' => array(
                'title'       => __('Non-Member Access Restriction', 'advanced-access-manager'),
                'description' => __('Limit subsite access to only members within the WordPress multisite network', 'advanced-access-manager'),
                'value'       => $configs->get('core.settings.multisite.members_only')
            )
        );

        return apply_filters('aam_settings_list_filter', $settings, 'multisite');
    }

    /**
     * Register core settings UI
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object)array(
            'uid'        => 'settings-multisite',
            'position'   => 15,
            'title'      => __('Multisite Settings', 'advanced-access-manager'),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'settings',
            'view'       => __CLASS__
        ));
    }

}