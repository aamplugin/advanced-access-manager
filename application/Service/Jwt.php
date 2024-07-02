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
 * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/287
 * @since 6.9.11 https://github.com/aamplugin/advanced-access-manager/issues/278
 * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/273
 * @since 6.9.8  https://github.com/aamplugin/advanced-access-manager/issues/263
 * @since 6.9.4  https://github.com/aamplugin/advanced-access-manager/issues/238
 * @since 6.9.0  https://github.com/aamplugin/advanced-access-manager/issues/221
 *               https://github.com/aamplugin/advanced-access-manager/issues/224
 * @since 6.6.2  https://github.com/aamplugin/advanced-access-manager/issues/139
 * @since 6.6.1  https://github.com/aamplugin/advanced-access-manager/issues/136
 * @since 6.6.0  https://github.com/aamplugin/advanced-access-manager/issues/129
 *               https://github.com/aamplugin/advanced-access-manager/issues/100
 *               https://github.com/aamplugin/advanced-access-manager/issues/118
 * @since 6.5.2  https://github.com/aamplugin/advanced-access-manager/issues/117
 * @since 6.5.0  https://github.com/aamplugin/advanced-access-manager/issues/99
 *               https://github.com/aamplugin/advanced-access-manager/issues/98
 * @since 6.4.0  Added the ability to issue refreshable token via API.
 *               https://github.com/aamplugin/advanced-access-manager/issues/71
 * @since 6.3.0  Fixed incompatibility with other plugins that check for RESTful error
 *               status through `rest_authentication_errors` filter
 * @since 6.1.0  Enriched error response with more details
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.12
 */
class AAM_Service_Jwt
{
    use AAM_Core_Contract_ServiceTrait,
        AAM_Core_Contract_RequestTrait;

    /**
     * Service alias
     *
     * Is used to get service instance if it is enabled
     *
     * @version 6.4.0
     */
    const SERVICE_ALIAS = 'jwt';

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.0.0
     */
    const FEATURE_FLAG = 'core.service.jwt.enabled';

    /**
     * JWT Registry DB option
     *
     * @version 6.0.0
     */
    const DB_OPTION = 'aam_jwt_registry';

    /**
     * Options aliases
     *
     * @version 6.9.11
     */
    const OPTION_ALIAS = array(
        'service.jwt.registry_size' => 'authentication.jwt.registryLimit',
        'service.jwt.bearer'        => 'authentication.jwt.container',
        'service.jwt.header_name'   => 'authentication.jwt.header'
    );

