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
 * @version 7.0.0
 */
trait AAM_Restful_ServiceTrait
{

    /**
     * Map of REST & HTTP status codes
     *
     * @version 7.0.0
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
        UnexpectedValueException::class => [
            'rest_code'   => 'rest_unauthorized',
            'http_status' => 406
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
     *
     * @version 7.0.0
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
     * @param mixed   $auth
     * @param boolean $access_level_aware
     * @param string  $ns
     *
     * @return void
     * @access private
     *
     * @version 7.0.0
     */
    private function _register_route(
        $route, $args, $auth, $access_level_aware = true, $ns = null
    ) {
        // Add the common arguments to all routes if needed
        if ($access_level_aware === true) {
            $args = array_merge_recursive(array(
                'args' => array(
                    'access_level' => array(
                        'description' => 'Access level for the controls',
                        'type'        => 'string',
                        'enum'        => [
                            AAM_Framework_Type_AccessLevel::ROLE,
                            AAM_Framework_Type_AccessLevel::USER,
                            AAM_Framework_Type_AccessLevel::VISITOR,
                            AAM_Framework_Type_AccessLevel::DEFAULT
                        ],
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
        } elseif (is_array($access_level_aware)) {
            // Let's build the additional params
            $additional_args = [
                'access_level' => [
                    'description' => 'Access level for the controls',
                    'type'        => 'string',
                    'enum'        => $access_level_aware,
                    'validate_callback' => function ($value, $request) {
                        return $this->_validate_access_level($value, $request);
                    }
                ]
            ];

            // Adding more params based on included access levels
            if (in_array(AAM_Framework_Type_AccessLevel::ROLE, $access_level_aware, true)) {
                $additional_args['role_id'] = [
                    'description'       => 'Role ID (aka slug)',
                    'type'              => 'string',
                    'validate_callback' => function ($value, $request) {
                        return $this->_validate_role_id($value, $request);
                    }
                ];
            } elseif (in_array(AAM_Framework_Type_AccessLevel::USER, $access_level_aware, true)) {
                $additional_args['user_id'] = [
                    'description'       => 'User ID',
                    'type'              => 'integer',
                    'validate_callback' => function ($value, $request) {
                        return $this->_validate_user_id($value, $request);
                    }
                ];
            }

            $args = array_merge_recursive([ 'args' => $additional_args ], $args);
        }

        AAM::api()->rest->register(
            is_null($ns) ? 'aam/v2' : $ns,
            $route,
            $args,
            $auth
        );
    }

    /**
     * Determine current subject
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_AccessLevel_Interface|null
     *
     * @access private
     * @since 7.0.0
     */
    private function _determine_access_level(WP_REST_Request $request)
    {
        $result       = null;
        $access_level = $request->get_param('access_level');

        if ($access_level) {
            $access_level_id = null;

            if ($access_level === AAM_Framework_Type_AccessLevel::ROLE) {
                $access_level_id = $request->get_param('role_id');
            } elseif ($access_level === AAM_Framework_Type_AccessLevel::USER) {
                $access_level_id = $request->get_param('user_id');
            }

            $result = AAM::api()->access_levels->get(
                $access_level, $access_level_id
            );
        }

        return $result;
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
     * @version 7.0.0
     */
    private function _validate_access_level($access_level, $request)
    {
        $response = true;

        if ($access_level === AAM_Framework_Type_AccessLevel::ROLE) {
            $role_id = $request->get_param('role_id');

            if (empty($role_id)) {
                $response = new WP_Error(
                    'rest_invalid_param',
                    __('The role_id is required', 'advanced-access-manager'),
                    array('status'  => 400)
                );
            }
        } elseif ($access_level === AAM_Framework_Type_AccessLevel::USER) {
            $user_id = $request->get_param('user_id');

            if (empty($user_id)) {
                $response = new WP_Error(
                    'rest_invalid_param',
                    __('The user_id is required', 'advanced-access-manager'),
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
     * @version 7.0.0
     */
    private function _validate_role_id($value, $request)
    {
        $response     = true;
        $access_level = $request->get_param('access_level');

        if ($access_level === AAM_Framework_Type_AccessLevel::ROLE) {
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
     * @version 7.0.0
     */
    private function _validate_user_id($value, $request)
    {
        $response = true;

        if (is_numeric($value)) {
            $access_level = $request->get_param('access_level');

            if ($access_level === AAM_Framework_Type_AccessLevel::USER) {
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
     * @access private
     * @version 7.0.0
     */
    private function _validate_role_accessibility($slug)
    {
        $response = true;

        try {
            $slug = urldecode($slug);

            if (!AAM::api()->roles->is_editable_role($slug)) {
                $response = new WP_Error(
                    'rest_not_found',
                    "Role {$slug} is not editable to current user",
                    [ 'status'  => 404 ]
                );
            }
        } catch (Exception $_) {
            $response = new WP_Error(
                'rest_not_found',
                sprintf("The role '%s' does not exist or is not editable", $slug),
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
     * @version 7.0.0
     */
    private function _validate_user_accessibility($user_id, $http_method)
    {
        $response = true;

        // Legacy check if user is filtered out by "User Level Filter" service
        // TODO: Remove when the "User Level Filter" service is gone
        $user = apply_filters('aam_get_user', get_user_by('id', $user_id));

        // Now, depending on the HTTP Method, do additional check
        if ($http_method === WP_REST_Server::READABLE) {
            $cap = 'list_users';
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
                    "The user with ID '%s' does not exist or is not editable",
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
     * @version 7.0.0
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
     * @version 7.0.0
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
     * @version 7.0.0
     */
    public static function bootstrap()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

}