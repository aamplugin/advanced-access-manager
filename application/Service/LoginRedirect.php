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
    const FEATURE_FLAG = 'core.service.login-redirect.enabled';

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
                    'description' => __('Manage login redirect for any group of users or individual user when authentication is completed successfully.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 30);
        }

        if ($enabled) {
            $this->initializeHooks();
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
    protected function initializeHooks()
    {
        // AAM Secure Login hooking
        add_filter(
            'aam_auth_response_filter',
            array($this, 'prepareLoginResponse'),
            10,
            3
        );

        // WP Core login redirect hook
        add_filter('login_redirect', array($this, 'getLoginRedirect'), 10, 3);

        // Policy generation hook
        add_filter(
            'aam_generated_policy_filter', array($this, 'generatePolicy'), 10, 4
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
    public function generatePolicy($policy, $resource_type, $options, $generator)
    {
        if ($resource_type === AAM_Core_Object_LoginRedirect::OBJECT_TYPE) {
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
    public function prepareLoginResponse($response, $request, $user)
    {
        if (empty($response['redirect']) || ($response['redirect'] === admin_url())) {
            $response['redirect'] = $this->getUserRedirect($user);
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
    public function getLoginRedirect($redirect, $requested, $user)
    {
        if (is_a($user, 'WP_User')
                && in_array($requested, array('', admin_url()), true)
        ) {
            $requested = $this->getUserRedirect($user);
            $redirect  = (!empty($requested) ? $requested : $redirect);
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
    protected function getUserRedirect(WP_User $user)
    {
        $redirect = null;

        $settings = AAM::api()->getUser($user->ID)->getObject(
            AAM_Core_Object_LoginRedirect::OBJECT_TYPE
        )->getOption();

        if (!empty($settings)) {
            switch ($settings['login.redirect.type']) {
                case 'page':
                    $redirect = get_page_link($settings['login.redirect.page']);
                    break;

                case 'url':
                    $redirect = wp_validate_redirect($settings['login.redirect.url']);
                    break;

                case 'callback':
                    if (is_callable($settings['login.redirect.callback'])) {
                        $redirect = call_user_func($settings['login.redirect.callback']);
                    }
                    break;

                default:
                    break;
            }
        }

        return $redirect;
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_LoginRedirect::bootstrap();
}