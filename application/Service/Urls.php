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

    use AAM_Core_Contract_RequestTrait,
        AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the feature
     *
     * @version 7.0.0
     */
    const FEATURE_FLAG = 'service.url.enabled';

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function __construct()
    {
        add_filter('aam_get_config_filter', function($result, $key) {
            if ($key === self::FEATURE_FLAG && is_null($result)) {
                $result = true;
            }

            return $result;
        }, 10, 2);

        $enabled = AAM::api()->config->get(self::FEATURE_FLAG);

        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if ($enabled) {
                add_action('aam_initialize_ui_action', function () {
                    AAM_Backend_Feature_Main_Url::register();
                });
            }

            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('URL Access', AAM_KEY),
                    'description' => __('Manage direct access to website URLs for any role or individual user. Define specific URLs or use wildcards (with the premium add-on). Control user requests by setting rules to allow, deny, or redirect access.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 20);
        }

        if ($enabled) {
            $this->initialize_hooks();
        }

        // Register the resource
        add_filter(
            'aam_get_resource_filter',
            function($resource, $access_level, $resource_type, $resource_id) {
                if (is_null($resource)
                    && $resource_type === AAM_Framework_Type_Resource::URL
                ) {
                    $resource = new AAM_Framework_Resource_Url(
                        $access_level, $resource_id
                    );
                }

                return $resource;
            }, 10, 4
        );
    }

    /**
     * Initialize URI hooks
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function initialize_hooks()
    {
        // Register RESTful API endpoints
        AAM_Restful_UrlService::bootstrap();

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
     *
     * @access public
     * @version 7.0.0
     */
    public function authorize()
    {
        $resource = AAM::api()->urls()->url($_SERVER['REQUEST_URI']);

        if ($resource->is_restricted()) {
            $redirect = $resource->get_redirect();

            if (empty($redirect) || $redirect['type'] === 'default') {
                AAM::api()->redirect->do_access_denied_redirect();
            } else {
                AAM::api()->redirect->do_redirect($redirect);
            }
        }
    }

}