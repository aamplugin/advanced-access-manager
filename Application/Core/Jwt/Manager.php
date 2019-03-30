<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM JWT Manager
 * 
 * @package AAM
 * @author  Vasyl Martyniuk <vasyl@vasyltech.com>
 * @since   v5.9.2
 */
class AAM_Core_Jwt_Manager {

    /**
     * Single instance of itself
     * 
     * @var AAM_Core_Jwt_Manager 
     * 
     * @access protected
     * @static
     */
    protected static $instance = null;

    /**
     * Constructor
     * 
     * @return void
     * 
     * @access protected
     */
    protected function __construct() {
        //register API endpoint
        add_action('rest_api_init', array($this, 'registerAPI'));

        //register authentication hook
        add_filter('determine_current_user', array($this, 'determineUser'), 999);
    }
    
    /**
     * Register APIs
     * 
     * @return void
     * 
     * @access public
     */
    public function registerAPI() {
        // Authenticate user
        register_rest_route('aam/v1', '/authenticate', array(
            'methods'  => 'POST',
            'callback' => array($this, 'authenticate'),
            'args' => array(
                'username' => array(
                    'description' => __('Valid username.', AAM_KEY),
                    'type'        => 'string',
                ),
                'password' => array(
                    'description' => __('Valid password.', AAM_KEY),
                    'type'        => 'string',
                )
            ),
        ));
        
        // Validate JWT token
        register_rest_route('aam/v1', '/validate-jwt', array(
            'methods'  => 'POST',
            'callback' => array($this, 'validateToken'),
            'args' => array(
                'jwt' => array(
                    'description' => __('JWT token.', AAM_KEY),
                    'type'        => 'string',
                )
            ),
        ));

        // Refresh JWT token
        register_rest_route('aam/v1', '/refresh-jwt', array(
            'methods'  => 'POST',
            'callback' => array($this, 'refreshToken'),
            'args' => array(
                'jwt' => array(
                    'description' => __('JWT token.', AAM_KEY),
                    'type'        => 'string',
                )
            ),
        ));
    }
    
    /**
     * Authenticate user
     * 
     * @param WP_REST_Request $request
     * 
     * @return WP_REST_Response
     * 
     * @access public
     */
    public function authenticate(WP_REST_Request $request) {
        $username = $request->get_param('username');
        $password = $request->get_param('password');
        $response = new WP_REST_Response();

        $auth   = new AAM_Core_Jwt_Auth();
        $result = $auth->authenticateWithCredentials($username, $password);

        if (!empty($result->error)) {
            $response->status = 403;
            $response->data = new WP_Error(
                'rest_jwt_auth_failure',
                strip_tags($result->reason)
            );
        } else {
            $jwt = $this->issueToken($result->user->ID);

            $response->status = 200;
            $response->data = array(
                'token'         => $jwt->token,
                'token_expires' => $jwt->claims['exp'],
                'user'          => $result->user
            );
        }
        
        return apply_filters('aam-jwt-response-filter', $response);
    }
    
