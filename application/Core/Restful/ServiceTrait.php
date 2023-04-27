<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API service trait
 *
 * @package AAM
 * @version 6.9.10
 */
trait AAM_Core_Restful_ServiceTrait
{

    /**
     * Single instance of itself
     *
     * @var static::class
     *
     * @access private
     * @static
     */
    private static $_instance = null;

    /**
     * Register new RESTful route
     *
     * The method also applies the `aam_rest_route_args_filter` filter that allows
     * other processes to change the router definition
     *
     * @param string $route
     * @param array  $args
     *
     * @return void
     *
     * @access private
     * @version 6.9.10
     */
    private function _register_route($route, $args)
    {
        // Add the common arguments to all routes
        $args = array_merge(array(
            'args' => array(
                'access_level' => array(
                    'description' => __('Access level for the controls', AAM_KEY),
                    'type'        => 'string',
                    'enum'        => array(
                        AAM_Core_Subject_Role::UID,
                        AAM_Core_Subject_User::UID,
                        AAM_Core_Subject_Visitor::UID,
                        AAM_Core_Subject_Default::UID
                    )
                ),
                'role_id' => array(
                    'description' => __('Role ID (aka slug)', AAM_KEY),
                    'type'        => 'string',
                    'validate_callback' => function ($value, $request) {
                        return $this->_validate_role_id($value, $request);
                    }
                ),
                'user_id' => array(
                    'description' => __('User ID', AAM_KEY),
                    'type'        => 'integer',
                    'validate_callback' => function ($value, $request) {
                        return $this->_validate_user_id($value, $request);
                    }
                )
            )
        ), $args);

        register_rest_route(
            'aam/v2/service',
            $route,
            apply_filters(
                'aam_rest_route_args_filter', $args, $route, 'aam/v2/service'
            )
        );
    }

    /**
     * Determine current subject
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Core_Subject
     *
     * @access private
     * @since 6.9.10
     */
    private function _determine_subject(WP_REST_Request $request)
    {
        $access_level = $request->get_param('access_level');
        $subject_id   = null;

        if ($access_level === AAM_Core_Subject_Role::UID) {
            $subject_id = $request->get_param('role_id');
        } elseif ($access_level === AAM_Core_Subject_User::UID) {
            $subject_id = $request->get_param('user_id');
        }

        return AAM_Framework_Manager::subject()->get($access_level, $subject_id);
    }

    /**
     * Validate the input value "role_id"
     *
     * @param string|null     $value   Input value
     * @param WP_REST_Request $request Request
     *
     * @return bool|WP_Error
     *
     * @access private
     * @version 6.9.10
     */
    private function _validate_role_id($value, $request)
    {
        $response     = true;
        $access_level = $request->get_param('access_level');
        $is_empty     = !is_string($value) || strlen($value) === 0;

        if ($access_level === AAM_Core_Subject_Role::UID) {
            if ($is_empty) {
                $response = new WP_Error(
                    'rest_invalid_param',
                    __('The role_id is required', AAM_KEY),
                    array('status'  => 400)
                );
            } else { // Verifying that the role exists and is accessible
                $response = $this->_validate_role_accessibility($value);
            }
        } elseif (!$is_empty) {
            $response = new WP_Error(
                'rest_invalid_param',
                __('The role_id param works only with access_level=role', AAM_KEY),
                array('status'  => 400)
            );
        }

        return $response;
    }

    /**
     * Validate the input value "user_id"
     *
     * @param string|null     $value   Input value
     * @param WP_REST_Request $request Request
     *
     * @return bool|WP_Error
     *
     * @access private
     * @version 6.9.10
     */
    private function _validate_user_id($value, $request)
    {
        $response = true;

        if (is_numeric($value)) {
            $access_level = $request->get_param('access_level');

            if ($access_level !== AAM_Core_Subject_User::UID) {
                $response = new WP_Error(
                    'rest_invalid_param',
                    __('The user_id param works only with access_level=user', AAM_KEY),
                    array('status'  => 400)
                );
            } else { // Verifying that the user exists and is accessible
                $response = $this->_validate_user_accessibility(intval($value));
            }
        }

        return $response;
    }

    /**
     * Validate role accessibility
     *
     * @param string $slug Role unique slug (aka ID)
     *
     * @return bool|WP_Error
     *
     * @access private
     * @version 6.9.10
     */
    private function _validate_role_accessibility($slug)
    {
        $response = true;

        try {
            AAM_Framework_Manager::roles()->get_role_by_slug($slug);
        } catch (UnderflowException $_) {
            $response = new WP_Error(
                'rest_not_found',
                sprintf(
                    __("The role '%s' does not exist or is not editable"),
                    $slug
                ),
                array('status'  => 404)
            );
        }

        return $response;
    }

    /**
     * Validate user accessibility
     *
     * @param int $user_id User ID
     *
     * @return bool|WP_Error
     *
     * @access private
     * @version 6.9.10
     */
    private function _validate_user_accessibility($user_id)
    {
        $response = true;

        $user = apply_filters('aam_get_user', get_user_by('id', $user_id));

        if ($user === false || is_wp_error($user)) {
            $response = new WP_Error(
                'rest_not_found',
                sprintf(
                    __("The user with ID '%s' does not exist or is not editable"),
                    $user_id
                ),
                array('status'  => 404)
            );
        }

        return $response;
    }

    /**
     * Prepare the failure response
     *
     * @param Exception $ex
     * @param string    $code
     * @param integer   $status
     *
     * @return WP_REST_Response
     *
     * @access private
     * @version 6.9.10
     */
    private function _prepare_error_response(
        $ex, $code = 'rest_unexpected_error', $status = 500
    ) {
        $message = $ex->getMessage();
        $data    = array('status' => $status);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            $data['details'] = array(
                'trace' => $ex->getTrace()
            );
        } elseif ($status === 500) { // Mask the real error if debug mode is off
            $message = __('Unexpected application error', AAM_KEY);
        }

        return new WP_REST_Response(new WP_Error($code, $message, $data), $status);
    }

    /**
     * Bootstrap the api
     *
     * @return static::class
     *
     * @access public
     * @version 6.9.10
     */
    public static function bootstrap()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

}