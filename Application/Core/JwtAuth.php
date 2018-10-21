<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM JWT Authentication
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_JwtAuth {

    /**
     * Single instance of itself
     * 
     * @var AAM_Core_JwtAuth 
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
        add_filter('determine_current_user', array($this, 'determineCurrentUser'), 999);
    }
    
    /**
     * Register APIs
     * 
     * @return void
     * 
     * @access public
     */
    public function registerAPI() {
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
        
        // try to authenticate user
        $result = AAM_Core_Login::getInstance()->execute(array(
            'user_login'    => $username,
            'user_password' => $password
        ), false);
        
        $response = new WP_REST_Response();
        
        if ($result['status'] === 'success') { // generate token
            try {
                $token = $this->generateJWT($result['user']->ID);
                
                $response->status = 200;
                $response->data = array(
                    'token'         => $token->token,
                    'token_expires' => $token->claims['exp'],
                    'user'          => $result['user']
                );
            } catch (Exception $ex) {
                $response->status = 400;
                $response->data = new WP_Error(
                    'rest_jwt_empty_secret_key',
                    $ex->getMessage()
                );
            }
        } else {
            $response->data = $result['error'];
            $response->status = 403;
        }
        
        return apply_filters('aam-jwt-response-filter', $response);
    }
    
    /**
     * Generate JWT token
     * 
     * @param int $userId
     * 
     * @return stdClass
     * 
     * @access public
     * @throws Exception
     */
    public function generateJWT($userId, $container = 'header') {
        $key       = AAM_Core_Config::get('authentication.jwt.secret', SECURE_AUTH_KEY);
        $expire    = AAM_Core_Config::get('authentication.jwt.expires', 86400);
        $container = AAM_Core_Config::get('authentication.jwt.container', $container);
        $alg       = AAM_Core_Config::get('authentication.jwt.algorithm', 'HS256');
        
        if ($key) {
            $claims = apply_filters('aam-jwt-claims-filter', array(
                "iat"    => time(),
                'exp'    => time() + $expire, // by default expires in 1 day
                'userId' => $userId,
            ));
            
            $token = Firebase\JWT\JWT::encode($claims, $key, $alg);
            
            if ($container === 'cookie') {
                setcookie(
                    'aam-jwt', 
                    $token, 
                    time() + $expire,
                    '/', 
                    parse_url(get_bloginfo('url'), PHP_URL_HOST), 
                    is_ssl(),
                    AAM_Core_Config::get('authentication.jwt.cookie.httpOnly', false)
                );
            }
        } else {
            Throw new Exception(
                __('JWT Authentication is enabled but secret key is not defined', AAM_KEY)
            );
        }
        
        return (object) array(
            'token'  => $token,
            'claims' => $claims
        );
    }
    
    /**
     * 
     * @param type $result
     */
    public function determineCurrentUser($result) {
        $token = $this->extractJwt();
        $key   = AAM_Core_Config::get('authentication.jwt.secret', SECURE_AUTH_KEY);
        
        if ($token) {
            try {
                $claims = Firebase\JWT\JWT::decode(
                        $token, $key, array_keys(Firebase\JWT\JWT::$supported_algs)
                );
                
                if (isset($claims->userId)) {
                    $result = $claims->userId;
                }
            } catch (Exception $ex) {
                // Do nothing
            }
        }
        
        return $result;
    }
    
    /**
     * 
     * @return type
     */
    protected function extractJwt() {
        $container = AAM_Core_Config::get('authentication.jwt.container', 'header');
        
        if ($container === 'header') {
            $jwt = apply_filters(
                'aam-jwt-authentication-header-filter', 
                AAM_Core_Request::server('HTTP_AUTHENTICATION')
            );
        } elseif ($container === 'cookie') {
            $jwt = apply_filters(
                'aam-jwt-authentication-cookie-filter', 
                AAM_Core_Request::cookie('aam-jwt')
            );
        } else {
            AAM_Core_Console::add(
                sprint_f(
                    __('Invalid value %s for property %s', AAM_KEY), 
                    $container, 
                    'authentication.jwt.container'
                )
            );
        }
        
        return (!empty($jwt) ? preg_replace('/^Bearer /', '', $jwt) : null);
    }
    
    /**
     * Get single instance of itself
     * 
     * @return AAM_Core_JwtAuth
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
     * Bootstrap AAM JWT Authentication feature
     * 
     * @return AAM_Core_JwtAuth
     * 
     * @access public
     * @static
     */
    public static function bootstrap() {
        return self::getInstance();
    }

}