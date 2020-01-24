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
 * @since 6.3.0 Fixed incompatibility with other plugins that check for RESTful error
 *              status through `rest_authentication_errors` filter
 * @since 6.1.0 Enriched error response with more details
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.3.0
 */
class AAM_Service_Jwt
{
    use AAM_Core_Contract_ServiceTrait,
        AAM_Core_Contract_RequestTrait;

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
     * @since 6.3.0 Fixed bug https://github.com/aamplugin/advanced-access-manager/issues/25
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.3.0
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

            return $args;
        });
        add_filter('aam_auth_response_filter', array($this, 'prepareLoginResponse'), 10, 2);
        add_action('set_logged_in_cookie', array($this, 'setJwtCookie'), 10, 6);

        // WP Core current user definition
        add_filter('determine_current_user', array($this, 'determineUser'), PHP_INT_MAX);

        // Delete JWT cookie if it is set
        add_action('wp_logout', function() {
            if ($this->getFromCookie('aam_jwt_token')) {
                setcookie(
                    'aam_jwt_token',
                    null,
                    time() - 1,
                    COOKIEPATH,
                    COOKIE_DOMAIN,
                    is_ssl(),
                    true
                );
            }
        });

        // Fetch specific claim from the JWT token if present
        add_filter('aam_get_jwt_claim', array($this, 'getJwtClaim'), 20, 2);
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
     * @todo Remove in 6.5.0
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
     * @access public
     * @version 6.0.0
     */
    public function prepareLoginResponse(array $response, WP_REST_Request $request)
    {
        if ($request->get_param('issueJWT') === true) {
            $jwt = $this->issueToken($response['user']->ID);

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
     * @access public
     * @version 6.0.0
     */
    public function revokeUserToken($userId, $token)
    {
        $filtered = array();

        foreach($this->getTokenRegistry($userId) as $item) {
            if ($token !== $item) {
                $filtered[] = $item;
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
     * @access public
     * @version 6.0.0
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

            // finally just redirect user to the homepage
            wp_safe_redirect(get_home_url());
            exit;
        }
    }

    /**
     * Set custom cookie with JWT
     *
     * This can be used later by services like Access Policy to dynamically
     * evaluate conditions based on claims
     *
     * @param string $logged_in_cookie
     * @param int    $expire
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function setJwtCookie($logged_in_cookie, $expire)
    {
        if (apply_filters('send_auth_cookies', true)) {
            $token = $this->extractToken();

            if (!empty($token)) {
                setcookie(
                    'aam_jwt_token',
                    $token->jwt,
                    $expire,
                    COOKIEPATH,
                    COOKIE_DOMAIN,
                    is_ssl(),
                    true
                );
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
     * @version 6.0.0
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
                    $jwt = $this->getFromServer('HTTP_AUTHENTICATION');
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