<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Secure Login service
 *
 * @since 6.3.1 Fixed bug with not being able to lock user
 * @since 6.1.0 Enriched error response with more details
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.3.1
 */
class AAM_Service_SecureLogin
{
    use AAM_Core_Contract_RequestTrait,
        AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.0.0
     */
    const FEATURE_FLAG = 'core.service.secure-login.enabled';

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
            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('Secure Login', AAM_KEY),
                    'description' => __('Enhance default WordPress authentication process with more secure login mechanism. The service registers frontend AJAX Login widget as well as additional endpoints for the RESTful API authentication.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 1);

            // Register additional tab for the Settings
            if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
                add_action('aam_init_ui_action', function () {
                    AAM_Backend_Feature_Settings_Security::register();
                }, 1);
            }
        }

        if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
            $this->initializeHooks();
        }
    }

    /**
     * Initialize core hooks
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function initializeHooks()
    {
        // Register custom frontend Login widget
        add_action('widgets_init', function () {
            register_widget('AAM_Backend_Widget_Login');
        });

        // Register custom RESTful API endpoint for login
        add_action('rest_api_init', array($this, 'registerRESTfulRoute'));

        // User login control
        add_filter('wp_authenticate_user', array($this, 'validateUserStatus'), 1, 2);
        add_filter('aam_verify_user_filter', array($this, 'validateUserStatus'));

        // Redefine the wp-login.php header message
        add_filter('login_message', array($this, 'loginMessage'));

        // Security controls
        add_filter('authenticate', array($this, 'enhanceAuthentication'), PHP_INT_MAX);
        add_filter('auth_cookie', array($this, 'manageAuthCookie'), 10, 5);
        add_action('wp_login_failed', array($this, 'trackFailedLoginAttempt'));

        // AAM UI controls
        add_filter('aam_user_row_actions_filter', function($actions, $user) {
            // Move this to the Secure Login Service
            if (current_user_can('aam_toggle_users')) {
                $actions[] = ($user->user_status ? 'unlock' : 'lock');
            }

            return $actions;
        }, 10, 2);
        add_filter('aam_ajax_filter', array($this, 'handleAjax'), 10, 3);
        add_filter('aam_user_expiration_actions_filter', function($actions) {
            $actions['lock'] = __('Block User Account', AAM_KEY);

            return $actions;
        });
        add_action('aam_process_inactive_user_action', array($this, 'lockUser'), 10, 2);

        // AAM Core integration
        add_action('aam_initialize_user_action', function(AAM_Core_Subject_User $user) {
            $currentId = get_current_user_id();

            if (intval($user->user_status) === 1 && ($currentId === $user->ID)) {
                wp_logout();
            }
        });
    }

    /**
     * Register RESTful Route
     *
     * Register AAM authentication endpoint
     *
     * @return void
     * @version 6.0.0
     */
    public function registerRESTfulRoute()
    {
        $config = array(
            'methods'  => 'POST',
            'callback' => array($this, 'authenticate'),
            'args' => apply_filters('aam_restful_authentication_args_filter', array(
                'username' => array(
                    'description' => 'Valid username.',
                    'type'        => 'string',
                ),
                'password' => array(
                    'description' => 'Valid password.',
                    'type'        => 'string',
                ),
                'redirect' => array(
                    'description' => 'Redirect URL after authentication.',
                    'type'        => 'string',
                ),
                'remember' => array(
                    'description' => 'Prolong the user session.',
                    'type'        => 'boolean',
                ),
                'returnAuthCookies' => array(
                    'description' => 'Return auth cookies.',
                    'type'        => 'boolean',
                )
            )),
        );

        register_rest_route('aam/v2', '/authenticate', $config);
    }

    /**
     * Authenticate user
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
    public function authenticate(WP_REST_Request $request)
    {
        $status  = 200;

        // No need to generate Auth cookies, unless explicitly stated so
        if ($request->get_param('returnAuthCookies') !== true) {
            add_filter('send_auth_cookies', '__return_false');
        }

        $user = wp_signon(array(
            'user_login'    => $request->get_param('username'),
            'user_password' => $request->get_param('password'),
            'remember'      => $request->get_param('remember')
        ));

        if (!is_wp_error($user)) {
            $result = apply_filters('aam_auth_response_filter', array(
                'user'     => $user,
                'redirect' => $request->get_param('redirect')
            ), $request);
        } else {
            $status = 403;
            $result = array(
                'code'   => $user->get_error_code(),
                'reason' => $user->get_error_message()
            );
        }

        return new WP_REST_Response($result, $status);
    }

    /**
     * Intercept auth token generation and enhance security
     *
     * If "One Session Per User" option is enabled, make sure that all other sessions
     * are removed
     *
     * @param string $cookie     Authentication cookie.
     * @param int    $user_id    User ID.
     * @param int    $expiration The time the cookie expires as a UNIX timestamp.
     * @param string $scheme     Cookie scheme used. Accepts 'auth', 'secure_auth', or 'logged_in'.
     * @param string $token      User's session token used.
     *
     * @return string
     *
     * @access public
     * @version 6.0.0
     */
    public function manageAuthCookie($cookie, $user_id, $expiration, $scheme, $token)
    {
        // Remove all other sessions if single session feature is enabled
        if (AAM_Core_Config::get('service.secureLogin.feature.singleSession', false)) {
            $sessions = WP_Session_Tokens::get_instance($user_id);

            if (count($sessions->get_all()) > 1) {
                $sessions->destroy_others($token);
            }
        }

        return $cookie;
    }

    /**
     * Track failed login attempts
     *
     * This method is used to enable brute force protection
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function trackFailedLoginAttempt()
    {
        // Track failed attempts only if Brute Force Lockout is enabled
        if (AAM_Core_Config::get('service.secureLogin.feature.bruteForceLockout', false)) {
            $this->updateLoginAttemptsTransient(1);
        }
    }

    /**
     * Increment/Decrement failed login attempts transient
     *
     * @param int $counter
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function updateLoginAttemptsTransient($counter)
    {
        $name     = $this->getLoginAttemptTransientName();
        $attempts = get_transient($name);

        if ($attempts !== false) {
            $timeout  = get_option("_transient_timeout_{$name}");
            $attempts = intval($attempts) + $counter;
        } else {
            $attempts = 1;
            $timeout  = strtotime(
                AAM_Core_Config::get(
                    'service.secureLogin.settings.attemptWindow',
                    '20 minutes'
                )
            );
        }

        set_transient($name, $attempts, $timeout - time());
    }

    /**
     * Get login attempts transient name
     *
     * @return string
     *
     * @access private
     * @version 6.0.0
     */
    private function getLoginAttemptTransientName()
    {
        return sprintf(
            'aam_failed_login_attempts_%s', $this->getFromServer('REMOTE_ADDR')
        );
    }

    /**
     * Pre-authentication hook
     *
     * Enhance authentication security with Brute Force protection or login delay
     *
     * @param mixed $response
     *
     * @return mixed
     *
     * @access public
     * @see wp_authenticate
     * @version 6.0.0
     */
    public function enhanceAuthentication($response)
    {
        // Brute Force Lockout
        if (AAM_Core_Config::get('service.secureLogin.feature.bruteForceLockout', false)) {
            $attempts  = get_transient($this->getLoginAttemptTransientName());
            $threshold = AAM_Core_Config::get('service.secureLogin.settings.loginAttempts', 20);

            if ($attempts >= $threshold) {
                $response = new WP_Error(
                    405,
                    __('Exceeded maximum number for authentication attempts. Try again later.', AAM_KEY)
                );
            }
        }

        return $response;
    }

    /**
     * Validate user status
     *
     * Check if user is locked or not
     *
     * @param WP_Error $user
     *
     * @return WP_Error|WP_User
     *
     * @access public
     * @version 6.0.0
     */
    public function validateUserStatus($user)
    {
        // Check if user is blocked
        if (is_a($user, 'WP_User') && (intval($user->user_status) === 1)) {
            $user = new WP_Error(
                405,
                AAM_Backend_View_Helper::preparePhrase(
                    '[ERROR]: User is locked. Contact website administrator.',
                    'strong'
                )
            );
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
     * @version 6.0.0
     */
    public function loginMessage($message)
    {
        if (empty($message) && ($this->getFromQuery('reason') === 'restricted')) {
            $message = sprintf(
                __('%sAccess is restricted. Login to get access.%s', AAM_KEY),
                '<p class="message">',
                '</p>'
            );
        }

        return $message;
    }

    /**
     * Handle AAM UI ajax calls
     *
     * @param mixed                 $response
     * @param AAM_Core_Subject_User $user
     * @param string                $action
     *
     * @return mixed
     *
     * @since 6.3.1 Fixed bug https://github.com/aamplugin/advanced-access-manager/issues/43
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.3.1
     */
    public function handleAjax($response, $user, $action)
    {
        if ($action === 'Service_SecureLogin.toggleUserStatus') {
            $result   = $this->toggleUserStatus($user);
            $response = wp_json_encode(
                array('status' => ($result ? 'success' : 'failure'))
            );
        }

        return $response;
    }

    /**
     * Lock user
     *
     * This method is invoked when user is expired
     *
     * @param array                 $trigger
     * @param AAM_Core_Subject_User $user
     *
     * @return void
     *
     * @access public
     * @version 6.0.0
     */
    public function lockUser(array $trigger, AAM_Core_Subject_User $user)
    {
        if ($trigger['action'] === 'lock') {
            $this->changeUserStatus($user->getPrincipal(), 1);
            wp_logout();
        }
    }

    /**
     * Toggle user status
     *
     * Either block or unblock user record
     *
     * @param AAM_Core_Subject_User $user
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected function toggleUserStatus(AAM_Core_Subject_User $user)
    {
        $result = false;

        if (current_user_can('aam_toggle_users') && current_user_can('edit_users')) {
            if (apply_filters('aam_user_can_manage_level_filter', true, $user->getMaxLevel())) {
                // User is not allowed to lock himself
                if (intval($user->getId()) !== get_current_user_id()) {
                    $result = $this->changeUserStatus(
                        $user->getPrincipal(), ($user->user_status ? 0 : 1)
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Change user status
     *
     * @param WP_User $user
     * @param int     $status
     *
     * @return boolean
     *
     * @access protected
     * @version 6.0.0
     */
    protected function changeUserStatus(WP_User $user, $status)
    {
        global $wpdb;

        $result = $wpdb->update(
            $wpdb->users,
            array('user_status' => $status),
            array('ID' => $user->ID)
        );

        if ($result) {
            $user->user_status = $status;
            clean_user_cache($user);
        }

        return $result;
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_SecureLogin::bootstrap();
}