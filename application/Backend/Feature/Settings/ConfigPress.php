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
 * @package AAM
 * @version 7.0.0
 */
class AAM_Backend_Feature_Settings_ConfigPress extends AAM_Backend_Feature_Abstract
{

    /**
     * Default access capability to the settings
     *
     * @version 7.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_settings';

    /**
     * HTML template to render
     *
     * @version 7.0.0
     */
    const TEMPLATE = 'settings/configpress.php';

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
        AAM_Backend_Feature::registerFeature((object) array(
            'uid'        => 'configpress',
            'position'   => 90,
            'title'      => __('ConfigPress', 'advanced-access-manager'),
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'settings',
            'view'       => __CLASS__
        ));
    }

}