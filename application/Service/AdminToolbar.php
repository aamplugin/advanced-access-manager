<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Toolbar service
 *
 * @since 6.9.27 https://github.com/aamplugin/advanced-access-manager/issues/362
 * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/319
 * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/302
 * @since 6.9.0  https://github.com/aamplugin/advanced-access-manager/issues/223
 * @since 6.4.0  https://github.com/aamplugin/advanced-access-manager/issues/76
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.27
 */
class AAM_Service_AdminToolbar
{
    use AAM_Core_Contract_RequestTrait,
        AAM_Core_Contract_ServiceTrait;

    /**
     * DB option name for cache
     *
     * @version 6.0.0
     */
    const CACHE_DB_OPTION = 'aam_toolbar_cache';

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.0.0
     */
    const FEATURE_FLAG = 'service.admin_toolbar.enabled';

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
            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('Admin Toolbar', AAM_KEY),
                    'description' => __('Manage access to the top admin toolbar items for any role or individual user. The service only removes restricted items but does not actually protect from direct access via link.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 10);
        }

        if ($enabled) {
            $this->initialize_hooks();
        }
    }

    /**
     * Get cached admin toolbar
     *
     * @return array
     *
     * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/319
     * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/297
     * @since 6.0.0  Initial implementation of the method
     *
     * @access public
     * @version 6.9.17
     */
    public function getToolbarCache()
    {
        return AAM_Framework_Utility_Cache::get(self::CACHE_DB_OPTION);
    }

    /**
     * Initialize Admin Toolbar hooks
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function initialize_hooks()
    {
        add_action('wp_before_admin_bar_render', function () {
            $can = current_user_can('aam_manage_admin_toolbar') && AAM::isAAM();

            if (!$can) {
                $this->_filter_admin_toolbar();
            }
        }, PHP_INT_MAX);

        // Policy generation hook
        // add_filter(
        //     'aam_generated_policy_filter', array($this, 'generatePolicy'), 10, 4
        // );

        if (is_admin()) {
            add_action('aam_initialize_ui_action', function () {
                AAM_Backend_Feature_Main_AdminToolbar::register();
            });

            // Cache admin toolbar
            if (current_user_can('aam_manage_admin_toolbar') && AAM::isAAM()) {
                add_action('wp_after_admin_bar_render', function() {
                    // Rebuild the cache
                    AAM::api()->admin_toolbar()->get_item_list();
                });
            }
        }

        // Register the resource
        add_filter(
            'aam_get_resource_filter',
            function($resource, $access_level, $resource_type) {
                if (is_null($resource)
                    && $resource_type === AAM_Framework_Type_Resource::TOOLBAR
                ) {
                    $resource = new AAM_Framework_Resource_AdminToolbar(
                        $access_level
                    );
                }

                return $resource;
            }, 10, 3
        );

        // Register RESTful API endpoints
        AAM_Restful_AdminToolbarService::bootstrap();
    }

    /**
     * Filter admin toolbar
     *
     * @return void
     *
     * @access private
     * @version 7.0.0
     */
    private function _filter_admin_toolbar()
    {
        global $wp_admin_bar;

        $service = AAM::api()->admin_toolbar();
        $nodes   = $wp_admin_bar->get_nodes();

        foreach ((is_array($nodes) ? $nodes : []) as $id => $node) {
            if (!$node->group && $service->is_hidden($id)) {
                if (!empty($node->parent)) { // update parent node with # link
                    $parent = $wp_admin_bar->get_node($node->parent);

                    if ($parent && ($parent->href === $node->href)) {
                        $wp_admin_bar->add_node(array(
                            'id'   => $parent->id,
                            'href' => '#'
                        ));
                    }
                }

                $wp_admin_bar->remove_node($id);
            }
        }
    }

    /**
     * Generate Toolbar policy statements
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
    // public function generatePolicy($policy, $resource_type, $options, $generator)
    // {
    //     if ($resource_type === AAM_Core_Object_Toolbar::OBJECT_TYPE) {
    //         if (!empty($options)) {
    //             $policy['Statement'] = array_merge(
    //                 $policy['Statement'],
    //                 $generator->generateBasicStatements($options, 'Toolbar')
    //             );
    //         }
    //     }

    //     return $policy;
    // }

}