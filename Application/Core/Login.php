<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM Core login
 * 
 * @package AAM
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 */
class AAM_Core_Login {

    /**
     * AAM Login flag
     * 
     * Is used to indicate that the user authentication process is handled by
     * AAM plugin. Important to differentiate to avoid redirects
     * 
     * @var boolean
     * 
     * @access protected 
     */
    protected $aamLogin = false;
    
    /**
     * Single instance of itself
     * 
     * @var AAM_Core_Login 
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
        // Fires after the user has successfully logged in
        add_action('wp_login', array($this, 'login'), 10, 2);
        
        // Fired after the user has been logged out successfully
        add_action('wp_logout', array($this, 'logout'));
        
        //user login control
        add_filter('wp_authenticate_user', array($this, 'authenticateUser'), 1, 2);
            
        //login process
        add_filter('login_message', array($this, 'loginMessage'));
            
        //security controls
        add_filter('authenticate', array($this, 'authenticate'), -1);
    }
    
    /**
     * Fires after the user has successfully logged in
     * 
     * @param string  $username Username
     * @param WP_User $user     Current user
     * 
     * @return void
     * 
     * @access public
     */
    public function login($username, $user = null) {
        if (is_a($user, 'WP_User')) {
            if (AAM_Core_Config::get('brute-force-lockout', false)) {
                $this->updateLoginCounter(-1);
            }
            
            // Delete User Switch flag in case admin is impersonating user
            AAM_Core_API::deleteOption('aam-user-switch-' . $user->ID);
            
            // Experimental feature. Track user session
            if (AAM::api()->getConfig('core.session.tracking', false)) {
                $ttl = AAM::api()->getConfig(
                    "core.session.user.{$this->ID}.ttl",
                    AAM::api()->getConfig("core.session.user.ttl", null)
                );
                if (!empty($ttl)) {
                    add_user_meta($user->ID, 'aam-authenticated-timestamp', time());
                }
            }
            
            if ($this->aamLogin === false) {
                $redirect = $this->getLoginRedirect($user);
                
                if ($redirect !== null) {
                    AAM_Core_API::redirect($redirect);
                }
            }
        }
    }
    
    /**
     * Logout redirect
     * 
     * @return void
     * 
     * @access public
     */
    public function logout() {
        $object = AAM::getUser()->getObject('logoutRedirect');
        $type   = $object->get('logout.redirect.type');
        
        if (!empty($type) && $type !== 'default') {
            $redirect = $object->get("logout.redirect.{$type}");
            AAM_Core_API::redirect($redirect);
        }
        
        // get user login timestamp
        delete_user_meta(AAM::getUser()->ID, 'aam-authenticated-timestamp');
    }
    
    /**
     * Control User Block flag
     *
     * @param WP_Error $user
     *
     * @return WP_Error|WP_User
     *
     * @access public
     */
    public function authenticateUser($user) {
        if (is_a($user, 'WP_User')) {
            // First check if user is blocked
            if (intval($user->user_status) === 1) {
                $user = new WP_Error();

                $message  = '[ERROR]: User is locked. Please contact your website ';
                $message .= 'administrator.';

                $user->add(
                    'authentication_failed', 
                    AAM_Backend_View_Helper::preparePhrase($message, 'strong')
                );
            } elseif (AAM_Core_Config::get('core.settings.singleSession', false)) {
                $sessions = WP_Session_Tokens::get_instance($user->ID);
                
                if (count($sessions->get_all()) >= 1) {
                    $sessions->destroy_all();
                }
            }
        }

        return $user;
    }
    
    /**
     * Customize login message
     * 
     * @param string $message
     * 
     * @return string
     * 
     * @access public
     */
    public function loginMessage($message) {
        $reason = AAM_Core_Request::get('reason');
        
        if (empty($message)) {
            if ($reason === 'restricted') {
                $message = AAM_Core_Config::get(
                    'security.redirect.message',
                    '<p class="message">' . 
                        __('Access denied. Please login to get access.', AAM_KEY) . 
                    '</p>'
                );
            }
        }
        
        return $message;
    }
    
