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
 * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
 * @since 6.9.15 https://github.com/aamplugin/advanced-access-manager/issues/314
 * @since 6.9.9  https://github.com/aamplugin/advanced-access-manager/issues/266
 * @since 6.4.0  https://github.com/aamplugin/advanced-access-manager/issues/76
 * @since 6.3.0  Fixed bug that causes PHP Notice if URI has not base
 *               (e.g.`?something=1`)
 * @since 6.1.0  The `authorizeUri` returns true if no match found
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.26
 */
class AAM_Service_Url
{
    use AAM_Core_Contract_RequestTrait,
        AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the feature
     *
     * @version 6.0.0
     */
    const FEATURE_FLAG = 'service.uri.enabled';

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
        add_filter('aam_get_config_filter', function($result, $key) {
            if ($key === self::FEATURE_FLAG && is_null($result)) {
                $result = true;
            }

            return $result;
        }, 10, 2);

        $enabled = AAM_Framework_Manager::configs()->get_config(self::FEATURE_FLAG);

        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if ($enabled) {
                add_action('aam_initialize_ui_action', function () {
                    AAM_Backend_Feature_Main_Uri::register();
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
    }

    /**
     * Initialize URI hooks
     *
     * @return void
     *
     * @since 6.9.9 https://github.com/aamplugin/advanced-access-manager/issues/266
     * @since 6.4.0 https://github.com/aamplugin/advanced-access-manager/issues/76
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.9.9
     */
    protected function initialize_hooks()
    {
        // Register RESTful API endpoints
        AAM_Restful_UrlService::bootstrap();

        // Authorize request
        add_action('init', function() {
            $this->authorize();
        });

        // Policy generation hook
        add_filter(
            'aam_generated_policy_filter',
            function ($policy, $type, $settings, $gen) {
                return $this->generate_policy($policy, $type, $settings, $gen);
            },
            10,
            4
        );

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
     * Authorize access to given URL
     *
     * This is the façade method that works with URL Resource to determine if access
     * is denied to the given URL and if so - redirect user accordingly.
     *
     * @param string $url
     * @param array  $inline_context
     *
     * @return boolean
     *
     * @access protected
     * @version 7.0.0
     */
    protected function authorize()
    {
        $service = AAM::api()->user()->urls();

        if ($service->is_restricted($_SERVER['REQUEST_URI'])) {
            $redirect = $service->get_redirect($_SERVER['REQUEST_URI']);

            if ($redirect['type'] === 'default') {
                AAM_Framework_Utility::do_access_denied_redirect();
            } else {
                AAM_Framework_Utility::do_redirect($redirect);
            }
        }
    }

    /**
     * Generate URI policy statements
     *
     * @param array                     $policy
     * @param string                    $resource_type
     * @param array                     $options
     * @param AAM_Core_Policy_Generator $generator
     *
     * @return array
     *
     * @access protected
     * @version 6.4.0
     */
    protected function generate_policy($policy, $resource_type, $options, $generator)
    {
        if ($resource_type === AAM_Framework_Type_Resource::URL) {
            if (!empty($options)) {
                $policy['Statement'] = array_merge(
                    $policy['Statement'],
                    $generator->generateBasicStatements($options, 'URL')
                );
            }
        }

        return $policy;
    }

}