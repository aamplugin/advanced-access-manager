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
        // Register RESTful API endpoints
        AAM_Restful_BackendMenu::bootstrap();

        add_action('init', function() {
            $this->initialize_hooks();
        }, PHP_INT_MAX);
    }

    /**
     * Initialize Admin Menu hooks
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
                AAM_Backend_Feature_Main_BackendMenu::register();
            });

            // Filter the admin menu only when we are not on the AAM page and user
            // does not have the ability to manage admin menu through AAM UI
            add_filter('parent_file', function($parent_file) {
                if (is_admin()
                    && filter_input(INPUT_GET, 'page') === 'aam'
                    && current_user_can('aam_manage_backend_menu')
                ) {
                    AAM::api()->backend_menu()->get_items();
                } else {
                    $this->filter_menu();
                }

                return $parent_file;
            }, PHP_INT_MAX);
        }

        // Control admin area
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            add_action('admin_init', function() {
                $this->_admin_init();
            }, PHP_INT_MAX);
        }
    }

    /**
     * Filter Admin Menu
     *
     * Keep in mind that this function only filter the menu items but does not
     * restrict access to them.
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function filter_menu()
    {
        global $menu, $submenu;

        $service = AAM::api()->backend_menu();

        foreach ($menu as $id => $item) {
            $menu_slug     = $item[2];
            $is_restricted = $service->is_denied('menu/' . $menu_slug);

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
                    $submenu[$menu_slug] = $submenus;
                }
            }

            // Remove the top level menu item if it is restricted and no other
            // sub menu items are available
            if ($is_restricted
                && empty($submenu[$menu_slug])
                && $menu_slug !== 'index.php'
            ) {
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
            $id        = basename(AAM::api()->misc->get($_SERVER, 'SCRIPT_NAME'));
            $taxonomy  = filter_input(INPUT_GET, 'taxonomy');
            $post_type = filter_input(INPUT_GET, 'post_type');
            $page      = filter_input(INPUT_GET, 'page');
            $params    = [];

            if (!empty($taxonomy)) {
                array_push($params, 'taxonomy=' . $taxonomy);
            }

            if (!empty($post_type) && ($post_type !== 'post')) {
                array_push($params, 'post_type=' . $post_type);
            } elseif (!empty($page)) {
                array_push($params, 'page=' . $page);
            }

            if (count($params)) {
                $id .= '?' . implode('&', $params);
            }
        }

        if (AAM::api()->backend_menu()->is_denied($id)) {
            AAM::api()->redirect->do_access_denied_redirect();
        }
    }

    /**
     * Filter submenu
     *
     * @param array                             $submenus
     * @param AAM_Framework_Service_BackendMenu $service
     * @param bool                              $default_effect
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _filter_submenu($submenus, $service)
    {
        foreach ($submenus as $id => $item) {
            if ($service->is_denied($item[2])) {
                unset($submenus[$id]);
            }
        }

        return array_values($submenus);
    }

}