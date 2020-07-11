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
 * @since 6.6.0 https://github.com/aamplugin/advanced-access-manager/issues/129
 *              https://github.com/aamplugin/advanced-access-manager/issues/100
 *              https://github.com/aamplugin/advanced-access-manager/issues/118
 * @since 6.5.2 Fixed https://github.com/aamplugin/advanced-access-manager/issues/117
 * @since 6.5.0 Enhanced https://github.com/aamplugin/advanced-access-manager/issues/99
 *              Fixed https://github.com/aamplugin/advanced-access-manager/issues/98
 * @since 6.4.0 Added the ability to issue refreshable token via API.
 *              Enhanced https://github.com/aamplugin/advanced-access-manager/issues/71
 * @since 6.3.0 Fixed incompatibility with other plugins that check for RESTful error
 *              status through `rest_authentication_errors` filter
 * @since 6.1.0 Enriched error response with more details
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.6.0
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
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function __construct()
    {
        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
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

        // Hook that initialize the AAM UI part of the service
        if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
            $this->initializeHooks();
        }
    }

    /**
     * Initialize service hooks
     *
     * @return void
     *
     * @since 6.6.0 https://github.com/aamplugin/advanced-access-manager/issues/129
     * @since 6.4.0 Added the ability to issue refreshable token through API.
     *              Enhanced https://github.com/aamplugin/advanced-access-manager/issues/71
     * @since 6.3.0 Fixed bug https://github.com/aamplugin/advanced-access-manager/issues/25
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.6.0
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
        add_filter('aam_auth_response_filter', array($this, 'prepareLoginResponse'), 10, 2);

        // WP Core current user definition
        add_filter('determine_current_user', array($this, 'determineUser'), PHP_INT_MAX);

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
     * @access public
     * @version 6.0.0
     */
    public function registerAPI()
    {
        // Validate JWT token
        register_rest_route('aam/v2', '/jwt/validate', array(
            'methods'  => 'POST',
            'callback' => array($this, 'validateToken'),
            'args' => array(
                'jwt' => array(
                    'description' => __('JWT token.', AAM_KEY),
                    'type'        => 'string',
                )
            ),
        ));
        register_rest_route('aam/v1', '/validate-jwt', array(
            'methods'  => 'POST',
            'callback' => array($this, 'validateTokenDeprecated'),
            'args' => array(
                'jwt' => array(
                    'description' => __('JWT token.', AAM_KEY),
                    'type'        => 'string',
                )
            ),
        ));

        // Refresh JWT token
        register_rest_route('aam/v2', '/jwt/refresh', array(
            'methods'  => 'POST',
            'callback' => array($this, 'refreshToken'),
            'args' => array(
                'jwt' => array(
                    'description' => __('JWT token.', AAM_KEY),
                    'type'        => 'string',
                )
            ),
        ));
        register_rest_route('aam/v1', '/refresh-jwt', array(
            'methods'  => 'POST',
            'callback' => array($this, 'refreshTokenDeprecated'),
            'args' => array(
                'jwt' => array(
                    'description' => __('JWT token.', AAM_KEY),
                    'type'        => 'string',
                )
            ),
        ));

        // Revoke JWT token
        register_rest_route('aam/v2', '/jwt/revoke', array(
            'methods'  => 'POST',
            'callback' => array($this, 'revokeToken'),
            'args' => array(
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
     * @since 6.1.0 Enriched error response with more details
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.1.0
     */
    public function validateToken(WP_REST_Request $request)
    {
        $jwt    = $request->get_param('jwt');
        $result = AAM_Core_Jwt_Issuer::getInstance()->validateToken($jwt);

        if ($result->isValid === true) {
            $response = new WP_REST_Response($result);
        } else {
            $response = new WP_REST_Response(array(
                'code'   => 'rest_jwt_validation_failure',
                'reason' => $result->reason
            ), $result->status);
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
     * @todo Remove in 6.5.0
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
     * @since 6.1.0 Enriched error response with more details
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.1.0
     */
    public function refreshToken(WP_REST_Request $request)
    {
        $jwt    = $request->get_param('jwt');
        $result = AAM_Core_Jwt_Issuer::getInstance()->validateToken($jwt);

        if ($result->isValid === true) {
            if (!empty($result->refreshable)) {
                // calculate the new expiration
                $issuedAt = new DateTime();
                $issuedAt->setTimestamp($result->iat);
                $expires = new DateTime('@' . $result->exp, new DateTimeZone('UTC'));

                $exp = new DateTime();
                $exp->add($issuedAt->diff($expires));

                $new = $this->issueToken($result->userId, $jwt, $exp, true);

                $response = new WP_REST_Response(array(
                    'token'         => $new->token,
                    'token_expires' => $new->claims['exp'],
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
                'reason' => $result->reason
            ), $result->status);
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
     * @since 6.1.0 Enriched error response with more details
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.1.0
     */
    public function revokeToken(WP_REST_Request $request)
    {
        $jwt    = $request->get_param('jwt');
        $claims = AAM_Core_Jwt_Issuer::getInstance()->validateToken($jwt);

        if ($claims->isValid === true) {
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
                'reason' => $claims->reason
            ), $claims->status);
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
     *
     * @return array
     *
     * @since 6.6.0 https://github.com/aamplugin/advanced-access-manager/issues/100
     * @since 6.4.0 Added the ability to issue refreshable token
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.6.0
     */
    public function prepareLoginResponse(array $response, WP_REST_Request $request)
    {
        if ($request->get_param('issueJWT') === true) {
            $refreshable = $request->get_param('refreshableJWT');

            if ($refreshable) {
                $refreshable = user_can(
                    $response['user']->ID, 'aam_issue_refreshable_jwt'
                );

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

            $jwt = $this->issueToken($response['user']->ID, null, null, $refreshable);

            $response['jwt'] = array(
                'token'         => $jwt->token,
                'token_expires' => $jwt->claims['exp']
            );
        }

        return $response;
    }

    /**
     * Issue JWT token
     *
     * @param int     $userId
     * @param string  $replace
     * @param string  $expires
     * @param boolean $refreshable
     *
     * @return object
     *
     * @access public
     * @version 6.0.0
     */
    public function issueToken(
        $userId,
        $replace = null,
        $expires = null,
        $refreshable = false
    ) {
        $result = AAM_Core_Jwt_Issuer::getInstance()->issueToken(
            array(
                'userId'      => $userId,
                'revocable'   => true,
                'refreshable' => $refreshable
            ),
            $expires
        );

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
     * @access public
     * @version 6.0.0
     */
    public function registerToken($userId, $token, $replaceExisting = false)
    {
        $registry = $this->getTokenRegistry($userId);
        $limit    = AAM_Core_Config::get('authentication.jwt.registryLimit', 10);

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
     * @since 6.6.0 https://github.com/aamplugin/advanced-access-manager/issues/118
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.6.0
     */
    public function revokeUserToken($userId, $token)
    {
        $filtered = array();

        foreach($this->getTokenRegistry($userId) as $item) {
            if ($token !== $item) {
                $filtered[] = $item;
            } else {
                // Also delete user session if any is active. The downside here is
                // that if user logged in with different token, he still is going to
                // be logged out because AAM does not track the token that user used
                // to login
                $sessions = WP_Session_Tokens::get_instance($userId);
                $sessions->destroy_all();
            }
        }

        return update_user_option($userId, self::DB_OPTION, $filtered);
    }

    /**
     * Determine current user by JWT
     *
     * @param int $userId
     *
     * @return int
     *
     * @access public
     * @version 6.0.0
     */
    public function determineUser($userId)
    {
        if (empty($userId)) {
            $token = $this->extractToken();

            if (!empty($token)) {
                $result = AAM_Core_Jwt_Issuer::getInstance()->validateToken($token->jwt);

                if ($result->isValid === true) {
                    // Verify that user is can be logged in
                    $user = apply_filters(
                        'aam_verify_user_filter', new WP_User($result->userId)
                    );

                    if (!is_wp_error($user)) {
                        $userId = $result->userId;

                        if ($token->method === 'get') {
                            // Also authenticate user if token comes from query param
                            add_action('init', array($this, 'authenticateUser'), 1);
                        }
                    }
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
     * @access public
     * @version 6.0.0
     */
    public function getJwtClaim($value, $prop)
    {
        $token = $this->extractToken();

        if ($token) {
            $claims = AAM_Core_Jwt_Issuer::getInstance()->extractTokenClaims(
                $token->jwt
            );

            $value = (property_exists($claims, $prop) ? $claims->$prop : null);
        }

        return $value;
    }

    /**
     * Authenticate user with JWT
     *
     * @return void
     *
     * @since 6.5.2 Fixed https://github.com/aamplugin/advanced-access-manager/issues/117
     * @since 6.5.0 Fixed https://github.com/aamplugin/advanced-access-manager/issues/98
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.5.2
     */
    public function authenticateUser()
    {
        $token  = $this->extractToken();
        $claims = AAM_Core_Jwt_Issuer::getInstance()->extractTokenClaims($token->jwt);

        // Check if account is active
        $user = apply_filters('aam_verify_user_filter', new WP_User($claims->userId));

        if (!is_wp_error($user)) {
            wp_set_current_user($claims->userId);
            wp_set_auth_cookie($claims->userId);

            do_action(
                'aam_set_user_expiration_action',
                array_merge(
                    array('expires' => $claims->exp),
                    property_exists($claims, 'trigger') ? (array)$claims->trigger : array()
                )
            );

            do_action('wp_login', $user->user_login, $user);

            // Determine where to redirect user and safely redirect & finally just
            // redirect user to the homepage
            $redirect_to = $this->getFromQuery('redirect_to');

            wp_safe_redirect(
                apply_filters(
                    'login_redirect',
                    (!empty($redirect_to) ? $redirect_to : admin_url()),
                    '',
                    $user
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
     * @since 6.5.0 Enhanced https://github.com/aamplugin/advanced-access-manager/issues/99
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.5.0
     */
    protected function extractToken()
    {
        $container = explode(',', AAM_Core_Config::get(
            'authentication.jwt.container',
            'header,get,post,cookie'
        ));

        foreach ($container as $method) {
            switch (strtolower(trim($method))) {
                case 'header':
                    // Fallback for Authorization header
                    $jwt1 = $this->getFromServer('HTTP_AUTHORIZATION');
                    $jwt2 = $this->getFromServer(AAM_Core_Config::get(
                        'authentication.jwt.header',
                        'HTTP_AUTHENTICATION'
                    ));

                    $jwt  = (!empty($jwt1) ? $jwt1 : $jwt2);
                    break;

                case 'cookie':
                    $jwt = $this->getFromCookie('aam_jwt_token');
                    break;

                case 'post':
                    $jwt = $this->getFromPost('aam-jwt');
                    break;

                case 'get':
                case 'query':
                    $jwt = $this->getFromQuery('aam-jwt');
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

}

if (defined('AAM_KEY')) {
    AAM_Service_Jwt::bootstrap();
}