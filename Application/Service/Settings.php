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
 * Settings service
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Service_Settings
{
    use AAM_Core_Contract_ServiceTrait;

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function __construct()
    {
        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            add_action('aam_init_ui_action', function () {
                AAM_Backend_Feature_Settings_Service::register();
                AAM_Backend_Feature_Settings_Core::register();
                AAM_Backend_Feature_Settings_Content::register();
                AAM_Backend_Feature_Settings_ConfigPress::register();
                AAM_Backend_Feature_Settings_Manager::register();
            }, 1);
        }
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_Settings::bootstrap();
}