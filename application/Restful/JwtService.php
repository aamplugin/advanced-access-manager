<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for the JWT Token service
 *
 * @since 6.9.11 https://github.com/aamplugin/advanced-access-manager/issues/278
 * @since 6.9.10 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.11
 */
class AAM_Restful_JwtService
{

    use AAM_Restful_ServiceTrait;

    /**
     * Constructor
     *
     * @return void
     *
     * @since 6.9.11 https://github.com/aamplugin/advanced-access-manager/issues/278
     * @since 6.9.10 Initial implementation of the method
     *
     * @access protected
     * @version 6.9.11
     */
    protected function __construct()
    {
        // Register API endpoint
        add_action('rest_api_init', function() {
            // Get the list of tokens
            $this->_register_route('/jwts', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_token_list'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => [
                    'fields' => array(
                        'description' => 'List of additional fields to return',
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_fields_input($value);
                        }
                    )
                ]
            ));

            // Create a new jwt token
            $this->_register_route('/jwts', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'create_token'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'expires_at' => array(
                        'description' => 'Well formatted date-time when the token expires',
                        'type'        => 'date-time'
                    ),
                    'expires_in' => array(
                        'description' => 'Relative datetime format',
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_expires_in_input($value);
                        }
                    ),
                    'is_refreshable' => array(
                        'description' => 'Wether issued JWT is refreshable',
                        'type'        => 'boolean'
                    ),
                    'is_revocable' => array(
                        'description' => 'Wether issued JWT is revocable',
                        'type'        => 'boolean',
                        'default'     => true
                    ),
                    'additional_claims' => array(
                        'description' => 'Any additional claims to include in the token',
                        'type'        => 'object'
                    ),
                    'fields' => array(
                        'description' => 'List of additional fields to return',
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_fields_input($value);
                        }
                    )
                )
            ));

            // Get a token by ID
            $this->_register_route('/jwt/(?P<id>[\dA-Za-z\-]+)', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_token'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => 'Token unique ID',
                        'type'        => 'string',
                        'required'    => true
                    ),
                    'fields' => array(
                        'description' => 'List of additional fields to return',
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_fields_input($value);
                        }
                    )
                )
            ));

            // Delete a token
            $this->_register_route('/jwt/(?P<id>[\dA-Za-z\-]+)', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_token'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => 'Token unique ID',
                        'type'        => 'string',
                        'required'    => true
                    )
                )
            ));

            // Reset all tokens
            $this->_register_route('/jwts', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'reset_tokens'),
                'permission_callback' => array($this, 'check_permissions')
            ));
        });
    }

    /**
     * Get list of all tokens
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.10
     */
    public function get_token_list(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = array();

            foreach($service->get_token_list() as $token) {
                array_push(
                    $result,
                    $this->_prepare_token_output($token, $request)
                );
            }
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Create new JWT token
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @since 6.9.11 https://github.com/aamplugin/advanced-access-manager/issues/278
     * @since 6.9.10 Initial implementation of the method
     *
     * @access public
     * @version 6.9.11
     */
    public function create_token(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);

            // Do we have any additional claims to include
            $claims = (array)$request->get_param('additional_claims');

            // Determining the token expiration time
            $expires_at = $request->get_param('expires_at');
            $expires_in = $request->get_param('expires_in');

            if (!empty($expires_at)) {
                $claims['exp'] = strtotime($expires_at);
            } elseif (!empty($expires_in)) {
                $claims['exp'] = strtotime($expires_in);
            }

            // Wether token is refreshable or not
            $is_ref                = $request->get_param('is_refreshable');
            $claims['refreshable'] = is_bool($is_ref) ? $is_ref : false;

            // Wether token is revocable or not
            $is_rev = $request->get_param('is_revocable');

            if (!is_bool($is_rev)) {
                $is_rev = AAM_Framework_Manager::configs()->get_config(
                    'service.jwt.is_revocable', true
                );
            }
            $claims['revocable'] = is_bool($is_rev) ? $is_rev : true;

            $token = $service->create_token($claims);

            // Determine the list of fields to return
            $fields = $request->get_param('fields');

            if (empty($fields)) {
                $fields = array('id', 'token');
            } else {
                $fields = explode(',', $fields);
            }

            $result = $this->_prepare_token_output($token, $request);
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get a token by ID
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.10
     */
    public function get_token(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $this->_prepare_token_output(
                $service->get_token_by_id($request->get_param('id')), $request
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Delete a token
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.10
     */
    public function delete_token(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);

            $service->delete_token($request->get_param('id'));

            $result = [ 'success' => true ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset all tokens
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.10
     */
    public function reset_tokens(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->reset();
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Check if current user has access to the service
     *
     * @return bool
     *
     * @access public
     * @version 6.9.10
     */
    public function check_permissions()
    {
        return current_user_can('aam_manager')
            && current_user_can('aam_manage_jwt');
    }

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
        $args = array_merge_recursive(array(
            'args' => array(
                'user_id' => array(
                    'description' => 'User ID',
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
     * Validate the input field "fields"
     *
     * @param string|null $value Input value
     *
     * @return bool|WP_Error
     *
     * @access private
     * @version 6.9.10
     */
    private function _validate_fields_input($value)
    {
        $response = true;

        if (is_string($value) && strlen($value) > 0) {
            $invalid_fields = [];

            foreach(explode(',', $value) as $field) {
                if (strlen(sanitize_key($field)) !== strlen($field)) {
                    $invalid_fields[] = $field;
                }
            }

            if (count($invalid_fields) > 0) {
                $response = new WP_Error(
                    'rest_invalid_param',
                    sprintf(
                        __('Invalid fields: %s'),
                        implode(', ', $invalid_fields)
                    ),
                    array('status'  => 400)
                );
            }
        }

        return $response;
    }

    /**
     * Validate the input field "expires_in"
     *
     * @param string|null $value Input value
     *
     * @return bool|WP_Error
     *
     * @access private
     * @version 6.9.10
     */
    private function _validate_expires_in_input($value)
    {
        $response = true;

        if (is_string($value) && strlen($value) > 0) {
            $time = strtotime($value);

            if ($time === false) {
                $response = new WP_Error(
                    'rest_invalid_param',
                    __('Invalid expires_in value'),
                    array('status'  => 400)
                );
            }
        }

        return $response;
    }

    /**
     * Prepare token for the output
     *
     * @param array           $input
     * @param WP_REST_Request $request
     *
     * @return array
     *
     * @access private
     * @version 6.9.10
     */
    private function _prepare_token_output($input, $request)
    {
        $output = [];

        // Determine the list of fields to return
        $fields = $request->get_param('fields');
        $fields = array_unique(array_merge(
            [ 'id', 'token' ],
            ($fields ? explode(',', $fields) : [])
        ));

        foreach($fields as $field) {
            if (array_key_exists($field, $input)) {
                $output[$field] = $input[$field];
            }
        }

        return $output;
    }

    /**
     * Get JWT framework service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_Jwts
     *
     * @access private
     * @version 6.9.10
     */
    private function _get_service($request)
    {
        $subject = AAM_Framework_Manager::subject()->get(
            AAM_Core_Subject_User::UID, $request->get_param('user_id')
        );

        return AAM_Framework_Manager::jwts([
            'subject'        => $subject,
            'error_handling' => 'exception'
        ]);
    }

}