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
 * @package AAM
 * @version 7.0.0
 */
class AAM_Service_BackendMenu
{
    use AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 7.0.0
     */
    const FEATURE_FLAG = 'service.backend_menu.enabled';

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
    }

    /**
     * Initialize Admin Menu hooks
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function initialize_hooks()
    {
        if (is_admin()) {
            // Filter the admin menu only when we are not on the AAM page and user
            // does not have the ability to manage admin menu through AAM UI
            if (!AAM::isAAM() || !current_user_can('aam_manage_admin_menu')) {
                add_filter('parent_file', function($parent_file) {
                    $this->filter_menu();

                    return $parent_file;
                }, PHP_INT_MAX);
            } elseif (AAM::isAAM() && current_user_can('aam_manage_admin_menu')) {
                add_filter('parent_file', function() {
                    // This will rebuild the backend menu cache
                    AAM::api()->backend_menu()->get_items();
                }, PHP_INT_MAX - 1);
            }
        }

        // Control admin area
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            add_action('admin_init', function() {
                $this->_admin_init();
            });
        }

        // Register RESTful API endpoints
        AAM_Restful_BackendMenuService::bootstrap();
    }

    /**
     * Filter Admin Menu
     *
     * Keep in mind that this function only filter the menu items but does not
     * restrict access to them.
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public function filter_menu()
    {
        global $menu, $submenu;

        $service = AAM::api()->backend_menu();

        foreach ($menu as $id => $item) {
            $menu_slug     = $item[2];
            $is_restricted = $service->is_restricted($menu_slug, true);

            // If top level menu has submenu items - filter them out as well
            if (!empty($submenu[$menu_slug])) {
                $submenus = $this->_filter_submenu(
                    $submenu[$menu_slug], $service
                );

                // If all submenu items are restricted, there is no need to
                // render the top level menu because the top level menu always
                // points to the first submenu item
                if (count($submenus) === 0) {
                    unset($submenu[$menu_slug]);
                    unset($menu[$id]);
                } else {
                    // When we are swapping the submenu item pointer, making sure
                    // that we correctly update submenu array
                    if ($menu_slug !== $submenus[0][2]) {
                        // Ensuring the parent menu item always points to
                        // the first submenu item and title is updated accordingly
                        $menu[$id][0] = $submenus[0][0]; // Swap title
                        $menu[$id][1] = $submenus[0][1]; // Swap capability
                        $menu[$id][2] = $submenus[0][2]; // Swap slug

                        $submenu[$menu[$id][2]] = $submenus;

                        // Remove the lingering reference
                        unset($submenu[$menu_slug]);

                        // Update the pointer
                        $menu_slug = $menu[$id][2];
                    } else {
                        $submenu[$menu_slug] = $submenus;
                    }
                }
            }

            // Remove the top level menu item if it is restricted and no other
            // sub menu items are available
            if ($is_restricted && empty($submenu[$menu_slug])) {
                unset($menu[$id]);
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
     * @param array                             $submenus
     * @param AAM_Framework_Service_BackendMenu $service
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _filter_submenu($submenus, $service)
    {
        foreach ($submenus as $id => $item) {
            if ($service->is_restricted($item[2])) {
                unset($submenus[$id]);
            }
        }

        return array_values($submenus);
    }

}