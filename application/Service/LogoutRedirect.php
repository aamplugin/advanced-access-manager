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
 * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
 * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/291
 * @since 6.4.0  https://github.com/aamplugin/advanced-access-manager/issues/76
 * @since 6.1.0  Fixed bug where white screen occurs if "Default" option is
 *               explicitly selected
 * @since 6.0.5  Fixed the bug with logout redirect
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.26
 */
class AAM_Service_LogoutRedirect
{
    use AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.0.0
     */
    const FEATURE_FLAG = 'service.logout-redirect.enabled';

    /**
     * Contains the redirect instructions for just logged out user
     *
     * This property is used to capture logging out user's redirect
     *
     * @var array
     *
     * @access protected
     * @since 7.0.0
     */
    private $_last_user_redirect = null;

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
        add_filter('aam_get_config_filter', function($result, $key) {
            if ($key === self::FEATURE_FLAG && is_null($result)) {
                $result = true;
            }

            return $result;
        }, 10, 2);

        $enabled = AAM::api()->configs()->get_config(self::FEATURE_FLAG);

        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if ($enabled) {
                add_action('aam_initialize_ui_action', function () {
                    AAM_Backend_Feature_Main_LogoutRedirect::register();
                });
            }

            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('Logout Redirect', AAM_KEY),
                    'description' => __('Manage the logout redirect for any group of users or individual users after they have successfully logged out.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 35);
        }

        if ($enabled) {
            $this->initialize_hooks();
        }
    }

    /**
     * Initialize Logout redirect hooks
     *
     * @return void
     *
     * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
     * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/291
     * @since 6.4.0  https://github.com/aamplugin/advanced-access-manager/issues/76
     * @since 6.1.0  Fixed bug where white screen occurs if "Default" option is
     *               explicitly selected
     * @since 6.0.5  Fixed bug where user was not redirected properly after logout
     *               because AAM was already hooking into `set_current_user`.
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.26
     */
    protected function initialize_hooks()
    {
        // Capture currently logging out user settings
        add_action('clear_auth_cookie', function() {
            $redirect = AAM::api()->user()->logout_redirect()->get_redirect();

            if (empty($redirect) || $redirect['type'] === 'default') {
                $this->_last_user_redirect = [
                    'type'         => 'url_redirect',
                    'redirect_url' => '/'
                ];
            } else {
                $this->_last_user_redirect = $redirect;
            }
        });

        // Fired after the user has been logged out successfully
        add_action('wp_logout', function() {
            AAM_Framework_Utility_Redirect::do_redirect($this->_last_user_redirect);
        }, PHP_INT_MAX);

        // Policy generation hook
        // add_filter('aam_generated_policy_filter', [ $this, 'generate_policy' ], 10, 4);

        // Register the resource
        add_filter(
            'aam_get_resource_filter',
            function($resource, $access_level, $resource_type) {
                if (is_null($resource)
                    && $resource_type === AAM_Framework_Type_Resource::LOGOUT_REDIRECT
                ) {
                    $resource = new AAM_Framework_Resource_LogoutRedirect(
                        $access_level
                    );
                }

                return $resource;
            }, 10, 3
        );

        // Register RESTful API
        AAM_Restful_LogoutRedirectService::bootstrap();
    }

    /**
     * Generate Logout Redirect policy params
     *
     * @param array                     $policy
     * @param string                    $resource_type
     * @param array                     $options
     * @param AAM_Core_Policy_Generator $generator
     *
     * @return array
     *
     * @access public
     * @version 6.4.0
     */
    // public function generate_policy($policy, $resource_type, $options, $generator)
    // {
    //     if ($resource_type === AAM_Framework_Type_Resource::LOGOUT_REDIRECT) {
    //         if (!empty($options)) {
    //             $policy['Param'] = array_merge(
    //                 $policy['Param'],
    //                 $generator->generateRedirectParam($options, 'logout')
    //             );
    //         }
    //     }

    //     return $policy;
    // }

}