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
 * @since 6.9.34 https://github.com/aamplugin/advanced-access-manager/issues/395
 * @since 6.9.32 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.34
 */
class AAM_Backend_Feature_Settings_Multisite extends AAM_Backend_Feature_Abstract
{

    /**
     * Default access capability to the collection of settings
     *
     * @version 6.9.32
     */
    const ACCESS_CAPABILITY = 'aam_manage_settings';

    /**
     * HTML template to render
     *
     * @version 6.9.32
     */
    const TEMPLATE = 'settings/multisite.php';

    /**
     * Get list of options
     *
     * @return array
     *
     * @since 6.9.34 https://github.com/aamplugin/advanced-access-manager/issues/395
     * @since 6.9.32 Initial implementation of the method
     *
     * @access public
     * @version 6.9.34
     */
    public static function getList()
    {
        $service  = AAM_Framework_Manager::configs();
        $settings = array(
            'multisite.settings.sync' => array(
                'title'       => __('Unified Multisite Configuration Sync', AAM_KEY),
                'description' => __('Effortlessly synchronize role and capability lists, along with all access settings (when configured)', AAM_KEY),
                'value'       => $service->get_config('multisite.settings.sync')
            ),
            'multisite.settings.nonmember' => array(
                'title'       => __('Non-Member Access Restriction', AAM_KEY),
                'description' => __('Limit subsite access to only members within the WordPress multisite network', AAM_KEY),
                'value'       => $service->get_config('multisite.settings.nonmember')
            )
        );

        return apply_filters('aam_settings_list_filter', $settings, 'multisite');
    }

    /**
     * Register core settings UI
     *
     * @return void
     *
     * @access public
     * @version 6.9.32
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object)array(
            'uid'        => 'settings-multisite',
            'position'   => 15,
            'title'      => __('Multisite Settings', AAM_KEY),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'settings',
            'view'       => __CLASS__
        ));
    }

}