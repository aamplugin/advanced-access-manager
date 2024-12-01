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
 * @package AAM
 * @version 7.0.0
 */
class AAM_Service_AdminToolbar
{

    use AAM_Core_Contract_RequestTrait,
        AAM_Core_Contract_ServiceTrait;

    /**
     * DB option name for cache
     *
     * @version 7.0.0
     */
    const CACHE_DB_OPTION = 'aam_toolbar_cache';

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 7.0.0
     */
    const FEATURE_FLAG = 'service.admin_toolbar.enabled';

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

        $enabled = AAM::api()->configs()->get_config(self::FEATURE_FLAG);

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
     * @access public
     * @version 7.0.0
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

        if (is_admin()) {
            add_action('aam_initialize_ui_action', function () {
                AAM_Backend_Feature_Main_AdminToolbar::register();
            });

            // Cache admin toolbar
            add_action('wp_after_admin_bar_render', function() {
                // Rebuild the cache
                if (current_user_can('aam_manage_admin_toolbar') && AAM::isAAM()) {
                    AAM::api()->admin_toolbar()->get_items();
                }
            });
        }

        // Register the resource
        add_filter(
            'aam_get_resource_filter',
            function($resource, $access_level, $resource_type, $resource_id) {
                if (is_null($resource)
                    && $resource_type === AAM_Framework_Type_Resource::TOOLBAR
                ) {
                    $resource = new AAM_Framework_Resource_AdminToolbar(
                        $access_level, $resource_id
                    );
                }

                return $resource;
            }, 10, 4
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
            if (!$node->group && $service->is_restricted($id)) {
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

}