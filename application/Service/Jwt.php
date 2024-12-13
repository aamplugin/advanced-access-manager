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
    use AAM_Core_Contract_ServiceTrait,
        AAM_Core_Contract_RequestTrait;

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 7.0.0
     */
    const FEATURE_FLAG = 'service.jwt.enabled';

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
        'service.jwt.enabled'          => true,
        'service.jwt.registry_size'    => 10,
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
     *
     * @access protected
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

        $enabled = AAM::api()->config->get(self::FEATURE_FLAG);

        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if ($enabled) {
                add_action('aam_initialize_ui_action', function () {
                    AAM_Backend_Feature_Main_Jwt::register();
                });
            }

            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('JWT Tokens', AAM_KEY),
                    'description' => __('Manage the website authentication with JWT Bearer token. The service facilitates the ability to manage the list of issued JWT token for any user, revoke them or issue new on demand.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 20);
        }

        if ($enabled) {
            $this->initialize_hooks();
        }
    }

    /**
     * Initialize service hooks
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function initialize_hooks()
    {
        if (AAM::isAAM()) {
            add_action('aam_post_edit_user_modal_action', function () {
                if (current_user_can('aam_manage_jwt')) {
                    echo AAM_Backend_View::get_instance()->loadPartial('jwt-login-url');
                }
            });
        }

        add_action('aam_clear_settings_action', function() {
            global $wpdb;

            // Run the query, will return true if deleted, false otherwise
		    $wpdb->delete(
                $wpdb->usermeta,
                array('meta_key' => $wpdb->prefix . AAM_Service_Jwt::DB_OPTION)
            );
        });

        // Register RESTful API
        AAM_Restful_JwtService::bootstrap();

        // Register API endpoint
        add_action('rest_api_init', function() {
            $this->_rest_api_init();
        });

        // Authentication hooks
        add_filter('aam_restful_authentication_args_filter', function ($args) {
            $args['issueJWT'] = array(
                'description' => __('Issue JWT Token', AAM_KEY),
                'type'        => 'boolean',
            );
            $args['refreshableJWT'] = array(
                'description' => __('Issue a refreshable JWT Token', AAM_KEY),
                'type'        => 'boolean',
            );

            return $args;
        });

        add_filter(
            'aam_rest_authenticated_user_data_filter',
            [ $this, 'prepare_login_response' ],
            10,
            3
        );

        // WP Core current user definition
        add_filter('determine_current_user', function($user_id){
            return $this->_determine_current_user($user_id);
        }, PHP_INT_MAX);

        // Fetch specific claim from the JWT token if present
        add_filter('aam_get_jwt_claim', function($value, $prop) {
            return $this->_aam_get_jwt_claim($value, $prop);
        }, 20, 2);
    }

    /**
     * Register JWT RESTful API endpoints
     *
     * @return void
     *
     * @access private
     * @version 7.0.0
     */
    private function _rest_api_init()
    {
        // Validate JWT token
        register_rest_route('aam/v2', '/jwt/validate', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'validateToken'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'jwt' => array(
                    'description' => __('JWT token.', AAM_KEY),
                    'type'        => 'string',
                )
            ),
        ));

        // Refresh JWT token
        register_rest_route('aam/v2', '/jwt/refresh', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'refreshToken'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'jwt' => array(
                    'description' => __('JWT token.', AAM_KEY),
                    'type'        => 'string',
                )
            ),
        ));

        // Revoke JWT token
        register_rest_route('aam/v2', '/jwt/revoke', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'revokeToken'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'jwt' => array(
                    'description' => __('JWT token.', AAM_KEY),
                    'type'        => 'string',
                )
            ),
        ));
    }

    /**
     * Validate JWT token
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @since 6.9.4 https://github.com/aamplugin/advanced-access-manager/issues/238
     * @since 6.9.0 https://github.com/aamplugin/advanced-access-manager/issues/221
     * @since 6.1.0 Enriched error response with more details
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.9.4
     */
    public function validateToken(WP_REST_Request $request)
    {
        $jwt    = $request->get_param('jwt');
        $result = $this->validate($jwt);

        if (!is_wp_error($result)) {
            $response = new WP_REST_Response($result);
        } else {
            $response = new WP_REST_Response(array(
                'code'   => 'rest_jwt_validation_failure',
                'reason' => $result->get_error_message()
            ), 400);
        }

        return $response;
    }

    /**
     * Refresh/renew JWT token
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @since 6.9.8 https://github.com/aamplugin/advanced-access-manager/issues/263
     * @since 6.9.4 https://github.com/aamplugin/advanced-access-manager/issues/238
     * @since 6.9.0 https://github.com/aamplugin/advanced-access-manager/issues/221
     * @since 6.1.0 Enriched error response with more details
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.9.8
     */
    public function refreshToken(WP_REST_Request $request)
    {
        $jwt    = $request->get_param('jwt');
        $result = $this->validate($jwt);

        if (!is_wp_error($result)) {
            if (!empty($result->refreshable)) {
                // calculate the new expiration
                $issuedAt = new DateTime();
                $issuedAt->setTimestamp($result->iat);
                $expires = new DateTime('@' . $result->exp, new DateTimeZone('UTC'));

                $exp = new DateTime();
                $exp->add($issuedAt->diff($expires));

                $token_result = $this->issueToken($result->userId, $jwt, $exp, true);

                $response = new WP_REST_Response(array(
                    'token'         => $token_result->token,
                    'token_expires' => $token_result->claims['exp'],
                ));
            } else {
                $response = new WP_REST_Response(array(
                    'code'   => 'rest_jwt_validation_failure',
                    'reason' =>__('JWT token is not refreshable', AAM_KEY)
                ), 405);
            }
        } else {
            $response = new WP_REST_Response(array(
                'code'   => 'rest_jwt_validation_failure',
                'reason' => $result->get_error_message()
            ), 400);
        }

        return $response;
    }

    /**
     * Revoke JWT token
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @since 6.9.4 https://github.com/aamplugin/advanced-access-manager/issues/238
     * @since 6.9.0 https://github.com/aamplugin/advanced-access-manager/issues/221
     * @since 6.1.0 Enriched error response with more details
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.9.4
     */
    public function revokeToken(WP_REST_Request $request)
    {
        $jwt    = $request->get_param('jwt');
        $claims = $this->validate($jwt);

        if (!is_wp_error($claims)) {
            if ($this->revokeUserToken($claims->userId, $jwt)) {
                $response = new WP_REST_Response(
                    array('message' => 'Token revoked successfully'), 200
                );
            } else {
                $response = new WP_REST_Response(array(
                    'code'   => 'rest_jwt_revoking_failure',
                    'reason' => __('Failed to revoke provided token', AAM_KEY)
                ), 409);
            }
        } else {
            $response = new WP_REST_Response(array(
                'code'   => 'rest_jwt_validation_failure',
                'reason' => $claims->get_error_message()
            ), 400);
        }

        return $response;
    }

    /**
     * Extern authentication request with JWT token
     *
     * The payload with "issueJWT" boolean flag defines if JWT has to be generated or
     * not.
     *
     * @param array           $response
     * @param WP_REST_Request $request
     * @param WP_User         $user
     *
     * @return array
     *
     * @since 6.9.8 https://github.com/aamplugin/advanced-access-manager/issues/263
     * @since 6.9.0 https://github.com/aamplugin/advanced-access-manager/issues/221
     * @since 6.6.2 https://github.com/aamplugin/advanced-access-manager/issues/139
     * @since 6.6.0 https://github.com/aamplugin/advanced-access-manager/issues/100
     * @since 6.4.0 Added the ability to issue refreshable token
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.9.8
     */
    public function prepare_login_response($response, $request, $user)
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

            $token_result = $this->issueToken(
                $user->ID, null, null, $issue_refreshable_jwt
            );

            $response['jwt'] = array(
                'token'         => $token_result->token,
                'token_expires' => $token_result->claims['exp']
            );
        }

        return $response;
    }

    /**
     * Issue JWT token
     *
     * @param int      $userId
     * @param string   $replace
     * @param DateTime $expires
     * @param boolean  $refreshable
     *
     * @return object
     *
     * @since 6.9.8 https://github.com/aamplugin/advanced-access-manager/issues/263
     * @since 6.9.0 https://github.com/aamplugin/advanced-access-manager/issues/221
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.9.8
     */
    public function issueToken(
        $userId,
        $replace = null,
        $expires = null,
        $refreshable = false
    ) {
        $claims = array(
            'userId'      => $userId,
            'revocable'   => true,
            'refreshable' => $refreshable
        );

        if (is_a($expires, DateTime::class)) {
            $claims['exp'] = $expires->getTimestamp();
        }

        $result = AAM_Core_Jwt_Manager::get_instance()->encode($claims);

        // Finally register token so it can be revoked
        $this->registerToken($userId, $result->token, $replace);

        return $result;
    }

    /**
     * Register JWT token to user's registry
     *
     * @param int    $userId
     * @param string $token
     * @param string $replaceExisting
     *
     * @return bool
     *
     * @since 6.9.11 https://github.com/aamplugin/advanced-access-manager/issues/278
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.11
     */
    public function registerToken($userId, $token, $replaceExisting = false)
    {
        $registry = $this->getTokenRegistry($userId);
        $limit    = AAM::api()->config->get('service.jwt.registry_size');

        if ($replaceExisting) {
            // First let's delete existing token
            $filtered = array();
            foreach($registry as $item) {
                if ($item !== $replaceExisting) {
                    $filtered[] = $item;
                }
            }

            // Add new token to the registry
            $filtered[] = $token;

            $result = update_user_option($userId, self::DB_OPTION, $filtered);
        } else {
            // Make sure that we do not overload the user meta
            if (count($registry) >= $limit) {
                array_shift($registry);
            }

            // Add new token to the registry
            $registry[] = $token;

            // Save token
            $result = update_user_option($userId, self::DB_OPTION, $registry);
        }

        return $result;
    }

    /**
     * Get JWT token registry
     *
     * @param int $userId
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function getTokenRegistry($userId)
    {
        $registry = get_user_option(self::DB_OPTION, $userId);

        return (!empty($registry) ? $registry : array());
    }

    /**
     * Revoke JWT token
     *
     * @param int    $userId
     * @param string $token
     *
     * @return bool
     *
     * @since 6.9.0 https://github.com/aamplugin/advanced-access-manager/issues/224
     * @since 6.6.0 https://github.com/aamplugin/advanced-access-manager/issues/118
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.9.0
     */
    public function revokeUserToken($userId, $token)
    {
        $filtered = array();

        foreach($this->getTokenRegistry($userId) as $item) {
            if ($token !== $item) {
                $filtered[] = $item;
            } elseif (get_current_user_id() !== $userId) {
                // Get the last JWT token that was used to authenticate user
                $last_token = get_user_meta(
                    get_current_user_id(), 'aam_auth_token', true
                );

                // Remove all user's sessions because we just revoked the token
                // they used to authenticate
                if ($last_token === $token) {
                    $sessions = WP_Session_Tokens::get_instance($userId);
                    $sessions->destroy_all();
                }
            }
        }

        return update_user_option($userId, self::DB_OPTION, $filtered);
    }

    /**
     * Deleting all the tokens for user
     *
     * @param int $userId
     *
     * @return bool
     *
     * @access public
     * @version 6.9.10
     */
    public function resetTokenRegistry($userId)
    {
        return delete_user_option($userId, self::DB_OPTION);
    }

    /**
     * Determine current user by JWT
     *
     * @param int $userId
     *
     * @return int
     *
     * @access private
     * @version 7.0.0
     */
    private function _determine_current_user($userId)
    {
        if (empty($userId)) {
            $token = $this->_extract_token();

            if (!empty($token)) {
                $result = $this->validate($token->jwt);

                if (!is_wp_error($result)) {
                    // Verify that user is can be logged in
                    $check = $this->_verify_user_status($result->userId);

                    if (!is_wp_error($check)) {
                        $userId = $result->userId;

                        if (in_array(
                            $token->method,
                            [ 'get', 'query', 'query_param' ],
                            true
                        )) {
                            // Also authenticate user if token comes from query param
                            add_action('init', array($this, 'authenticateUser'), 1);
                        }
                    }

                    do_action(
                        'aam_valid_jwt_token_detected_action',
                        $token->jwt,
                        $result
                    );
                }
            }
        }

        return $userId;
    }

    /**
     * Get specific claim from the JWT token
     *
     * @param mixed  $value
     * @param string $prop
     *
     * @return mixed
     *
     * @access private
     * @version 7.0.0
     */
    private function _aam_get_jwt_claim($value, $prop)
    {
        $token   = $this->_extract_token();
        $from_db = false;

        // If token is not found, try to fetch it from DB, but only if user is already
        // authenticated
        if (is_object($token)) {
            $jwt = $token->jwt;
        } elseif (is_user_logged_in()) {
            $jwt     = get_user_meta(get_current_user_id(), 'aam_auth_token', true);
            $from_db = true;
        } else {
            $jwt = null;
        }

        if ($jwt) {
            $claims = $this->validate($jwt);

            if (!is_wp_error($claims)) {
                $value = (property_exists($claims, $prop) ? $claims->$prop : null);
            } elseif ($from_db) { // automatically purge invalid token
                delete_user_meta(wp_get_current_user()->ID, 'aam_auth_token');
            }
        }

        return $value;
    }

    /**
     * Authenticate user with JWT
     *
     * @return void
     *
     * @since 6.9.4 https://github.com/aamplugin/advanced-access-manager/issues/238
     * @since 6.9.0 https://github.com/aamplugin/advanced-access-manager/issues/221
     * @since 6.5.2 https://github.com/aamplugin/advanced-access-manager/issues/117
     * @since 6.5.0 https://github.com/aamplugin/advanced-access-manager/issues/98
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.9.4
     */
    public function authenticateUser()
    {
        $token  = $this->_extract_token();
        $claims = $this->validate($token->jwt);

        if (!is_wp_error($claims)
            && $this->_verify_user_status($claims->userId) === true
        ) {
            wp_set_current_user($claims->userId);
            wp_set_auth_cookie($claims->userId);

            // If we are authenticating with passwordless, that manually set user's
            // expiration attributes
            $data = [ 'expires_at' => $claims->exp ];

            if (property_exists($claims, 'trigger')) {
                $data['trigger'] = [
                    'type'    => $claims->trigger->action,
                    'to_role' => $claims->trigger->meta
                ];
            }

            $user = AAM::api()->user($claims->userId);

            $user->update($data);

            do_action('wp_login', $user->user_login, $user->get_wp_user());

            // Determine where to redirect user and safely redirect & finally just
            // redirect user to the homepage
            $redirect_to = $this->getFromQuery('redirect_to');

            wp_safe_redirect(
                apply_filters(
                    'login_redirect',
                    (!empty($redirect_to) ? $redirect_to : admin_url()),
                    '',
                    $user->get_wp_user()
                )
            );

            // Halt the execution. Redirect should carry user away if this is not
            // a CLI execution (e.g. Unit Test)
            if (php_sapi_name() !== 'cli') {
                exit;
            }
        }
    }

    /**
     * Extract JWT token from the request
     *
     * Based on the `authentication.jwt.container` setting, parse HTTP request and
     * try to extract the JWT token
     *
     * @return object|null
     *
     * @access protected
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
                        $jwt = $this->getFromServer($h);

                        if (!empty($jwt)) {
                            break;
                        }
                    }
                    break;

                case 'cookie':
                    $jwt = $this->getFromCookie($configs->get(
                        'service.jwt.cookie_name'
                    ));
                    break;

                case 'post':
                case 'post_param':
                    $jwt = $this->getFromPost($configs->get(
                        'service.jwt.post_param_name'
                    ));
                    break;

                case 'get':
                case 'query':
                case 'query_param':
                    $jwt = $this->getFromQuery($configs->get(
                        'service.jwt.query_param_name'
                    ));
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
     * Determine if provided token is valid
     *
     * This method adds additional validation layer. Besides checking if token itself
     * is valid (which is done in the core JWT manager), this method verifies that
     * token is part of JWT token registry.
     *
     * @param string $token
     *
     * @return object
     * @since 6.9.4
     */
    protected function validate($token)
    {
        // First level of validation - making sure that the token is properly signed
        // and not expired
        $result = AAM_Core_Jwt_Manager::get_instance()->validate($token);

        if (!is_wp_error($result)) {
            // Second level of validation
            // If token is "revocable", make sure that claimed user still has
            // the token in the meta
            if (!empty($result->revocable)) {
                $registry = $this->getTokenRegistry($result->userId);

                if (!is_array($registry) || !in_array($token, $registry, true)) {
                    $result = new WP_Error(
                        'rest_jwt_validation_failure',
                        __('Token has been revoked', AAM_KEY)
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Verify user's status
     *
     * @param int $user_id
     *
     * @return bool|WP_Error
     *
     * @access private
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