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
     * @version 7.0.0
     */
    protected function __construct()
    {
        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            add_action('aam_initialize_ui_action', function () {
                AAM_Backend_Feature_Main_LogoutRedirect::register();
            });
        }

        $this->initialize_hooks();
    }

    /**
     * Initialize Logout redirect hooks
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function initialize_hooks()
    {
        // Capture currently logging out user settings
        add_action('clear_auth_cookie', function() {
            $redirect = AAM::api()->logout_redirect()->get_redirect();

            if (empty($redirect) || $redirect['type'] === 'default') {
                $this->_last_user_redirect = [
                    'type'         => 'url_redirect',
                    'redirect_url' => '/'
                ];
            } else {
                $this->_last_user_redirect = $redirect;
            }
        });

        // Fired after the user has been logged out successfully
        add_action('wp_logout', function() {
            AAM::api()->redirect->do_redirect($this->_last_user_redirect);
        }, PHP_INT_MAX);

        // Register RESTful API
        AAM_Restful_LogoutRedirect::bootstrap();
    }

}