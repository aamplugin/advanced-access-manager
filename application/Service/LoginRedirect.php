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
 * @since 6.9.19 https://github.com/aamplugin/advanced-access-manager/issues/332
 * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/285
 * @since 6.6.2  https://github.com/aamplugin/advanced-access-manager/issues/139
 * @since 6.5.0  https://github.com/aamplugin/advanced-access-manager/issues/98
 * @since 6.4.0  https://github.com/aamplugin/advanced-access-manager/issues/76
 * @since 6.0.0  Initial implementation of the class
 *
 * @version 6.9.19
 */
class AAM_Service_LoginRedirect
{
    use AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.0.0
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

        $enabled = AAM_Framework_Manager::configs()->get_config(self::FEATURE_FLAG);

        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if ($enabled) {
                add_action('aam_init_ui_action', function () {
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
     * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/285
     * @since 6.6.2  https://github.com/aamplugin/advanced-access-manager/issues/139
     * @since 6.4.0  https://github.com/aamplugin/advanced-access-manager/issues/76
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.12
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

        // Policy generation hook
        add_filter(
            'aam_generated_policy_filter', [ $this, 'generate_policy' ], 10, 4
        );

        // Register the resource
        add_filter(
            'aam_get_resource_filter',
            function($resource, $access_level, $resource_type) {
                if (is_null($resource)
                    && $resource_type === AAM_Framework_Type_Resource::LOGIN_REDIRECT
                ) {
                    $resource = new AAM_Framework_Resource_LoginRedirect(
                        $access_level
                    );
                }

                return $resource;
            }, 10, 3
        );

        // Register RESTful API
        AAM_Restful_LoginRedirectService::bootstrap();
    }

    /**
     * Generate Login Redirect policy params
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
    public function generate_policy($policy, $resource_type, $options, $generator)
    {
        if ($resource_type === AAM_Framework_Type_Resource::LOGIN_REDIRECT) {
            if (!empty($options)) {
                $policy['Param'] = array_merge(
                    $policy['Param'],
                    $generator->generateRedirectParam($options, 'login')
                );
            }
        }

        return $policy;
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
     * @since 6.6.2 https://github.com/aamplugin/advanced-access-manager/issues/139
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @see AAM_Service_SecureLogin::authenticate
     * @version 6.6.2
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
     * @since 6.5.0 Fixed the way login redirect is computed
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.5.0
     */
    public function get_login_redirect($redirect, $requested, $user)
    {
        // Determine if we want to suppress redirect_to URL
        $suppress = AAM_Framework_Manager::configs()->get_config(
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
     * @since 6.9.19 https://github.com/aamplugin/advanced-access-manager/issues/332
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.19
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

        return AAM_Framework_Utility::to_redirect_url($redirect);
    }

}