    /**
     * Default configurations
     *
     * @version 6.9.34
     */
    const DEFAULT_CONFIG = [
        'core.service.jwt.enabled'     => true,
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
     * @version 6.0.0
     */
    protected function __construct()
    {
        add_filter('aam_get_config_filter', function($result, $key) {
            if (is_null($result) && array_key_exists($key, self::DEFAULT_CONFIG)) {
                $result = self::DEFAULT_CONFIG[$key];
            }

            return $result;
        }, 10, 2);

        $enabled = AAM_Framework_Manager::configs()->get_config(self::FEATURE_FLAG);

        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if ($enabled) {
                add_action('aam_init_ui_action', function () {
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
            $this->initializeHooks();
        }
    }

    /**
     * Initialize service hooks
     *
     * @return void
     *
     * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/273
     * @since 6.6.0  https://github.com/aamplugin/advanced-access-manager/issues/129
     * @since 6.4.0  Added the ability to issue refreshable token through API.
     * @since 6.4.0  https://github.com/aamplugin/advanced-access-manager/issues/71
     * @since 6.3.0  https://github.com/aamplugin/advanced-access-manager/issues/25
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.10
     */
    protected function initializeHooks()
    {
        if (AAM::isAAM()) {
            add_action('aam_post_edit_user_modal_action', function () {
                if (current_user_can('aam_manage_jwt')) {
                    echo AAM_Backend_View::getInstance()->loadPartial('jwt-login-url');
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
        add_action('rest_api_init', array($this, 'registerAPI'));

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
            'aam_auth_response_filter', array($this, 'prepareLoginResponse'), 10, 3
        );

        // WP Core current user definition
        add_filter(
            'determine_current_user', array($this, 'determineUser'), PHP_INT_MAX
        );

        // Fetch specific claim from the JWT token if present
        add_filter('aam_get_jwt_claim', array($this, 'getJwtClaim'), 20, 2);

        // Service fetch
        $this->registerService();
    }

    /**
     * Register JWT RESTful API endpoints
     *
     * @return void
     *
     * @since 6.6.1 Fixed https://github.com/aamplugin/advanced-access-manager/issues/136
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.6.1
     */
    public function registerAPI()
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
        register_rest_route('aam/v1', '/validate-jwt', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'validateTokenDeprecated'),
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
        register_rest_route('aam/v1', '/refresh-jwt', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'refreshTokenDeprecated'),
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
     * Validate JWT Token
     *
     * Deprecated endpoint that is replaced with V2
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @version 6.0.0
     */
    public function validateTokenDeprecated(WP_REST_Request $request)
    {
        _deprecated_function('aam/v1/validate-jwt', '6.0.0', 'aam/v2/jwt/validate');

        return $this->validateToken($request);
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
     * Refresh JWT Token
     *
     * Deprecated endpoint that is replaced with V2
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @version 6.0.0
     * @todo Remove in 7.0.0
     */
    public function refreshTokenDeprecated(WP_REST_Request $request)
    {
        _deprecated_function('aam/v1/refresh-jwt', '6.0.0', 'aam/v2/jwt/refresh');

        return $this->refreshToken($request);
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
    public function prepareLoginResponse(
        array $response, WP_REST_Request $request, $user
    ) {
        if ($request->get_param('issueJWT') === true) {
            $refreshable = $request->get_param('refreshableJWT');

            if ($refreshable) {
                $refreshable = user_can($user->ID, 'aam_issue_refreshable_jwt');

                if ($refreshable === false) {
                    throw new Exception(
                        __('Current user is not allowed to issue refreshable JWT token', AAM_KEY),
                        400
                    );
                }
            }

            // Include also roles
            if ($request->get_param('includeRoles') === true) {
                add_filter('aam_jwt_claims_filter', function($claims) {
                    $user = get_user_by('ID', $claims['userId']);
                    $claims['roles'] = $user->roles;

                    return $claims;
                });
            }

            $token_result = $this->issueToken($user->ID, null, null, $refreshable);

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

        $result = AAM_Core_Jwt_Manager::getInstance()->encode($claims);

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
        $limit    = $this->_getConfigOption('service.jwt.registry_size', 10);

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
     * @since 6.9.11 https://github.com/aamplugin/advanced-access-manager/issues/278
     * @since 6.9.4  https://github.com/aamplugin/advanced-access-manager/issues/238
     * @since 6.9.0  https://github.com/aamplugin/advanced-access-manager/issues/221
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.11
     */
    public function determineUser($userId)
    {
        if (empty($userId)) {
            $token = $this->extractToken();

            if (!empty($token)) {
                $result = $this->validate($token->jwt);

                if (!is_wp_error($result)) {
                    // Verify that user is can be logged in
                    $user = AAM_Framework_Manager::users([
                        'error_handling' => 'wp_error'
                    ])->verify_user_state($result->userId);

                    if (!is_wp_error($user)) {
                        $userId = $result->userId;

                        if (in_array(
                            $token->method, array('get', 'query', 'query_param'), true)
                        ) {
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
     * @since 6.9.4 https://github.com/aamplugin/advanced-access-manager/issues/238
     * @since 6.9.0 https://github.com/aamplugin/advanced-access-manager/issues/221
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.9.4
     */
    public function getJwtClaim($value, $prop)
    {
        $token   = $this->extractToken();
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
        $token  = $this->extractToken();
        $claims = $this->validate($token->jwt);

        if (!is_wp_error($claims)) {
            // Check if account is active
            $user = AAM_Framework_Manager::users([
                'error_handling' => 'wp_error'
            ])->verify_user_state($claims->userId);
        }

        if (isset($user) && !is_wp_error($user)) {
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

            AAM_Framework_Manager::users()->update($claims->userId, $data);

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
     * @since 6.9.11 https://github.com/aamplugin/advanced-access-manager/issues/278
     * @since 6.5.0  https://github.com/aamplugin/advanced-access-manager/issues/99
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.11
     */
    protected function extractToken()
    {
        $container = explode(',', $this->_getConfigOption(
            'service.jwt.bearer',
            'header,query_param,post_param,cookie'
        ));

        foreach ($container as $method) {
            switch (strtolower(trim($method))) {
                case 'header':
                    // Fallback for Authorization header
                    $possibles = array(
                        'HTTP_AUTHORIZATION',
                        'REDIRECT_HTTP_AUTHORIZATION',
                        $this->_getConfigOption(
                            'service.jwt.header_name', 'HTTP_AUTHENTICATION'
                        )
                    );

                    foreach($possibles as $h) {
                        $jwt = $this->getFromServer($h);

                        if (!empty($jwt)) {
                            break;
                        }
                    }
                    break;

                case 'cookie':
                    $jwt = $this->getFromCookie($this->_getConfigOption(
                        'service.jwt.cookie_name', 'aam_jwt_token'
                    ));
                    break;

                case 'post':
                case 'post_param':
                    $jwt = $this->getFromPost($this->_getConfigOption(
                        'service.jwt.post_param_name', 'aam-jwt'
                    ));
                    break;

                case 'get':
                case 'query':
                case 'query_param':
                    $jwt = $this->getFromQuery($this->_getConfigOption(
                        'service.jwt.query_param_name', 'aam-jwt'
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
        $result = AAM_Core_Jwt_Manager::getInstance()->validate($token);

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
     * Get configuration option
     *
     * @param string $option
     * @param mixed  $default
     *
     * @return mixed
     *
     * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/287
     * @since 6.9.11 Initial implementation of the method
     *
     * @access private
     * @version 6.9.12
     */
    private function _getConfigOption($option, $default = null)
    {
        $value = AAM_Framework_Manager::configs()->get_config($option);

        if (is_null($value) && array_key_exists($option, self::OPTION_ALIAS)) {
            $value = AAM_Framework_Manager::configs()->get_config(
                self::OPTION_ALIAS[$option]
            );
        }

        return is_null($value) ? $default : $value;
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_Jwt::bootstrap();
}