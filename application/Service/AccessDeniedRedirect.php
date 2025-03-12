<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Access Denied Redirect service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Service_AccessDeniedRedirect
{

    use AAM_Service_BaseTrait;

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
                AAM_Backend_Feature_Main_AccessDeniedRedirect::register();
            });
        }

        // Register RESTful API endpoints
        AAM_Restful_AccessDeniedRedirect::bootstrap();

        $this->initialize_hooks();
    }

    /**
     * Initialize Access Denied Redirect hooks
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function initialize_hooks()
    {
        add_action('aam_access_denied_redirect_handler_filter', function($handler) {
            if (is_null($handler)) {
                $handler = function() {
                    $service  = AAM::api()->access_denied_redirect();
                    $redirect = $service->get_redirect(
                        AAM::api()->misc->get_current_area()
                    );

                    if ($redirect['type'] === 'default') {
                        if (isset($redirect['http_status_code'])) {
                            $status_code = $redirect['http_status_code'];
                        } else {
                            $status_code = 401;
                        }

                        wp_die(
                            __('The access is denied.', 'advanced-access-manager'),
                            __('Access Denied', 'advanced-access-manager'),
                            apply_filters('aam_wp_die_args_filter', [
                                'exit'     => true,
                                'response' => $status_code
                            ])
                        );
                    } else {
                        AAM::api()->redirect->do_redirect($redirect);
                    }
                };
            }

            return $handler;
        });
    }

}