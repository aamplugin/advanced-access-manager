<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

use Vectorface\Whip\Whip;

/**
 * Secure Login service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Service_SecureLogin
{
    use AAM_Service_BaseTrait;

    /**
     * Default configurations
     *
     * @version 7.0.0
     */
    const DEFAULT_CONFIG = [
        'service.secure_login.single_session'      => false,
        'service.secure_login.brute_force_lockout' => false,
        'service.secure_login.time_window'         => '+20 minutes',
        'service.secure_login.login_attempts'      => 5,
        'service.secure_login.login_message'       => 'Login to get access.'
    ];

    /**
     * Constructor
     *
     * @return void
     * @access protected
     *
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

        if (is_admin()) {
            // Register additional tab for the Settings
            add_action('aam_initialize_ui_action', function () {
                AAM_Backend_Feature_Settings_Security::register();
            });
        }

        $this->initialize_hooks();
    }

    /**
     * Initialize core hooks
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function initialize_hooks()
    {
        // Register custom frontend Login widget
        add_action('widgets_init', function () {
            register_widget('AAM_Backend_Widget_Login');
        });

        // Register custom RESTful API endpoint for login
        AAM_Restful_SecureLogin::bootstrap();

        // Redefine the wp-login.php header message
        add_filter('login_message', function($message) {
            return $this->_login_message($message);
        });

        // Security controls
        add_filter('authenticate', function($response) {
            return $this->_authenticate($response);
        }, PHP_INT_MAX);

        add_filter('auth_cookie', function($cookie, $user_id, $_, $__, $token) {
            return $this->_auth_cookie($cookie, $user_id, $token);
        }, 10, 5);

        add_action('wp_login_failed', function() {
            $this->_wp_login_failed();
        });
    }

    /**
     * Intercept auth token generation and enhance security
     *
     * If "One Session Per User" option is enabled, make sure that all other sessions
     * are removed
     *
     * @param string $cookie     Authentication cookie.
     * @param int    $user_id    User ID.
     * @param string $token      User's session token used.
     *
     * @return string
     * @access private
     *
     * @version 7.0.0
     */
    private function _auth_cookie($cookie, $user_id, $token)
    {
        $configs = AAM::api()->config;

        // Remove all other sessions if single session feature is enabled
        if ($configs->get('service.secure_login.single_session')) {
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
     * @access private
     *
     * @version 7.0.0
     */
    private function _wp_login_failed()
    {
        $configs = AAM::api()->config;
        $enabled = $configs->get('service.secure_login.brute_force_lockout');

        // Track failed attempts only if Brute Force Lockout is enabled
        if ($enabled) {
            $name     = $this->_get_login_attempt_key();
            $attempts = AAM::api()->cache->get($name);

            if (!empty($attempts)) {
                $attempts = intval($attempts) + 1;

                AAM::api()->cache->update($name, $attempts);
            } else {
                $timeout = strtotime($configs->get(
                    'service.secure_login.time_window'
                ));

                AAM::api()->cache->set($name, 1, $timeout - time());
            }
        }
    }

    /**
     * Pre-authentication hook
     *
     * Enhance authentication security with Brute Force protection or login delay
     *
     * @param mixed $response
     *
     * @return mixed
     * @access private
     * @see wp_authenticate
     *
     * @version 7.0.0
     */
    private function _authenticate($response)
    {
        $configs = AAM::api()->config;

        // Brute Force Lockout
        if ($configs->get('service.secure_login.brute_force_lockout')) {
            $threshold = $configs->get('service.secure_login.login_attempts');
            $attempts  = AAM::api()->cache->get($this->_get_login_attempt_key());

            if ($attempts >= $threshold) {
                $response = new WP_Error(
                    405,
                    __('Exceeded maximum number for login attempts.', 'advanced-access-manager')
                );
            }
        }

        return $response;
    }

    /**
     * Customize login message
     *
     * @param string $message
     *
     * @return string
     * @access private
     *
     * @version 7.0.0
     */
    private function _login_message($message)
    {
        $reason = AAM::api()->misc->get($_GET, 'reason');

        if (empty($message) && ($reason === 'restricted')) {
            $str = AAM::api()->config->get(
                'service.secure_login.login_message'
            );

            $message = '<p class="message">' . esc_js($str) . '</p>';
        }

        return $message;
    }

    /**
     * Get login attempts counter key name
     *
     * @return string
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_login_attempt_key()
    {
        $whip = new Whip();

        return 'failed_login_attempts_' . $whip->getValidIpAddress();
    }

}