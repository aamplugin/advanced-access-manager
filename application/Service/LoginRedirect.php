<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Login Redirect service
 *
 * @package AAM
 *
 * @version 7.0.0
 */
class AAM_Service_LoginRedirect
{

    use AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 7.0.0
     */
    const FEATURE_FLAG = 'service.login_redirect.enabled';

    /**
     * Default configurations
     *
     * @version 7.0.0
     */
    const DEFAULT_CONFIG = [
        'service.login_redirect.enabled'              => true,
        'service.login_redirect.suppress_redirect_to' => false
    ];

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
            if (is_null($result) && array_key_exists($key, self::DEFAULT_CONFIG)) {
                $result = self::DEFAULT_CONFIG[$key];
            }

            return $result;
        }, 10, 2);

        $enabled = AAM::api()->configs()->get_config(self::FEATURE_FLAG);

        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if ($enabled) {
                add_action('aam_initialize_ui_action', function () {
                    AAM_Backend_Feature_Main_LoginRedirect::register();
                });
            }

            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('Login Redirect', AAM_KEY),
                    'description' => __('Handle login redirects for any user group or individual user upon successful authentication.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 30);
        }

        if ($enabled) {
            $this->initialize_hooks();
        }
    }

    /**
     * Initialize service hooks
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function initialize_hooks()
    {
        // AAM Secure Login hooking
        add_filter(
            'aam_rest_authenticated_user_data_filter',
            array($this, 'prepare_login_response'),
            10,
            3
        );

        // WP Core login redirect hook
        add_filter('login_redirect', [ $this, 'get_login_redirect' ], 10, 3);

        // Register RESTful API
        AAM_Restful_LoginRedirectService::bootstrap();
    }

    /**
     * Prepare login redirect response
     *
     * This method hooks into the Secure Login redirect service and override the
     * response for the Ajax login request
     *
     * @param array           $response
     * @param WP_REST_Request $request
     * @param WP_User         $user
     *
     * @return array
     *
     * @access public
     * @see AAM_Service_SecureLogin::authenticate
     * @version 7.0.0
     */
    public function prepare_login_response($response, $request, $user)
    {
        if (is_array($response) && $request->get_param('return_login_redirect')) {
            $response['redirect_to'] = $this->get_user_redirect_url($user);
        }

        return $response;
    }

    /**
     * Get login redirect URL
     *
     * @param string  $redirect
     * @param string  $requested
     * @param WP_User $user
     *
     * @return string
     *
     * @access public
     * @version 7.0.0
     */
    public function get_login_redirect($redirect, $requested, $user)
    {
        // Determine if we want to suppress redirect_to URL
        $suppress = AAM::api()->configs()->get_config(
            'service.login_redirect.suppress_redirect_to'
        );

        if (is_a($user, 'WP_User')
                && (in_array($requested, array('', admin_url()), true) || $suppress)
        ) {
            $redirect = $this->get_user_redirect_url($user);
        }

        return $redirect;
    }

    /**
     * Prepare user redirect URL
     *
     * @param WP_User $user
     *
     * @return string|null
     *
     * @access protected
     * @version 7.0.0
     */
    protected function get_user_redirect_url($user)
    {
        $redirect = AAM::api()->user($user->ID)->login_redirect()->get_redirect();

        if ($redirect['type'] === 'default') {
            $redirect = [
                'type'         => 'url_redirect',
                'redirect_url' => admin_url()
            ];
        }

        return AAM_Framework_Utility_Redirect::to_redirect_url($redirect);
    }

}