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
 * @package AAM
 * @version 7.0.0
 */
class AAM_Restful_Jwt
{

    use AAM_Restful_ServiceTrait;

    /**
     * Necessary permissions to access endpoint
     *
     * @version 7.0.0
     */
    const PERMISSIONS = [
        'aam_manager',
        'aam_manage_jwts'
    ];

    /**
     * Constructor
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function __construct()
    {
        // Register API endpoint
        add_action('rest_api_init', function() {
            // Get the list of tokens
            $this->_register_route('/jwts', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_tokens'),
                'args'     => [
                    'fields' => array(
                        'description' => 'List of additional fields to return',
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_fields_input($value);
                        }
                    )
                ]
            ], self::PERMISSIONS, [ AAM_Framework_Type_AccessLevel::USER ]);

            // Create a new jwt token
            $this->_register_route('/jwts', [
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_token'),
                'args'     => array(
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
                        'type'        => 'boolean',
                        'default'     => false
                    ),
                    'is_revocable' => array(
                        'description' => 'Wether issued JWT is revocable',
                        'type'        => 'boolean',
                        'default'     => true
                    ),
                    'additional_claims' => array(
                        'description' => 'Any additional claims to include in the token',
                        'type'        => [ 'string', 'object' ],
                        'default'     => []
                    ),
                    'fields' => array(
                        'description' => 'List of additional fields to return',
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_fields_input($value);
                        }
                    )
                )
            ], self::PERMISSIONS, [ AAM_Framework_Type_AccessLevel::USER ]);

            // Get a token by ID
            $this->_register_route('/jwt/(?P<id>[\w\-]+)', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_token'),
                'args'     => array(
                    'id' => array(
                        'description' => 'Token unique ID',
                        'type'        => 'string',
                        'format'      => 'uuid',
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
            ], self::PERMISSIONS, [ AAM_Framework_Type_AccessLevel::USER ]);

            // Delete a token
            $this->_register_route('/jwt/(?P<id>[\w\-]+)', [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_token'),
                'args'     => array(
                    'id' => array(
                        'description' => 'Token unique ID',
                        'type'        => 'string',
                        'format'      => 'uuid',
                        'required'    => true
                    )
                )
            ], self::PERMISSIONS, [ AAM_Framework_Type_AccessLevel::USER ]);

            // Reset all tokens
            $this->_register_route('/jwts', [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'reset_tokens')
            ], self::PERMISSIONS, [ AAM_Framework_Type_AccessLevel::USER ]);
        });
    }

    /**
     * Get list of all registered tokens
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function get_tokens(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = array();

            foreach($service->get_tokens() as $token_data) {
                array_push($result, $this->_prepare_token_output(
                    $token_data, $request->get_param('fields')
                ));
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
     * @access public
     *
     * @version 7.0.0
     */
    public function create_token(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $claims  = [];

            // Do we have any additional claims to include
            if ($request->has_param('additional_claims')) {
                $raw_claims = $request->get_param('additional_claims');

                if (is_string($raw_claims)) {
                    $claims = json_decode($raw_claims, true);
                } elseif (is_array($raw_claims)) {
                    $claims = $raw_claims;
                } else {
                    throw new InvalidArgumentException('Invalid additional claims');
                }
            }

            // Determining the token expiration time
            $expires_at = $request->get_param('expires_at');
            $expires_in = $request->get_param('expires_in');

            if (!empty($expires_at)) {
                $ttl = $expires_at;
            } elseif (!empty($expires_in)) {
                $ttl = $expires_in;
            } else {
                $ttl = null;
            }

            $token_data = $service->issue($claims, [
                'ttl'         => $ttl,
                'revocable'   => $request->get_param('is_revocable'),
                'refreshable' => $request->get_param('is_refreshable')
            ]);

            $result = $this->_prepare_token_output(
                $token_data, $request->get_param('fields')
            );
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
     * @access public
     *
     * @version 7.0.0
     */
    public function get_token(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $this->_prepare_token_output(
                $service->get_token_by($request->get_param('id'), 'jti'),
                $request->get_param('fields')
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
     * @access public
     *
     * @version 7.0.0
     */
    public function delete_token(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $token   = $service->get_token_by($request->get_param('id'), 'jti');

            $result = [ 'success' => $service->revoke($token['token']) ];
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
     * @access public
     *
     * @version 7.0.0
     */
    public function reset_tokens(WP_REST_Request $request)
    {
        try {
            $result = [ 'success' => $this->_get_service($request)->reset() ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Validate the input field "fields"
     *
     * @param string|null $value Input value
     *
     * @return bool|WP_Error
     * @access private
     *
     * @version 7.0.0
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
                    sprintf('Invalid fields: %s', implode(', ', $invalid_fields)),
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
     * @access private
     *
     * @version 7.0.0
     */
    private function _validate_expires_in_input($value)
    {
        $response = true;

        if (is_string($value) && strlen($value) > 0) {
            $time = strtotime($value);

            if ($time === false) {
                $response = new WP_Error(
                    'rest_invalid_param',
                    'Invalid expires_in value',
                    array('status'  => 400)
                );
            }
        }

        return $response;
    }

    /**
     * Prepare token for the output
     *
     * @param array $token_data
     * @param array $fields
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_token_output($token_data, $fields)
    {
        $output = [
            'id'       => $token_data['claims']['jti'],
            'token'    => $token_data['token'],
            'is_valid' => $token_data['is_valid']
        ];

        if ($token_data['is_valid']) {
            foreach((!empty($fields) ? wp_parse_list($fields) : []) as $field) {
                if ($field === 'signed_url') {
                    $output[$field] = add_query_arg(
                        'aam-jwt', $token_data['token'], site_url()
                    );
                } elseif (array_key_exists($field, $token_data)) {
                    $output[$field] = $token_data[$field];
                }
            }
        } else {
            $output['error'] = $token_data['error'];
        }

        return $output;
    }

    /**
     * Get JWT framework service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_Jwts
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_service($request)
    {
        $access_level = AAM::api()->access_levels->get(
            AAM_Framework_Type_AccessLevel::USER,
            $request->get_param('user_id')
        );

        return AAM::api()->jwts(
            $access_level,
            [ 'error_handling' => 'exception' ]
        );
    }

}