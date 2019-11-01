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
 * Backend Settings area abstract manager
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Backend_Feature_Settings_Manager extends AAM_Backend_Feature_Abstract
{

    use AAM_Core_Contract_RequestTrait;

    /**
     * Default access capability to the settings tab
     *
     * @version 6.0.0
     */
    const ACCESS_CAPABILITY = 'aam_manage_settings';

    /**
     * Save the option
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function save()
    {
        $param = $this->getFromPost('param');
        $value = $this->getFromPost('value');

        AAM_Core_Config::set($param, $value);

        return wp_json_encode(array('status' => 'success'));
    }

    /**
     * Clear all AAM settings
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function clearSettings()
    {
        AAM_Core_API::clearSettings();

        return wp_json_encode(array('status' => 'success'));
    }

    /**
     * Register settings UI manager
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
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