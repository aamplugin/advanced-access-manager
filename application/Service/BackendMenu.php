<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Admin Menu service
 *
 * @since 6.9.28 https://github.com/aamplugin/advanced-access-manager/issues/370
 * @since 6.9.27 https://github.com/aamplugin/advanced-access-manager/issues/362
 * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/319
 * @since 6.9.14 https://github.com/aamplugin/advanced-access-manager/issues/307
 * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/293
 * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/272
 * @since 6.9.5  https://github.com/aamplugin/advanced-access-manager/issues/240
 * @since 6.4.0  https://github.com/aamplugin/advanced-access-manager/issues/71
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.28
 */
class AAM_Service_BackendMenu
{
    use AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.0.0
     */
    const FEATURE_FLAG = 'service.backend_menu.enabled';

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
                    AAM_Backend_Feature_Main_BackendMenu::register();
                });
            }

            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('Backend Menu', AAM_KEY),
                    'description' => __('Manage access to the admin (backend) main menu for any role or individual user. The service removes restricted menu items and protects direct access to them.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 5);
        }

        if ($enabled) {
            $this->initialize_hooks();
        }
    }

    /**
     * Initialize Admin Menu hooks
     *
     * @return void
     *
     * @since 6.9.27 https://github.com/aamplugin/advanced-access-manager/issues/362
     * @since 6.9.17 https://github.com/aamplugin/advanced-access-manager/issues/319
     * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/293
     * @since 6.9.5  https://github.com/aamplugin/advanced-access-manager/issues/240
     * @since 6.4.0  https://github.com/aamplugin/advanced-access-manager/issues/71
     * @since 6.0.0  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.27
     */
    protected function initialize_hooks()
    {
        if (is_admin()) {
            // Filter the admin menu only when we are not on the AAM page and user
            // does not have the ability to manage admin menu through AAM UI
            if (!AAM::isAAM() || !current_user_can('aam_manage_admin_menu')) {
                add_filter('parent_file', function($parent_file) {
                    return $this->_filter_menu($parent_file);
                }, PHP_INT_MAX);
            } elseif (AAM::isAAM() && current_user_can('aam_manage_admin_menu')) {
                add_filter('parent_file', function() {
                    // This will rebuild the backend menu cache
                    AAM::api()->backend_menu()->get_item_list();
                }, PHP_INT_MAX - 1);
            }
        }

        // Policy generation hook
        // add_filter(
        //     'aam_generated_policy_filter', array($this, 'generatePolicy'), 10, 4
        // );

        // Control admin area
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            add_action('admin_init', function() {
                $this->_admin_init();
            });
        }

        // Register the resource
        add_filter(
            'aam_get_resource_filter',
            function($resource, $access_level, $resource_type) {
                if (is_null($resource)
                    && $resource_type === AAM_Framework_Type_Resource::BACKEND_MENU
                ) {
                    $resource = new AAM_Framework_Resource_BackendMenu(
                        $access_level
                    );
                }

                return $resource;
            }, 10, 3
        );

        // Register RESTful API endpoints
        AAM_Restful_BackendMenuService::bootstrap();
    }

    /**
     * Generate Backend Menu policy statements
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
    //     if ($resource_type === AAM_Core_Object_Menu::OBJECT_TYPE) {
    //         if (!empty($options)) {
    //             $policy['Statement'] = array_merge(
    //                 $policy['Statement'],
    //                 $generator->generateBasicStatements($options, 'BackendMenu')
    //             );
    //         }
    //     }

    //     return $policy;
    // }

    /**
     * Filter Admin Menu
     *
     * Keep in mind that this function only filter the menu items but does not
     * restrict access to them.
     *
     * @param array $parent_file
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _filter_menu($parent_file)
    {
        global $menu, $submenu;

        $service = AAM::api()->backend_menu();

        foreach ($menu as $id => $item) {
            $menu_slug     = $item[2];
            $is_restricted = $service->is_restricted($menu_slug);

            if (!empty($submenu[$menu_slug])) {
                // Cover the scenario when there are some dynamic submenus
                $sub_items = $this->_filter_submenu($item, $is_restricted);
            } else {
                $sub_items = [];
            }

            // Cover scenario like with Visual Composer where landing page
            // is defined dynamically
            if ($is_restricted) { // Is top level menu restricted?
                unset($menu[$id]);
            } elseif ($service->is_restricted($menu_slug)) { // Is sub restricted?
                if (count($sub_items)) {
                    $submenu[$item[2]] = $sub_items;
                } else { // If there are no submenu items defined, just delete menu
                    unset($menu[$id]);
                }
            }
        }

        // Remove duplicated separators
        $count = 0;
        foreach ($menu as $id => $item) {
            if (preg_match('/^separator/', $item[2])) {
                if ($count === 0) {
                    $count++;
                } else {
                    unset($menu[$id]);
                }
            } else {
                $count = 0;
            }
        }

        return $parent_file;
    }

    /**
     * Check screen direct access
     *
     * @return void
     *
     * @access public
     *
     * @version 7.0.0
     */
    private function _admin_init()
    {
        global $plugin_page;

        // Compile menu
        $id = $plugin_page;

        if (empty($id)) {
            $id       = basename(AAM_Core_Request::server('SCRIPT_NAME'));
            $taxonomy = AAM_Core_Request::get('taxonomy');
            $postType = AAM_Core_Request::get('post_type');
            $page     = AAM_Core_Request::get('page');
            $params   = array();

            if (!empty($taxonomy)) {
                array_push($params, 'taxonomy=' . $taxonomy);
            }

            if (!empty($postType) && ($postType !== 'post')) {
                array_push($params, 'post_type=' . $postType);
            } elseif (!empty($page)) {
                array_push($params, 'page=' . $page);
            }

            if (count($params)) {
                $id .= '?' . implode('&', $params);
            }
        }

        if (AAM::api()->backend_menu()->is_restricted($id)) {
            AAM_Framework_Utility_Redirect::do_access_denied_redirect();
        }
    }

    /**
     * Filter submenu
     *
     * @param array &$parent
     * @param bool  $deny_all
     *
     * @return void
     *
     * @access private
     * @global array $menu
     * @global array $submenu
     * @version 6.0.0
     */
    private function _filter_submenu(&$parent, $deny_all = false)
    {
        global $submenu;

        $service  = AAM::api()->backend_menu();
        $filtered = [];

        foreach ($submenu[$parent[2]] as $id => $item) {
            if ($deny_all || $service->is_restricted($item[2])) {
                unset($submenu[$parent[2]][$id]);
            } else {
                $filtered[] = $submenu[$parent[2]][$id];
            }
        }

        if (count($filtered)) { // Make sure that the parent points to the first sub
            $values    = array_values($filtered);
            $parent[2] = $values[0][2];
        }

        return $filtered;
    }

}