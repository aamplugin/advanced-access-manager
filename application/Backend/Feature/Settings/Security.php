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
 * @since 6.9.34 https://github.com/aamplugin/advanced-access-manager/issues/395
 * @since 6.9.14 https://github.com/aamplugin/advanced-access-manager/issues/308
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.34
 */
class AAM_Backend_Feature_Settings_Security extends AAM_Backend_Feature_Abstract
{

    /**
     * Default access capability to the collection of settings
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_settings';

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = 'settings/security.php';

    /**
     * Get list of security options
     *
     * @return array
     *
     * @since 6.9.34 https://github.com/aamplugin/advanced-access-manager/issues/395
     * @since 6.9.14 https://github.com/aamplugin/advanced-access-manager/issues/308
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.34
     */
    public static function getList()
    {
        $service  = AAM_Framework_Manager::configs();
        $settings = array(
            'service.secureLogin.feature.singleSession' => array(
                'title'       => __('One Session Per User', AAM_KEY),
                'description' => sprintf(AAM_Backend_View_Helper::preparePhrase('Automatically destroy all other sessions for a user if he/she tries to login from different location. For more information refer to the %sOne Session Per User%s page.', 'strong', 'strong'), '<a href="https://aamportal.com/reference/advanced-access-manager/setting/one-session-per-user?ref=plugin" target="_blank">', '</a>'),
                'value'       => $service->get_config(
                    'service.secureLogin.feature.singleSession'
                )
            ),
            'service.secureLogin.feature.bruteForceLockout' => array(
                'title'       => __('Brute Force Lockout', AAM_KEY),
                'description' => sprintf(AAM_Backend_View_Helper::preparePhrase('Automatically reject login request if number of unsuccessful attempts exceeds 20 over the period of 2 minutes (both values are configurable). For more information refer to the %sBrute Force Lockout%s page.', 'strong', 'strong'), '<a href="https://aamportal.com/reference/advanced-access-manager/setting/bruteforce-lockout?ref=plugin" target="_blank">', '</a>'),
                'value'       => $service->get_config(
                    'service.secureLogin.feature.bruteForceLockout'
                )
            ),
        );

        return apply_filters('aam_settings_list_filter', $settings, 'security');
    }

    /**
     * Register security settings
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'settings-security',
            'position'   => 6,
            'title'      => __('Security Settings', AAM_KEY),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'settings',
            'view'       => __CLASS__
        ));
    }

}