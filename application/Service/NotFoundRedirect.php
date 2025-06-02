<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * 404 redirect service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Service_NotFoundRedirect
{
    use AAM_Service_BaseTrait;

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
        // Register the RESTful API
        AAM_Restful_NotFoundRedirect::bootstrap();

        add_action('init', function() {
            $this->initialize_hooks();
        }, PHP_INT_MAX);
    }

    /**
     * Initialize the service hooks
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
                AAM_Backend_Feature_Main_NotFoundRedirect::register();
            });
        }

        add_action('wp', function() {
            global $wp_query;

            if ($wp_query->is_404) { // Handle 404 redirect
                $redirect = AAM::api()->not_found_redirect()->get_redirect();

                if ($redirect['type'] !== 'default') {
                    AAM::api()->redirect->do_redirect($redirect);
                }
            }
        });
    }

}