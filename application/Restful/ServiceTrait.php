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
 * @since 6.9.37 https://github.com/aamplugin/advanced-access-manager/issues/413
 * @since 6.9.10 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.37
 */
trait AAM_Restful_ServiceTrait
{

    /**
     * Map of REST & HTTP status codes
     *
     * @version 6.9.33
     */
    private $_exception_map = [
        InvalidArgumentException::class => [
            'rest_code'   => 'rest_invalid_argument',
            'http_status' => 400
        ],
        DomainException::class => [
            'rest_code'   => 'rest_unauthorized',
            'http_status' => 401
        ],
        LogicException::class => [
            'rest_code'   => 'rest_workflow_error',
            'http_status' => 409
        ],
        OutOfRangeException::class => [
            'rest_code'   => 'rest_not_found',
            'http_status' => 404
        ],
        RuntimeException::class => [
            'rest_code'   => 'rest_insufficient_storage',
            'http_status' => 507
        ],
        Exception::class => [
            'rest_code'   => 'rest_unexpected_error',
            'http_status' => 500
        ]
    ];

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
     * @param string  $route
     * @param array   $args
     * @param boolean $access_level_aware
     *
     * @return void
     *
     * @access private
     * @version 6.9.10
     */
    private function _register_route($route, $args, $access_level_aware = true)
    {
        // Add the common arguments to all routes if needed
        if ($access_level_aware) {
            $args = array_merge_recursive(array(
                'args' => array(
                    'access_level' => array(
                        'description' => 'Access level for the controls',
                        'type'        => 'string',
                        'enum'        => array(
                            AAM_Core_Subject_Role::UID,
                            AAM_Core_Subject_User::UID,
                            AAM_Core_Subject_Visitor::UID,
                            AAM_Core_Subject_Default::UID
                        ),
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_access_level($value, $request);
                        }
                    ),
                    'role_id' => array(
                        'description'       => 'Role ID (aka slug)',
                        'type'              => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_role_id($value, $request);
                        }
                    ),
                    'user_id' => array(
                        'description'       => 'User ID',
                        'type'              => 'integer',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_user_id($value, $request);
                        }
                    )
                )
            ), $args);
        }

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
     * @return AAM_Core_Subject|null
     *
     * @access private
     * @since 6.9.10
     */
    private function _determine_subject(WP_REST_Request $request)
    {
        $subject      = null;
        $access_level = $request->get_param('access_level');

        if ($access_level) {
            $subject_id   = null;

            if ($access_level === AAM_Core_Subject_Role::UID) {
                $subject_id = $request->get_param('role_id');
            } elseif ($access_level === AAM_Core_Subject_User::UID) {
                $subject_id = $request->get_param('user_id');
            }

            $subject = AAM_Framework_Manager::subject()->get(
                $access_level, $subject_id
            );
        }

        return $subject;
    }

    /**
     * Validate if additional values are passed depending on access level
     *
     * @param string          $access_level
     * @param WP_REST_Request $request
     *
     * @return boolean|WP_Error
     *
     * @access private
     * @version 6.9.33
     */
    private function _validate_access_level($access_level, $request)
    {
        $response = true;

        if ($access_level === AAM_Core_Subject_Role::UID) {
            $role_id = $request->get_param('role_id');

            if (empty($role_id)) {
                $response = new WP_Error(
                    'rest_invalid_param',
                    __('The role_id is required', AAM_KEY),
                    array('status'  => 400)
                );
            }
        } elseif ($access_level === AAM_Core_Subject_User::UID) {
            $user_id = $request->get_param('user_id');

            if (empty($user_id)) {
                $response = new WP_Error(
                    'rest_invalid_param',
                    __('The user_id is required', AAM_KEY),
                    array('status'  => 400)
                );
            }
        }

        return $response;
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

        if ($access_level === AAM_Core_Subject_Role::UID) {
            // Verifying that the role exists and is accessible
            $response = $this->_validate_role_accessibility($value);
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

            if ($access_level === AAM_Core_Subject_User::UID) {
                // Verifying that the user exists and is accessible
                $response = $this->_validate_user_accessibility(
                    intval($value), $request->get_method()
                );
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
     * @since 6.9.37 https://github.com/aamplugin/advanced-access-manager/issues/413
     * @since 6.9.10 Initial implementation of the method
     *
     * @access private
     * @version 6.9.37
     */
    private function _validate_role_accessibility($slug)
    {
        $response = true;

        try {
            $service = AAM_Framework_Manager::roles([
                'error_handling' => 'exception'
            ]);

            if (!$service->is_editable_role($slug)) {
                $response = new WP_Error(
                    'rest_not_found',
                    "Role {$slug} is not editable to current user",
                    [ 'status'  => 404 ]
                );
            }
        } catch (Exception $_) {
            $response = new WP_Error(
                'rest_not_found',
                sprintf(
                    __("The role '%s' does not exist or is not editable"),
                    $slug
                ),
                [ 'status'  => 404 ]
            );
        }

        return $response;
    }

    /**
     * Validate user accessibility
     *
     * @param int    $user_id     User ID
     * @param string $http_method HTTP Method
     *
     * @return bool|WP_Error
     *
     * @access private
     * @version 6.9.10
     */
    private function _validate_user_accessibility($user_id, $http_method)
    {
        $response = true;

        // Legacy check if user is filtered out by "User Level Filter" service
        // TODO: Remove when the "User Level Filter" service is gone
        $user = apply_filters('aam_get_user', get_user_by('id', $user_id));

        // Now, depending on the HTTP Method, do additional check
        if ($http_method === WP_REST_Server::READABLE) {
            $cap = 'aam_list_users';
        } else {
            $cap = 'edit_user';
        }

        if ($user === false
            || is_wp_error($user)
            || !current_user_can($cap, $user->ID)
        ) {
            $response = new WP_Error(
                'rest_not_found',
                sprintf(
                    __("The user with ID '%s' does not exist or is not editable"),
                    $user_id
                ),
                [ 'status'  => 404 ]
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
    private function _prepare_error_response($ex, $code = null, $status = null)
    {
        $message = $ex->getMessage();

        // Determining the HTTP status code and REST error code
        $attributes = $this->_determine_response_attributes($ex, $code, $status);

        $data = array('status' => $attributes['http_status']);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            $data['details'] = array(
                'trace' => $ex->getTrace()
            );
        } elseif ($attributes['http_status'] === 500) {
            // Mask the real error if debug mode is off
            $message = 'Unexpected application error';
        }

        return new WP_REST_Response(
            new WP_Error($attributes['rest_code'], $message, $data),
            $attributes['http_status']
        );
    }

    /**
     * Determine proper HTTP status & REST response codes
     *
     * @param Exception   $exception
     * @param string|null $code
     * @param int|null    $status
     *
     * @return array
     *
     * @access private
     * @version 6.9.33
     */
    private function _determine_response_attributes($exception, $code, $status)
    {
        $exception_type = get_class($exception);

        if (array_key_exists($exception_type, $this->_exception_map)) {
            $recommended = $this->_exception_map[$exception_type];
        } else {
            $recommended = $this->_exception_map[Exception::class];
        }

        return [
            'rest_code'   => empty($code) ? $recommended['rest_code'] : $code,
            'http_status' => empty($status) ? $recommended['http_status'] : $status
        ];
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