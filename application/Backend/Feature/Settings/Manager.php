<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Backend Settings area abstract manager
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Backend_Feature_Settings_Manager extends AAM_Backend_Feature_Abstract
{

    /**
     * Default access capability to the settings tab
     *
     * @version 7.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_settings';

    /**
     * Register settings UI manager
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public static function register()
    {
        AAM_Backend_Feature::registerFeature((object) array(
            'capability' => self::ACCESS_CAPABILITY,
            'type'       => 'core',
            'view'       => __CLASS__
        ));
    }

}