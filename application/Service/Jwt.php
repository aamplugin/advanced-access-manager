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
     * @version 7.0.0
     */
    protected function __construct()
    {
        add_filter('aam_get_config_filter', function($result, $key) {
            if (is_null($result) && array_key_exists($key, self::DEFAULT_CONFIG)) {
                $result = self::DEFAULT_CONFIG[$key];
            }

            return $result;
        }, 10, 2);

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

        $this->initialize_hooks();
    }

    /**
     * Initialize service hooks
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function initialize_hooks()
    {
        add_action('aam_reset_action', function() {
            global $wpdb;

            // Run the query, will return true if deleted, false otherwise
		    $wpdb->delete($wpdb->usermeta, [
                'meta_key' => $wpdb->prefix . AAM_Framework_Service_Jwts::DB_OPTION
            ]);
        });

        // Register RESTful API
        AAM_Restful_Jwt::bootstrap();

        add_filter(
            'aam_rest_authenticated_user_data_filter',
            function($result, $request, $user) {
                return $this->_prepare_login_response($result, $request, $user);
            }, 10, 3
        );

        // WP Core current user definition
        add_filter('determine_current_user', function($user_id){
            return $this->_determine_current_user($user_id);
        }, PHP_INT_MAX);

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
        $issue_jwt             = $request->get_param('issue_jwt');
        $issue_refreshable_jwt = $request->get_param('issue_refreshable_jwt');

        if (is_array($response) && ($issue_jwt || $issue_refreshable_jwt)) {
            if ($issue_refreshable_jwt) {
                if (current_user_can('aam_issue_refreshable_jwt')) {
                    throw new DomainException(
                        'Current user is not allowed to issue refreshable JWT token'
                    );
                }
            }

            $result = AAM::api()->jwts('user:' . $user->ID)->issue([], [
                'refreshable' => $issue_refreshable_jwt
            ]);

            $response['jwt'] = array(
                'token'         => $result['token'],
                'token_expires' => $result['claims']['exp']
            );
        }

        return $response;
    }

    /**
     * Determine current user by JWT
     *
     * @param int $user_id
     *
     * @return int
     * @access private
     *
     * @version 7.0.0
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
                    $is_valid = AAM::api()->jwts(
                        'user:' . $cuid, [ 'error_handling' => 'wp_error' ]
                    )->validate($token->jwt);

                    if ($is_valid === true) {
                        $is_active = $this->_verify_user_status($cuid);

                        if ($is_active === true) {
                            if (in_array(
                                $token->method,
                                [ 'get', 'query', 'query_param' ],
                                true
                            )) {
                                // Also authenticate user if token comes from query
                                // param
                                add_action('init', function() use ($cuid, $claims) {
                                    $this->_authenticate_user($cuid, $claims);
                                }, 1);
                            }

                            $user_id = $cuid;
                        }
                    }
                }
            }
        }

        return $user_id;
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
     * @version 7.0.0
     */
    private function _extract_token()
    {
        $configs   = AAM::api()->config;
        $container = wp_parse_list($configs->get('service.jwt.bearer'));

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
     * @return bool|WP_Error
     * @access private
     *
     * @version 7.0.0
     */
    private function _verify_user_status($user_id)
    {
        $result = true;
        $user   = AAM::api()->user($user_id);

        // Step #1. Verify that user is active
        if (!$user->is_user_active()) {
            $result = new WP_Error(
                'inactive_user',
                '[ERROR]: User is inactive. Contact the administrator.'
            );
        }

        // Step #2. Verify that user is not expired
        if ($user->is_user_access_expired()) {
            $result = new WP_Error(
                'inactive_user',
                '[ERROR]: User access is expired. Contact the administrator.'
            );
        }

        return $result;
    }

}