    /**
     * Validate JWT token
     * 
     * @param WP_REST_Request $request
     * 
     * @return WP_REST_Response
     * 
     * @access public
     */
    public function validateToken(WP_REST_Request $request) {
        $jwt      = $request->get_param('jwt');
        $issuer   = new AAM_Core_Jwt_Issuer();
        $response = new WP_REST_Response();

        $result = $issuer->validateToken($jwt);

        if ($result->status === 'valid') {
            $response->status = 200;
            $response->data   = $result;
        } else {
            $response->status = 400;
            $response->data   = new WP_Error(
                'rest_jwt_validation_failure',
                $result->reason
            );
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
     * @access public
     */
    public function refreshToken(WP_REST_Request $request) {
        $jwt      = $request->get_param('jwt');
        $issuer   = new AAM_Core_Jwt_Issuer();
        $response = new WP_REST_Response();

        $result = $issuer->validateToken($jwt);

        if ($result->status === 'valid') {
            // calculate the new expiration
            $issuedAt = new DateTime();
            $issuedAt->setTimestamp($result->iat);
            $expires = DateTime::createFromFormat('m/d/Y, H:i O', $result->exp);

            $exp = new DateTime();
            $exp->add($issuedAt->diff($expires));

            $new = $this->issueToken($result->userId, $jwt, $exp);

            $response->status = 200;
            $response->data = array(
                'token'         => $new->token,
                'token_expires' => $new->claims['exp'],
            );
        } else {
            $response->status = 400;
            $response->data   = new WP_Error(
                'rest_jwt_validation_failure',
                $result->reason
            );
        }

        return $response;
    }
    
    /**
     * Determine current user by JWT
     * 
     * @param int $userId
     * 
     * @return int
     * 
     * @access public
     */
    public function determineUser($userId) {
        if (empty($userId)) {
            $token  = $this->extractJwt();

            if (!empty($token)) {
                $issuer = new AAM_Core_Jwt_Issuer();
                $result = $issuer->validateToken($token->jwt);

                if ($result->status === 'valid') {
                    $userId = $result->userId;
                    $this->possiblyLoginUser($token->method, $result);
                }
            }
        }
        
        return $userId;
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
     */
    public function registerToken($userId, $token, $replaceExisting = false) {
        $registry = $this->getTokenRegistry($userId);
        $limit    = AAM_Core_Config::get('authentication.jwt.registryLimit', 10);

        if ($replaceExisting) {
            $result = update_user_meta($userId, 'aam-jwt', $token, $replaceExisting);
        } else {
            // Make sure that we do not overload the user meta
            if (count($registry) >= $limit) {
                $this->revokeToken($userId, array_shift($registry));
            }

            // Save token
            $result = add_user_meta($userId, 'aam-jwt', $token);
        }

        
        return $result;
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
     */
    public function revokeToken($userId, $token) {
        $result   = false;
        $registry = $this->getTokenRegistry($userId);

        if (in_array($token, $registry, true)) {
            $result = delete_user_meta($userId, 'aam-jwt', $token);
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
     */
    public function getTokenRegistry($userId) { 
        $registry = get_user_meta($userId, 'aam-jwt', false);
        
        return (!empty($registry) ? $registry : array());
    }

    /**
     * Issue JWT token
     *
     * @param int    $userId
     * @param string $replace
     * @param string $expires
     * 
     * @return object
     * 
     * @access protected
     */
    protected function issueToken($userId, $replace = null, $expires = null) {
        $issuer = new AAM_Core_Jwt_Issuer();
        $result = $issuer->issueToken(
            array('userId' => $userId, 'revocable' => true), $expires
        );

        // Finally register token so it can be revoked
        $this->registerToken($userId, $result->token, $replace);

        return $result;
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
     */
    protected function extractJwt() {
        $container = explode(',', AAM_Core_Config::get(
            'authentication.jwt.container', 'header,post,query,cookie'
        ));
        
        $jwt = null;
        
        foreach($container as $method) {
            switch(strtolower(trim($method))) {
                case 'header':
                    $jwt = AAM_Core_Request::server('HTTP_AUTHENTICATION');
                    break;
                
                case 'cookie':
                    $jwt = AAM_Core_Request::cookie('aam-jwt');
                    break;
                
                case 'query':
                    $jwt = AAM_Core_Request::get('aam-jwt');
                    break;
                
                case 'post':
                    $jwt = AAM_Core_Request::post('aam-jwt');
                    break;
                
                default:
                    $jwt = apply_filters('aam-get-jwt-filter', null, $method);
                    break;
            }
            
            if (!is_null($jwt)) {
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
     * Also login user if HTTP request is get and JWT was extracted from query params
     *
     * @param string $container
     * @param object $claims
     * 
     * @return void
     * 
     * @access protected
     */
    protected function possiblyLoginUser($container, $claims) {
        // Also login user if REQUEST_METHOD is GET
        $method = AAM_Core_Request::server('REQUEST_METHOD');

        if ($container === 'query' && ($method === 'GET')) {
            $exp = get_user_meta($claims->userId, 'aam_user_expiration', true);

            // Do it only once
            if (empty($exp)) {
                wp_set_current_user($claims->userId);
                wp_set_auth_cookie($claims->userId);

                // TODO: Remove June 2020
                $exp = (is_numeric($claims->exp) ? date('m/d/Y, H:i O', $claims->exp) : $claims->exp);

                update_user_meta(
                    $claims->userId, 
                    'aam_user_expiration',
                    $exp . '|logout|'
                );
                do_action('wp_login', '', wp_get_current_user());
            }
        }
    }

    /**
     * Get single instance of itself
     * 
     * @return AAM_Core_Jwt_Manager
     * 
     * @access public
     * @static
     */
    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }
    
    /**
     * Bootstrap AAM JWT Manager
     * 
     * @return AAM_Core_Jwt_Manager
     * 
     * @access public
     * @static
     */
    public static function bootstrap() {
        return self::getInstance();
    }

}