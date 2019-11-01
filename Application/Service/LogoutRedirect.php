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
 * Logout Redirect service
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Service_LogoutRedirect
{
    use AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.0.0
     */
    const FEATURE_FLAG = 'core.service.logout-redirect.enabled';

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
            if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
                add_action('aam_init_ui_action', function () {
                    AAM_Backend_Feature_Main_LogoutRedirect::register();
                });
            }

            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('Logout Redirect', AAM_KEY),
                    'description' => __('Manage logout redirect for any group of users or individual user after user logged out successfully.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 35);
        }

        if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
            $this->initializeHooks();
        }
    }

    /**
     * Initialize Logout redirect hooks
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function initializeHooks()
    {
        // Fired after the user has been logged out successfully
        add_action('wp_logout', function() {
            $settings = AAM::getUser()->getObject(
                AAM_Core_Object_LogoutRedirect::OBJECT_TYPE
            )->getOption();

            // Determining redirect type
            $type = 'default';
            if (!empty($settings['logout.redirect.type'])) {
                $type = $settings['logout.redirect.type'];
            }

            if ($type !== 'default') {
                AAM_Core_Redirect::execute(
                    $type, array($type => $settings["logout.redirect.{$type}"])
                );

                // Halt the execution. Redirect should carry user away if this is not
                // a CLI execution (e.g. Unit Test)
                if (php_sapi_name() !== 'cli') {
                    exit;
                }
            }
        });
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_LogoutRedirect::bootstrap();
}