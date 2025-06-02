<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * JWT Token service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Service_Jwt
{
    use AAM_Service_BaseTrait;

    /**
     * JWT Registry DB option
     *
     * @version 7.0.0
     */
    const DB_OPTION = 'aam_jwt_registry';

    /**
     * Default configurations
     *
     * @version 7.0.0
     */
    const DEFAULT_CONFIG = [
        'service.jwt.bearer'           => 'header,query_param,post_param,cookie',
        'service.jwt.header_name'      => 'HTTP_AUTHENTICATION',
        'service.jwt.cookie_name'      => 'aam_jwt_token',
        'service.jwt.post_param_name'  => 'aam-jwt',
        'service.jwt.query_param_name' => 'aam-jwt'
    ];

    /**
     * Constructor
     *
     * @return void
     * @access protected
     *
     * @version 7.0.4
     */
    protected function __construct()
    {
        add_filter('aam_get_config_filter', function($result, $key) {
            if (empty($result) && array_key_exists($key, self::DEFAULT_CONFIG)) {
                $result = self::DEFAULT_CONFIG[$key];
            }

            return $result;
        }, 10, 2);

        // WP Core current user definition
        add_filter('determine_current_user', function($user_id){
            return $this->_determine_current_user($user_id);
        }, PHP_INT_MAX);

        // Register RESTful API
        AAM_Restful_Jwt::bootstrap();

        add_action('init', function() {
            $this->initialize_hooks();
        }, PHP_INT_MAX);
    }

    /**
     * Initialize service hooks
     *
     * @return void
     * @access protected
     *
     * @version 7.0.4
     */
    protected function initialize_hooks()
    {
        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            add_action('aam_initialize_ui_action', function () {
                AAM_Backend_Feature_Main_Jwt::register();
            });

            add_action('aam_post_edit_user_modal_action', function () {
                if (current_user_can('aam_manage_jwt')) {
                    echo AAM_Backend_View::get_instance()->loadPartial(
                        'jwt-login-url'
                    );
                }
            });
        }

        add_action('aam_reset_action', function() {
            global $wpdb;

            // Run the query, will return true if deleted, false otherwise
		    $wpdb->delete($wpdb->usermeta, [
                'meta_key' => $wpdb->prefix . AAM_Framework_Service_Jwts::DB_OPTION
            ]);
        });

        add_filter(
            'aam_rest_authenticated_user_data_filter',
            function($result, $request, $user) {
                return $this->_prepare_login_response($result, $request, $user);
            }, 10, 3
        );

        // Allow other implementations to work with JWT token
        add_filter('aam_current_jwt_filter', function($result) {
            if (empty($result)) {
                $token  = $this->_extract_token();
                $result = !empty($token) ? $token->jwt : null;
            }

            return $result;
        });
    }

    /**
     * Extern authentication request with JWT token
     *
     * @param array           $response
     * @param WP_REST_Request $request
     * @param WP_User         $user
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    private function _prepare_login_response($response, $request, $user)
    {
        $issue_jwt = $this->_get_backward_compatible_request_param(
            'issue_jwt', 'issueJWT', $request
        );
        $issue_refreshable_jwt = $this->_get_backward_compatible_request_param(
            'issue_refreshable_jwt',
            'refreshableJWT',
            $request
        );

        if (is_array($response) && ($issue_jwt || $issue_refreshable_jwt)) {
            if ($issue_refreshable_jwt) {
                if (current_user_can('aam_issue_refreshable_jwt')) {
                    throw new DomainException(
                        'You are not allowed to issue refreshable JWT token'
                    );
                }
            }

            $result = AAM::api()->jwts('user:' . $user->ID)->issue([], [
                'refreshable' => $issue_refreshable_jwt
            ]);

            $response['jwt'] = [
                'token'         => $result['token'],
                'token_expires' => $result['claims']['exp']
            ];
        }

        return $response;
    }

    /**
     * Get backward compatible param from request
     *
     * @param string          $new_param
     * @param string          $legacy_param
     * @param WP_REST_Request $request
     * @param bool            $default
     *
     * @return string|null
     * @access private
     *
     * @version 7.0.1
     */
    private function _get_backward_compatible_request_param(
        $new_param, $legacy_param, $request, $default = false
    ) {
        $result = $request->get_param($new_param);

        if (empty($result)) {
            $result = $request->get_param($legacy_param);

            if (!empty($result)) {
                _deprecated_argument('/authenticate', AAM_VERSION, sprintf(
                    'The REST %s parameter is deprecated. Replace it with %s',
                    $legacy_param,
                    $new_param
                ));
            }
        }

        return is_null($result) ? $default : $result;
    }

    /**
     * Determine current user by JWT
     *
     * @param int $user_id
     *
     * @return int
     * @access private
     *
     * @version 7.0.4
     */
    private function _determine_current_user($user_id)
    {
        if (empty($user_id)) {
            $token = $this->_extract_token();

            if (!empty($token)) {
                $claims = AAM::api()->jwt->decode($token->jwt);

                if (!is_wp_error($claims)) {
                    // Backward compatibility
                    if (array_key_exists('userId', $claims)) {
                        $cuid = $claims['userId'];
                    } else {
                        $cuid = $claims['user_id'];
                    }

                    // Get JWT service and verify that token is valid
                    $service  = AAM::api()->jwts(
                        'user:' . $cuid,
                        [ 'error_handling' => 'wp_error' ]
                    );

                    if (!is_wp_error($service)) {
                        $is_valid = $service->validate($token->jwt);

                        if ($is_valid === true) {
                            if ($this->_is_user_active($cuid)) {
                                $this->_maybe_authenticate($cuid, $token, $claims);

                                $user_id = $cuid;
                            }
                        }
                    }
                }
            }
        }

        return $user_id;
    }

    /**
     * Determine if JWT token is used in password-less URL and if so - authenticate
     *
     * @param int    $user_id
     * @param object $token
     * @param array  $claims
     *
     * @return void
     * @access private
     *
     * @version 7.0.4
     */
    private function _maybe_authenticate($user_id, $token, $claims)
    {
        if (in_array($token->method, [ 'get', 'query', 'query_param' ], true)) {
            $this->_authenticate_user($user_id, $claims);
        }
    }

    /**
     * Authenticate user with JWT
     *
     * @return void
     * @access private
     *
     * @version 7.0.0
     */
    private function _authenticate_user($user_id, $token_claims)
    {
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        // If we are authenticating with passwordless, that manually set user's
        // expiration attributes
        $data = [
            'expiration' => [
                'expires_at' => $token_claims['exp']
            ]
        ];

        if (array_key_exists('trigger', $token_claims)) {
            $data['expiration']['trigger'] = $token_claims['trigger'];
        }

        $user = AAM::api()->user($user_id);

        $user->update($data);

        do_action('wp_login', $user->user_login, $user->get_core_instance());

        // Determine where to redirect user and safely redirect & finally just
        // redirect user to the homepage
        $redirect_to = AAM::api()->misc->get($_GET, 'redirect_to');

        wp_safe_redirect(
            apply_filters(
                'login_redirect',
                (!empty($redirect_to) ? $redirect_to : admin_url()),
                '',
                $user->get_core_instance()
            )
        );

        // Halt the execution. Redirect should carry user away if this is not
        // a CLI execution (e.g. Unit Test)
        if (php_sapi_name() !== 'cli') {
            exit;
        }
    }

    /**
     * Extract JWT token from the request
     *
     * Based on the `authentication.jwt.container` setting, parse HTTP request and
     * try to extract the JWT token
     *
     * @return object|null
     * @access protected
     *
     * @version 7.0.4
     */
    private function _extract_token()
    {
        $configs   = AAM::api()->config;
        $container = wp_parse_list($configs->get('service.jwt.bearer', ''));

        foreach ($container as $method) {
            switch (strtolower(trim($method))) {
                case 'header':
                    // Fallback for Authorization header
                    $possibles = array(
                        'HTTP_AUTHORIZATION',
                        'REDIRECT_HTTP_AUTHORIZATION',
                        $configs->get('service.jwt.header_name')
                    );

                    foreach($possibles as $h) {
                        $jwt = AAM::api()->misc->get($_SERVER, $h);

                        if (!empty($jwt)) {
                            break;
                        }
                    }
                    break;

                case 'cookie':
                    $jwt = AAM::api()->misc->get(
                        $_COOKIE, $configs->get('service.jwt.cookie_name')
                    );
                    break;

                case 'post':
                case 'post_param':
                    $jwt = AAM::api()->misc->get(
                        $_POST, $configs->get('service.jwt.post_param_name')
                    );
                    break;

                case 'get':
                case 'query':
                case 'query_param':
                    $jwt = AAM::api()->misc->get(
                        $_GET, $configs->get('service.jwt.query_param_name')
                    );
                    break;

                default:
                    $jwt = apply_filters('aam_extract_jwt_filter', null, $method);
                    break;
            }

            if (!empty($jwt)) {
                break;
            }
        }

        if (!empty($jwt)) {
            $response = (object) array(
                'jwt'    => preg_replace('/^Bearer /', '', $jwt),
                'method' => $method
            );
        } else {
            $response = null;
        }

        return $response;
    }

    /**
     * Verify user's status
     *
     * @param int $user_id
     *
     * @return bool
     * @access private
     *
     * @version 7.0.4
     */
    private function _is_user_active($user_id)
    {
        $user = AAM::api()->user($user_id);

        // Verify that user is active and is not expired
        return $user->is_user_active() && !$user->is_user_access_expired();
    }

}