<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API to support backward compatibility with AAM 6
 *
 * @package AAM
 * @version 7.0.1
 */
class AAM_Restful_BackwardCompatibility
{

    use AAM_Restful_ServiceTrait;

    /**
     * Constructor
     *
     * @return void
     * @access protected
     *
     * @version 7.0.1
     */
    protected function __construct()
    {
        // Register API endpoint
        if (AAM::api()->config->get('service.jwt.enabled', true)) {
            add_action('rest_api_init', function() {
                // Validate JWT token
                register_rest_route('aam/v2', '/jwt/validate', [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'validate_token' ],
                    'permission_callback' => '__return_true',
                    'args'                => [
                        'jwt' => [
                            'description' => __('JWT token.', AAM_KEY),
                            'type'        => 'string',
                        ]
                    ],
                ]);

                // Refresh JWT token
                register_rest_route('aam/v2', '/jwt/refresh', [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'refresh_token' ],
                    'permission_callback' => '__return_true',
                    'args'                => [
                        'jwt' => [
                            'description' => __('JWT token.', AAM_KEY),
                            'type'        => 'string',
                        ]
                    ],
                ]);

                // Revoke JWT token
                register_rest_route('aam/v2', '/jwt/revoke', [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'revoke_token' ],
                    'permission_callback' => '__return_true',
                    'args'                => [
                        'jwt' => [
                            'description' => __('JWT token.', AAM_KEY),
                            'type'        => 'string',
                        ]
                    ],
                ]);
            });
        }
    }

    /**
     * Validate JWT token
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.1
     */
    public function validate_token($request)
    {
        try {
            _deprecated_function(
                '/aam/v2/jwt/validate',
                AAM_VERSION,
                'Deprecated /jwt/validate REST API endpoint will be removed in 7.5.0'
            );

            // Get JWT service
            $service = $this->_get_jwt_service_through_jwt($request);
            $jwt     = $request->get_param('jwt');

            // Validating the token
            $result = $service->validate($jwt);

            if (!is_wp_error($result)) {
                $result = AAM::api()->jwt->decode($jwt);
            } else {
                throw new InvalidArgumentException($result->get_error_message());
            }
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Refresh JWT token
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.1
     */
    public function refresh_token($request)
    {
        try {
            _deprecated_function(
                '/aam/v2/jwt/refresh',
                AAM_VERSION,
                'Deprecated /jwt/refresh REST API endpoint will be removed in 7.5.0'
            );

            // Get JWT service
            $service = $this->_get_jwt_service_through_jwt($request);
            $jwt     = $request->get_param('jwt');

            // Validating the token
            $result = $service->validate($jwt);

            if (!is_wp_error($result)) {
                $result = $service->refresh($jwt);
            } else {
                throw new InvalidArgumentException($result->get_error_message());
            }
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Revoke JWT token
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.1
     */
    public function revoke_token($request)
    {
        try {
            _deprecated_function(
                '/aam/v2/jwt/revoke',
                AAM_VERSION,
                'Deprecated /jwt/revoke REST API endpoint will be removed in 7.5.0'
            );

            // Get JWT service
            $service = $this->_get_jwt_service_through_jwt($request);
            $jwt     = $request->get_param('jwt');

            // Validating the token
            $result = $service->validate($jwt);

            if (!is_wp_error($result)) {
                $service->revoke($jwt);

                $result = [
                    'message' => 'Token revoked successfully'
                ];
            } else {
                throw new InvalidArgumentException($result->get_error_message());
            }
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get pre-configured JWT service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_Jwts
     * @access private
     *
     * @version 7.0.1
     */
    private function _get_jwt_service_through_jwt($request)
    {
        $jwt = $request->get_param('jwt');

        // Let's extract user_id from the token
        if (!empty($jwt)) {
            $claims = AAM::api()->jwt->decode($jwt);

            if (!is_wp_error($claims)) {
                // Validating token and ensuring it is not revoked
                $result = AAM::api()->jwts(AAM::api()->access_levels->get(
                        AAM_Framework_Type_AccessLevel::USER,
                        isset($claims['userId']) ? $claims['userId'] : $claims['user_id']
                    ),
                    [ 'error_handling' => 'exception' ]
                );
            } else {
                throw new InvalidArgumentException($claims->get_error_message());
            }
        } else {
            throw new InvalidArgumentException('Invalid JWT token');
        }

        return $result;
    }

}