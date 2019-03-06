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
            'callback' => array($this, 'validateJWT'),
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
        
        // try to authenticate user
        $result = AAM_Core_Login::getInstance()->execute(array(
            'user_login'    => $username,
            'user_password' => $password
        ), false);
        
        $response = new WP_REST_Response();
        
        if ($result['status'] === 'success') { // generate token
            try {
                $token = $this->issueJWT($result['user']->ID);
                
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
            $response->data = $result['reason'];
            $response->status = 403;
        }
        
        return apply_filters('aam-jwt-response-filter', $response);
    }
    
    /**
     * 
     * @param WP_REST_Request $request
     */
    public function validateJWT(WP_REST_Request $request) {
        $jwt    = $request->get_param('jwt');
        $key    = AAM_Core_Config::get('authentication.jwt.secret', SECURE_AUTH_KEY);
        
        $response = new WP_REST_Response(array(
            'status' => 'invalid'
        ), 400);
        
        if (!empty($jwt)) {
            try {
                $claims = Firebase\JWT\JWT::decode(
                    $jwt, $key, array_keys(Firebase\JWT\JWT::$supported_algs)
                );
                
                $response->status = 200;
                $response->data   = array(
                    'status'        => 'valid',
                    'token_expires' => $claims->exp
                );
            } catch (Exception $ex) {
                $response->data['reason'] = $ex->getMessage();
            }
        }
        
        return $response;
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
    public function issueJWT($userId, $container = 'header') {
        $container = explode(
            ',', AAM_Core_Config::get('authentication.jwt.container', $container)
        );
        
        $token = $this->generateJWT($userId);
        
        if (in_array('cookie', $container, true)) {
            setcookie(
                'aam-jwt', 
                $token->token, 
                $token->claims['exp'],
                '/', 
                parse_url(get_bloginfo('url'), PHP_URL_HOST), 
                is_ssl(),
                AAM_Core_Config::get('authentication.jwt.cookie.httpOnly', false)
            );
        }
        
        return $token;
    }
    
    /**
     * Generate the token
     * 
     * @param int $userId
     * @param int $expires
     * 
     * @return stdObject
     * 
     * @access public
     * @throws Exception
     */
    public static function generateJWT($userId, $expires = null) {
        $key     = AAM_Core_Config::get('authentication.jwt.secret', SECURE_AUTH_KEY);
        $expire  = AAM_Core_Config::get('authentication.jwt.expires', $expires);
        $alg     = AAM_Core_Config::get('authentication.jwt.algorithm', 'HS256');
        
        if (!empty($expire)) {
            $time = DateTime::createFromFormat('m/d/Y, H:i O', $expires);
        } else {
            $time = new DateTime('+24 hours');
        }
        
        if ($key) {
            $claims = apply_filters('aam-jwt-claims-filter', array(
                "iat"       => time(),
                'exp'       => $time->format('U'),
                'userId'    => $userId
            ));
            
            $token = Firebase\JWT\JWT::encode($claims, $key, $alg);
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
        
        if (!empty($token['jwt'])) {
            try {
                $claims = Firebase\JWT\JWT::decode(
                        $token['jwt'], $key, array_keys(Firebase\JWT\JWT::$supported_algs)
                );
                
                if (isset($claims->userId)) {
                    $result = $claims->userId;
                    
                    // Also login user if REQUEST_METHOD is GET
                    if ($token['method'] === 'query' 
                            && AAM_Core_Request::server('REQUEST_METHOD') === 'GET') {
                        wp_set_current_user($claims->userId);
                        wp_set_auth_cookie($claims->userId);
                        
                        $exp = get_user_meta($claims->userId, 'aam_user_expiration', true);
                        if (empty($exp)) {
                            update_user_meta(
                                $claims->userId, 
                                'aam_user_expiration',
                                date('m/d/Y, H:i O', $claims->exp) . '|logout|'
                            );
                        }
                        
                        do_action('wp_login', '', wp_get_current_user());
                    }
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
        
        return array(
            'jwt'    => preg_replace('/^Bearer /', '', $jwt),
            'method' => $method
        );
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