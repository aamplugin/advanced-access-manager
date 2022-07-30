<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 *
 * @version 6.0.0
 */

/**
 * Backend security settings
 *
 * @package AAM
 * @version 6.0.0
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
     * @access public
     * @version 6.0.0
     */
    public static function getList()
    {
        $settings = array(
            'service.secureLogin.feature.singleSession' => array(
                'title'       => __('One Session Per User', AAM_KEY),
                'description' => sprintf(AAM_Backend_View_Helper::preparePhrase('Automatically destroy all other sessions for a user if he/she tries to login from different location. For more information about this option please refer to %sHow does AAM Secure Login works%s.', 'strong', 'strong'), '<a href="https://aamplugin.com/article/how-does-aam-secure-login-works" target="_blank">', '</a>'),
                'value'       => AAM_Core_Config::get('service.secureLogin.feature.singleSession', false)
            ),
            'service.secureLogin.feature.bruteForceLockout' => array(
                'title'       => __('Brute Force Lockout', AAM_KEY),
                'description' => sprintf(AAM_Backend_View_Helper::preparePhrase('Automatically reject login attempts if number of unsuccessful login attempts is more than 20 over the period of 2 minutes (both values are configurable). For more information about this option please refer to %sHow does AAM Secure Login works%s.', 'strong', 'strong'), '<a href="https://aamplugin.com/article/how-does-aam-secure-login-works" target="_blank">', '</a>'),
                'value'       => AAM_Core_Config::get('service.secureLogin.feature.bruteForceLockout', false)
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