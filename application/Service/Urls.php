<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * URI access service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Service_Urls
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
                AAM_Backend_Feature_Main_Url::register();
            });
        }

        // Register RESTful API endpoints
        AAM_Restful_Urls::bootstrap();

        $this->initialize_hooks();
    }

    /**
     * Initialize URI hooks
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function initialize_hooks()
    {
        // Authorize request
        add_action('init', function() {
            $this->authorize();
        });
    }

    /**
     * Authorize access to given URL
     *
     * This is the façade method that works with URL Service to determine if access
     * is denied to the given URL and if so - redirect user accordingly.
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function authorize()
    {
        $service = AAM::api()->urls();
        $uri     = AAM::api()->misc->get($_SERVER, 'REQUEST_URI');

        if ($service->is_denied($uri)) {
            $redirect = $service->get_redirect($uri);

            if (empty($redirect) || $redirect['type'] === 'default') {
                AAM::api()->redirect->do_access_denied_redirect();
            } else {
                AAM::api()->redirect->do_redirect($redirect);
            }
        }
    }

}