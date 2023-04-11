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
 * @since 6.9.9 https://github.com/aamplugin/advanced-access-manager/issues/266
 * @since 6.4.0 https://github.com/aamplugin/advanced-access-manager/issues/76
 * @since 6.3.0 Fixed bug that causes PHP Notice if URI has not base
 *              (e.g.`?something=1`)
 * @since 6.1.0 The `authorizeUri` returns true if no match found
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.9
 */
class AAM_Service_Uri
{
    use AAM_Core_Contract_RequestTrait,
        AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the feature
     *
     * @version 6.0.0
     */
    const FEATURE_FLAG = 'core.service.uri.enabled';

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
            // Hook that initialize the AAM UI part of the service
            if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
                add_action('aam_init_ui_action', function () {
                    AAM_Backend_Feature_Main_Uri::register();
                });
            }

            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('URI Access', AAM_KEY),
                    'description' => __('Manage direct access to the website URIs for any role or individual user. Define either explicit URI or wildcard (with Complete Package addon) as well as how to manage user request (allow, deny, redirect, etc.).', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 20);
        }

        if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
            $this->initializeHooks();
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
    protected function initializeHooks()
    {
        // Register RESTful API endpoints
        AAM_Core_Restful_UrlService::bootstrap();

        // Authorize request
        add_action('init', array($this, 'authorizeUri'));

        // Policy generation hook
        add_filter(
            'aam_generated_policy_filter', array($this, 'generatePolicy'), 10, 4
        );
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
     * @access public
     * @version 6.4.0
     */
    public function generatePolicy($policy, $resource_type, $options, $generator)
    {
        if ($resource_type === AAM_Core_Object_Uri::OBJECT_TYPE) {
            if (!empty($options)) {
                $policy['Statement'] = array_merge(
                    $policy['Statement'],
                    $generator->generateBasicStatements($options, 'URI')
                );
            }
        }

        return $policy;
    }

    /**
     * Authorize access to current URI
     *
     * @return boolean
     *
     * @since 6.9.9 https://github.com/aamplugin/advanced-access-manager/issues/266
     * @since 6.3.0 https://github.com/aamplugin/advanced-access-manager/issues/18
     * @since 6.1.0 The method return boolean `true` if no matches found
     * @since 6.0.0 Initial implementation of the method
     *
     * @access public
     * @version 6.9.9
     */
    public function authorizeUri()
    {
        // Preparing the list of requested locations that we'll check against the
        // set list of URL access rules
        $raw    = $this->getFromServer('REQUEST_URI');
        $psd    = wp_parse_url($raw);
        $object = AAM::getUser()->getObject(AAM_Core_Object_Uri::OBJECT_TYPE);
        $params = array();

        if (isset($psd['query'])) {
            parse_str($psd['query'], $params);
        }

        if (isset($psd['path'])) {
            $match = $object->findMatch($psd['path'], $params);

            if (!empty($match) && ($match['type'] !== 'allow')) {
                // Prepare the metadata for the redirect
                $metadata = array();

                if (!empty($match['code'])){
                    $metadata['code'] = $match['code'];
                }

                if (!empty($match['action'])){
                    $metadata[$match['type']] = $match['action'];
                }

                AAM_Core_Redirect::execute($match['type'], $metadata, true);
            }
        }

        return true;
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_Uri::bootstrap();
}