    /**
     * Authentication hooks
     * 
     * @param mixed  $response
     */
    public function authenticate($response) {
        // Login Timeout
        if (AAM_Core_Config::get('core.settings.loginTimeout', false)) {
            @sleep(intval(AAM_Core_Config::get('security.login.timeout', 1)));
        }

        // Brute Force Lockout
        if (AAM_Core_Config::get('core.settings.bruteForceLockout', false)) {
            $this->updateLoginCounter(1);
        }
        
        return $response;
    }
    
    /**
     * Get AAM Login Redirect rule
     * 
     * @param WP_User $user
     * 
     * @return null|string
     * 
     * @access protected
     */
    protected function getLoginRedirect($user) {
        $redirect = null;
        $subject  = new AAM_Core_Subject_User($user->ID);
        $object   = $subject->getObject('loginRedirect');
            
        //if Login redirect is defined
        $type = $object->get('login.redirect.type');
            
        if (!empty($type) && $type !== 'default') {
            $redirect = $object->get("login.redirect.{$type}");
        }
        
        return $redirect;
    }
    
    /**
     * Update login counter
     * 
     * @param int $increment
     * 
     * @return void
     * 
     * @access protected
     */
    protected function updateLoginCounter($increment) {
        $attempts = get_transient('aam_login_attempts');

        if ($attempts !== false) {
            $timeout  = get_option('_transient_timeout_aam_login_attempts') - time();
            $attempts = intval($attempts) + $increment;
        } else {
            $attempts = 1;
            $period   = strtotime(
                    AAM_Core_Config::get('security.login.period', '20 minutes')
            );
            $timeout  = $period - time();
        }

        if ($attempts >= AAM_Core_Config::get('security.login.attempts', 20)) {
            if (AAM_Core_Api_Area::isAPI()) {
                throw new Exception(
                    'Exceeded maximum number for authentication attempts. Please try later again.'
                );
            } else {
                wp_safe_redirect(site_url('index.php'));
                exit;
            }
        } else {
            set_transient('aam_login_attempts', $attempts, $timeout);
        }
    }
    
    /**
     * Handle WP core login
     * 
     * @return array
     * 
     * @access public
     */
    public function execute($credentials = array(), $set_cookie = true) {
        $this->aamLogin = true;
        
        if ($set_cookie === false) {
            add_filter('send_auth_cookies', '__return_false');
        }
        
        $response = array(
            'status'   => 'failure',
            'redirect' => AAM_Core_Request::post('redirect')
        );

        try {
            $user = wp_signon($credentials);

            if (is_wp_error($user)) {
                Throw new Exception($user->get_error_message());
            }
            
            if (empty($response['redirect'])) {
                $goto = $this->getLoginRedirect($user);
                $response['redirect'] = ($goto ? $this->normalizeRule($goto) : admin_url());
            }
            
            $response['status'] = 'success';
            $response['user']   = $user;
        } catch (Exception $ex) {
            $response['reason'] = $ex->getMessage();
        }

        return $response;
    }
    
    /**
     * Normalize redirect rule
     * 
     * @param mixed $redirect
     * 
     * @return string
     * 
     * @access protected
     */
    protected function normalizeRule($redirect) {
        $normalized = null;
        
        if (filter_var($redirect, FILTER_VALIDATE_URL)) {
            $normalized = $redirect;
        } elseif (preg_match('/^[\d]+$/', $redirect)) {
            $normalized = get_page_link($redirect);
        } elseif (is_callable($redirect)) {
            $normalized = call_user_func($redirect);
        }
        
        return $normalized;
    }

    /**
     * Get single instance of itself
     * 
     * @return AAM_Core_Login
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
     * Bootstrap AAM Login feature
     * 
     * @return AAM_Core_Login
     * 
     * @access public
     * @static
     */
    public static function bootstrap() {
        return self::getInstance();
    }

}