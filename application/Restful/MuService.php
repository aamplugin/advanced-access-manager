<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API service that must be on at all time
 *
 * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/398
 * @since 6.9.33 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.35
 */
class AAM_Restful_MuService
{

    use AAM_Restful_ServiceTrait;

    /**
     * Construct
     *
     * @return void
     *
     * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/398
     * @since 6.9.33 Initial implementation of the method
     *
     * @access protected
     * @version 6.9.35
     */
    protected function __construct()
    {
        // Covering the bug in WordPress core that does not set correct user local
        add_filter('rest_pre_dispatch', function($r, $_, $request) {
            global $wp_locale_switcher;

            if (strpos($request->get_route(), '/aam/') !== false) {
                $user_id = get_current_user_id();

                if (function_exists('switch_to_user_locale')) {
                    switch_to_user_locale($user_id);
                } elseif (is_a($wp_locale_switcher, 'WP_Locale_Switcher')) {
                    $wp_locale_switcher->switch_to_locale(
                        get_user_locale($user_id), $user_id
                    );
                }
            }

            return $r;
        }, 10, 3);

        // Few services always available to support AAM UI
        AAM_Restful_RoleService::bootstrap();
        AAM_Restful_UserService::bootstrap();
        AAM_Restful_ConfigService::bootstrap();
        AAM_Restful_SettingService::bootstrap();

        // Get currently managed "Access Level" (previously known as "subject")
        add_filter(
            'aam_rest_get_access_level_filter', [$this, 'get_access_level'], 10, 2
        );

        // Handle common REST errors
        add_filter(
            'aam_rest_get_error_response_filter', [$this, 'get_error_response'], 10, 4
        );

        // Register a common AAM, access level aware RESTful API endpoint
        add_action('aam_rest_register_route', [$this, 'register_route'], 10, 2);
    }

    /**
     * Get current access level
     *
     * @param null|object     $access_level
     * @param WP_REST_Request $request
     *
     * @return null|object
     *
     * @access public
     * @version 6.9.33
     */
    public function get_access_level($access_level, $request)
    {
        if (is_null($access_level)) {
            $access_level = $this->_determine_subject($request);
        }

        return $access_level;
    }

    /**
     * Get RESTful error response
     *
     * @param mixed     $response
     * @param Exception $exception
     * @param string    $rest_code
     * @param int       $http_status
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.33
     */
    public function get_error_response(
        $response, $exception, $rest_code, $http_status
    ) {
        if (is_null($response)) {
            $response = $this->_prepare_error_response(
                $exception, $rest_code, $http_status
            );
        }

        return $response;
    }

    /**
     * Register AAM standard RESTful API route
     *
     * @param string $route
     * @param array  $args
     *
     * @return void
     *
     * @access public
     * @version 6.9.33
     */
    public function register_route($route, $args)
    {
        $this->_register_route($route, $args);
    }

}