<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Logout Redirect service
 *
 * @since 6.1.0 Fixed bug where white screen occurs if "Default" option is
 *              explicitly selected
 * @since 6.0.5 Fixed the bug with logout redirect
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.1.0
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
     * Contains the redirect instructions for just logged out user
     *
     * @var array
     *
     * @access protected
     * @since 6.0.5
     */
    protected $redirect = null;

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
     * @since 6.1.0 Fixed bug where white screen occurs if "Default" option is
     *              explicitly selected
     * @since 6.0.5 Fixed bug where user was not redirected properly after logout
     *              because AAM was already hooking into `set_current_user`.
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.1.0
     */
    protected function initializeHooks()
    {
        // Capture currently logging out user settings
        add_action('clear_auth_cookie', function() {
            $this->redirect = AAM::getUser()->getObject(
                AAM_Core_Object_LogoutRedirect::OBJECT_TYPE
            )->getOption();
        });

        // Fired after the user has been logged out successfully
        add_action('wp_logout', function() {
            // Determining redirect type
            $type = 'default';
            if (!empty($this->redirect['logout.redirect.type'])) {
                $type = $this->redirect['logout.redirect.type'];
            }

            if ($type !== 'default') {
                AAM_Core_Redirect::execute(
                    $type, array($type => $this->redirect["logout.redirect.{$type}"])
                );
            }

            // Halt the execution. Redirect should carry user away if this is not
            // a CLI execution (e.g. Unit Test)
            if (php_sapi_name() !== 'cli' && $type !== 'default') {
                exit;
            }
        }, PHP_INT_MAX);
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_LogoutRedirect::bootstrap();
}