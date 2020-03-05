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
 * @since 6.4.0 Enhanced https://github.com/aamplugin/advanced-access-manager/issues/71
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.4.0
 */
class AAM_Service_AdminMenu
{
    use AAM_Core_Contract_ServiceTrait;

    /**
     * Service alias
     *
     * Is used to get service instance if it is enabled
     *
     * @version 6.4.0
     */
    const SERVICE_ALIAS = 'admin-menu';

    /**
     * DB cache option
     *
     * @version 6.0.0
     */
    const CACHE_DB_OPTION = 'aam_menu_cache';

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.0.0
     */
    const FEATURE_FLAG = 'core.service.admin-menu.enabled';

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
                    AAM_Backend_Feature_Main_Menu::register();
                });
            }

            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('Admin Menu', AAM_KEY),
                    'description' => __('Manage access to the admin (backend) main menu for any role or individual user. The service removes restricted menu items and protects direct access to them.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 5);
        }

        if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
            $this->initializeHooks();
        }
    }

    /**
     * Initialize Admin Menu hooks
     *
     * @return void
     *
     * @since 6.4.0 Enhanced https://github.com/aamplugin/advanced-access-manager/issues/71
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.4.0
     */
    public function initializeHooks()
    {
        if (is_admin()) {
            // Filter the admin menu only when we are not on the AAM page and user
            // does not have the ability to manage admin menu through AAM UI
            if (!AAM::isAAM() || !current_user_can('aam_manage_admin_menu')) {
                add_filter('parent_file', array($this, 'filterMenu'), PHP_INT_MAX);
            } elseif (AAM::isAAM()) {
                // If we are on the AAM page, then cache the menu and submenu that will
                // be displayed for managing on the Admin Menu tab
                add_filter('parent_file', function() {
                    global $menu, $submenu;

                    AAM_Core_API::updateOption(self::CACHE_DB_OPTION, array(
                        'menu'    => $menu,
                        'submenu' => $submenu
                    ));
                }, PHP_INT_MAX - 1);
            }
        }

        // Policy generation hook
        add_filter(
            'aam_generated_policy_filter', array($this, 'generatePolicy'), 10, 4
        );

        add_action('aam_clear_settings_action', function() {
            AAM_Core_API::deleteOption(self::CACHE_DB_OPTION);
        });

        // Control admin area
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            add_action('admin_init', array($this, 'checkScreenAccess'));
        }

        // Service fetch
        $this->registerService();
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
    public function generatePolicy($policy, $resource_type, $options, $generator)
    {
        if ($resource_type === AAM_Core_Object_Menu::OBJECT_TYPE) {
            if (!empty($options)) {
                $policy['Statement'] = array_merge(
                    $policy['Statement'],
                    $generator->generateBasicStatements($options, 'BackendMenu')
                );
            }
        }

        return $policy;
    }

    /**
     * Get cached menu array
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function getMenuCache()
    {
        return AAM_Core_API::getOption(self::CACHE_DB_OPTION, array());
    }

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
     * @access public
     * @global array $menu
     * @global array $submenu
     * @version 6.0.0
     */
    public function filterMenu($parent_file)
    {
        global $menu, $submenu;

        $object = AAM::getUser()->getObject(AAM_Core_Object_Menu::OBJECT_TYPE);

        foreach ($menu as $id => $item) {
            if (!empty($submenu[$item[2]])) {
                // Cover the scenario when there are some dynamic submenus
                $subs = $this->filterSubmenu(
                    $item, ($object->isRestricted('menu-' . $item[2]))
                );
            } else {
                $subs = array();
            }

            // Cover scenario like with Visual Composer where landing page
            // is defined dynamically
            if ($object->isRestricted('menu-' . $item[2])) {
                unset($menu[$id]);
            } elseif ($object->isRestricted($item[2])) {
                if (count($subs)) {
                    $menu[$id][2] = $subs[0][2];
                    $submenu[$menu[$id][2]] = $subs;
                } else {
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
     * Filter submenu
     *
     * @param array &$parent
     * @param bool  $deny_all
     *
     * @return void
     *
     * @access protected
     * @global array $menu
     * @global array $submenu
     * @version 6.0.0
     */
    protected function filterSubmenu(&$parent, $deny_all = false)
    {
        global $submenu;

        $object   = AAM::getUser()->getObject(AAM_Core_Object_Menu::OBJECT_TYPE);
        $filtered = array();

        foreach ($submenu[$parent[2]] as $id => $item) {
            if ($deny_all || $object->isRestricted($this->normalizeItem($item[2]))) {
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

    /**
     * Normalize menu item
     *
     * @param string $menu
     *
     * @return string
     *
     * @access protected
     * @version 6.0.0
     */
    protected function normalizeItem($menu)
    {
        if (strpos($menu, 'customize.php') === 0) {
            $menu = 'customize.php';
        }

        return $menu;
    }

    /**
     * Check screen direct access
     *
     * @return void
     *
     * @access public
     * @global string $plugin_page
     * @version 6.0.0
     */
    public function checkScreenAccess()
    {
        global $plugin_page;

        // Compile menu
        $menu = $plugin_page;

        if (empty($menu)) {
            $menu     = basename(AAM_Core_Request::server('SCRIPT_NAME'));
            $taxonomy = AAM_Core_Request::get('taxonomy');
            $postType = AAM_Core_Request::get('post_type');
            $page     = AAM_Core_Request::get('page');

            if (!empty($taxonomy)) {
                $menu .= '?taxonomy=' . $taxonomy;
            } elseif (!empty($postType) && ($postType !== 'post')) {
                $menu .= '?post_type=' . $postType;
            } elseif (!empty($page)) {
                $menu .= '?page=' . $page;
            }
        }

        $object = AAM::getUser()->getObject(AAM_Core_Object_Menu::OBJECT_TYPE);

        if ($object->isRestricted($menu)) {
            wp_die(
                __('Sorry, you are not allowed to view this page.', AAM_KEY),
                'aam_access_denied'
            );
        }
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_AdminMenu::bootstrap();
}