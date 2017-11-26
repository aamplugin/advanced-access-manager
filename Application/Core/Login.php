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
     *
     * @var type 
     */
    protected $aamLogin = false;
    
    /**
     *
     * @var type 
     */
    protected static $instance = null;

    /**
     * 
     */
    protected function __construct() {
        //login hook
        add_action('wp_login', array($this, 'login'), 10, 2);
        add_action('wp_logout', array($this, 'logout'));
        
        //user login control
        add_filter('wp_authenticate_user', array($this, 'authenticate'), 1, 2);
            
        //login process
        add_filter('login_message', array($this, 'loginMessage'));
            
        //security controls
        add_action('login_form_login', array($this, 'watch'), 1);
    }
    
    /**
     * 
     * @param type $username
     * @param type $user
     */
    public function login($username, $user = null) {
        if (is_a($user, 'WP_User')) {
            $this->updateLoginCounter(-1);
            
            AAM_Core_API::deleteOption('aam-user-switch-' . $user->ID);
            
            if ($this->aamLogin === false) {
                $redirect = $this->getLoginRedirect($user);
                
                if ($redirect !== null) {
                    AAM_Core_API::redirect($redirect);
                }
            }
        }
    }
    
    /**
     * 
     * @param type $user
     * @return type
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
     * 
     */
    public function logout() {
        $object = AAM::getUser()->getObject('logoutRedirect');
        $type   = $object->get('logout.redirect.type');
        
        if (!empty($type) && $type !== 'default') {
            $redirect = $object->get("logout.redirect.{$type}");
            AAM_Core_API::redirect($redirect);
        }
    }
    
    /**
     * 
     * @param type $message
     * @return type
     */
    public function loginMessage($message) {
        $reason = AAM_Core_Request::get('reason');
        
        if (empty($message) && ($reason == 'access-denied')) {
            $message = AAM_Core_Config::get(
                'login.redirect.message', 
                '<p class="message">' . __('Access denied. Please login to get access.', AAM_KEY) . '</p>'
            );
        }
        
        return $message;
    }

    /**
     * 
     */
    public function watch() {
        //Login Timeout
        if (AAM_Core_Config::get('login-timeout', false)) {
            @sleep(intval(AAM_Core_Config::get('security.login.timeout', 1)));
        }

        //Brute Force Lockout
        if (AAM_Core_Config::get('brute-force-lockout', false)) {
            $this->updateLoginCounter(1);
        }
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
    public function authenticate($user) {
        if (is_a($user, 'WP_User') && $user->user_status == 1) {
            $user = new WP_Error();
            
            $message  = 'ERROR]: User is locked. Please contact your website ';
            $message .= 'administrator.';
            
            $user->add(
                'authentication_failed', 
                AAM_Backend_View_Helper::preparePhrase($message, 'strong')
            );
        }

        return $user;
    }
    
    /**
     * 
     * @return type
     * @throws Exception
     */
    public function execute() {
        $this->aamLogin = true;
        
        $response = array(
            'status' => 'failure',
            'redirect' => AAM_Core_Request::post('redirect')
        );

        $log = sanitize_user(AAM_Core_Request::post('log'));

        try {
            $user = wp_signon(array(), $this->checkUserSSL($log));

            if (is_wp_error($user)) {
                Throw new Exception($user->get_error_message());
            }
            $redirect = $this->getLoginRedirect($user);
            
            if (empty($response['redirect'])) {
                $response['redirect'] = ($redirect ? $this->normalizeRedirect($redirect) : admin_url());
            }
            
            $response['status'] = 'success';
        } catch (Exception $ex) {
            $response['reason'] = $ex->getMessage();
        }

        return $response;
    }
    
    /**
     * 
     * @param type $redirect
     * @return type
     */
    protected function normalizeRedirect($redirect) {
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
     * 
     * @param type $increment
     */
    protected function updateLoginCounter($increment) {
        $attempts = get_transient('aam-login-attemtps');

        if ($attempts !== false) {
            $timeout = get_option('_transient_timeout_aam-login-attemtps') - time();
            $attempts = intval($attempts) + $increment;
        } else {
            $attempts = 1;
            $timeout = strtotime(
                    '+' . AAM_Core_Config::get('security.login.period', '20 minutes')
            ) - time();
        }

        if ($attempts >= AAM_Core_Config::get('security.login.attempts', 20)) {
            wp_safe_redirect(site_url('index.php'));
            exit;
        } else {
            set_transient('aam-login-attemtps', $attempts, $timeout);
        }
    }

    /**
     * 
     * @param type $log
     * @param type $pwd
     * @throws Exception
     */
    protected function validate($log, $pwd) {
        if (empty($log) || empty($pwd)) {
            Throw new Exception(__('Username and password are required', AAM_KEY));
        }
    }

    /**
     * 
     * @param type $log
     * @return boolean
     */
    protected function checkUserSSL($log) {
        $secure = false;
        $user = get_user_by((strpos($log, '@') ? 'email' : 'login'), $log);

        if ($user) {
            if (!force_ssl_admin() && get_user_option('use_ssl', $user->ID)) {
                $secure = true;
                force_ssl_admin(true);
            }
        }

        return $secure;
    }

    /**
     * 
     * @return type
     */
    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }
    
    /**
     * 
     * @return type
     */
    public static function bootstrap() {
        return self::getInstance();
    }

}