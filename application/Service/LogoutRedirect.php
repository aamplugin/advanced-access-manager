<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Logout Redirect service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Service_LogoutRedirect
{
    use AAM_Service_BaseTrait;

    /**
     * Contains the redirect instructions for just logged out user
     *
     * This property is used to capture logging out user's redirect
     *
     * @var array
     * @access protected
     *
     * @since 7.0.0
     */
    private $_last_user_redirect = null;

    /**
     * Constructor
     *
     * @return void
     * @access protected
     *
     * @version 7.0.4
     */
    protected function __construct()
    {
        // Register RESTful API
        AAM_Restful_LogoutRedirect::bootstrap();

        add_action('init', function() {
            $this->initialize_hooks();
        }, PHP_INT_MAX);
    }

    /**
     * Initialize Logout redirect hooks
     *
     * @return void
     * @access protected
     *
     * @version 7.0.4
     */
    protected function initialize_hooks()
    {
        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            add_action('aam_initialize_ui_action', function () {
                AAM_Backend_Feature_Main_LogoutRedirect::register();
            });
        }

        // Capture currently logging out user settings
        add_action('clear_auth_cookie', function() {
            $redirect = AAM::api()->logout_redirect()->get_redirect();

            if (!empty($redirect) && $redirect['type'] !== 'default') {
                $this->_last_user_redirect = $redirect;
            }
        });

        // Fired after the user has been logged out successfully
        add_action('wp_logout', function() {
            if (!empty($this->_last_user_redirect)) {
                AAM::api()->redirect->do_redirect($this->_last_user_redirect);
            }
        }, PHP_INT_MAX);
    }

    /**
     * Get default logout redirect
     *
     * @return string
     * @access private
     *
     * @version 7.0.1
     */
    private function _get_default_logout_redirect()
    {
        $requested_redirect_to = '';
        $redirect_to           = AAM::api()->misc->get($_REQUEST, 'redirect_to');
        $user                  = wp_get_current_user();

        if (!empty($redirect_to) && is_string($redirect_to)) {
			$result = $requested_redirect_to = $redirect_to;
		} else {
			$result = add_query_arg([
                'loggedout' => 'true',
                'wp_lang'   => get_user_locale(wp_get_current_user())
            ], wp_login_url());
		}

		return apply_filters(
            'logout_redirect', $result, $requested_redirect_to, $user
        );
    }

}