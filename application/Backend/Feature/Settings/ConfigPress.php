<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend ConfigPress tab
 *
 * @since 6.9.34 https://github.com/aamplugin/advanced-access-manager/issues/395
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.34
 */
class AAM_Backend_Feature_Settings_ConfigPress extends AAM_Backend_Feature_Abstract
{

    use AAM_Core_Contract_RequestTrait;

    /**
     * Default access capability to the settings
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_settings';

    /**
     * HTML template to render
     *
     * @version 6.0.0
     */
    const TEMPLATE = 'settings/configpress.php';

    /**
     * Register service UI
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'configpress',
            'position'   => 90,
            'title'      => __('ConfigPress', AAM_KEY),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'settings',
            'view'       => __CLASS__
        ));
    }

}