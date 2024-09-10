<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service for Backend Menu
 *
 * @since 6.9.36 https://github.com/aamplugin/advanced-access-manager/issues/409
 * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/401
 * @since 6.9.27 https://github.com/aamplugin/advanced-access-manager/issues/362
 * @since 6.9.18 https://github.com/aamplugin/advanced-access-manager/issues/326
 * @since 6.9.13 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.36
 */
class AAM_Framework_Service_BackendMenu
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * DB cache option
     *
     * @version 7.0.0
     */
    const CACHE_DB_OPTION = 'aam_menu_cache';

    /**
     * Return the complete backend menu list with permissions
     *
     * @param array $inline_context Context
     *
     * @return array
     *
     * @access public
     * @version 6.9.13
     */
    public function get_item_list($inline_context = null)
    {
        try {
            $result   = [];
            $resource = $this->get_resource(true, $inline_context);

            // Getting the menu cache so we can build the list
            $menu = $this->_get_raw_menu();

            if (!empty($menu)) {
                foreach ($menu['menu'] as $item) {
                    if (preg_match('/^separator/', $item['slug'])) {
                        continue; //skip separator
                    }

                    array_push($result, $this->_prepare_menu($item, $resource));
                }
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get existing menu by ID
     *
     * @param string $slug           Menu item slug
     * @param array  $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 6.9.13
     * @throws OutOfRangeException If menu does not exist
     */
    public function get_item($slug, $inline_context = null)
    {
        try {
            $result = false;

            foreach($this->get_item_list($inline_context) as $menu) {
                if ($menu['slug'] === $slug) {
                    $result = $menu;
                } elseif (isset($menu['children'])) {
                    foreach($menu['children'] as $child) {
                        if ($child['slug'] === $slug) {
                            $result = $child;
                        }
                    }
                }
            }

            if ($result === false) {
                throw new OutOfRangeException('Backend menu does not exist');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Update existing backend menu permission
     *
     * @param string  $slug           Menu item slug
     * @param string  $effect         Wether allow or deny
     * @param boolean $is_top_level   Wether defining access to the whole branch or not
     * @param array   $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function update_item_permission(
        $slug, $effect = 'deny', $is_top_level = false, $inline_context = null
    ) {
        try {
            $result     = $this->get_item($slug);
            $resource   = $this->get_resource(false, $inline_context);
            $permission = [ 'effect' => $effect ];

            if ($is_top_level) {
                $permission['is_top_level'] = true;
            }

            if (!$resource->set_permission($result['slug'], $permission)) {
                throw new RuntimeException('Failed to persist settings');
            }

            $result = $this->get_item($slug);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Delete menu permission
     *
     * @param string $slug           Menu item slug
     * @param array  $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function delete_item_permission($slug, $inline_context = null)
    {
        try {
            $resource    = $this->get_resource(false, $inline_context);
            $permissions = $resource->get_permissions(true);

            // Note! User can delete only explicitly set rule (overwritten rule)
            if (array_key_exists($slug, $permissions)) {
                unset($permissions[$slug]);

                if (!$resource->set_permissions($permissions)) {
                    throw new RuntimeException('Failed to persist changes');
                }
            } else {
                throw new OutOfRangeException('Menu item does not exist');
            }

            $result = $this->get_item($slug);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Reset all backend menu permissions
     *
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/401
     * @since 6.9.13 Initial implementation of the method
     *
     * @access public
     * @version 6.9.35
     */
    public function reset($inline_context = null)
    {
        try {
            // Reset settings to default
            $this->get_resource(false, $inline_context)->reset();

            $result = $this->get_item_list($inline_context);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get backend menu resource
     *
     * @param array $inline_context
     *
     * @return AAM_Framework_Resource_BackendMenu
     *
     * @access public
     * @version 7.0.0
     */
    public function get_resource($reload = false, $inline_context = null)
    {
        try {
            $access_level = $this->_get_access_level($inline_context);
            $result       = $access_level->get_resource(
                AAM_Framework_Type_Resource::BACKEND_MENU, null, $reload
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Check if menu item is restricted
     *
     * @param string $slug
     * @param array  $inline_context
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_restricted($slug, $inline_context = null)
    {
        $result = null;

        try {
            $result = $this->get_resource(false, $inline_context)->is_restricted(
                $slug, $this->_get_parent_item($slug)
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get raw Admin Menu
     *
     * This method also caches the admin menu for future usage
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_raw_menu()
    {
        static $_cache = [];
        global $menu, $submenu;

        if (empty($_cache)) {
            $result        = [];
            $persist_cache = false;

            if (!empty($menu)) {
                $result['menu'] = $this->_filter_menu_items($menu);
                $persist_cache  = true;
            }

            if (!empty($submenu)) {
                $result['submenu'] = $this->_filter_submenu_items($submenu);
                $persist_cache     = true;
            }

            if ($persist_cache) {
                AAM_Framework_Utility_Cache::set(
                    self::CACHE_DB_OPTION, $result, 31536000
                ); // Cache for a year
            }

            if (empty($result)) { // Either AJAX or RESTful API call
                $result = AAM_Framework_Utility_Cache::get(self::CACHE_DB_OPTION);
            }

            $_cache = $result; // Avoid doing the same thing over & over again
        } else {
            $result = $_cache;
        }

        return is_array($result) ? $result : [];
    }

    /**
     * Filter menu items
     *
     * @param array $items
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _filter_menu_items($items)
    {
        $response = [];

        if (is_array($items)) {
            foreach($items as $i => $item) {
                $response[$i] = [
                    'slug' => $item[2],
                    'cap'  => $item[1],
                    'name' => base64_encode(
                        is_string($item[0]) ? $item[0] : __('No Label', AAM_KEY)
                    )
                ];
            }
        }

        return $response;
    }

    /**
     * Filter submenu item list
     *
     * @param array $items
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _filter_submenu_items($items)
    {
        $response = [];

        if (is_array($items)) {
            foreach($items as $menu_id => $sub_level) {
                $response[$menu_id] = [];

                foreach($sub_level as $i => $item) {
                    $response[$menu_id][$i] = [
                        'slug' => $item[2],
                        'cap'  => $item[1],
                        'name' => base64_encode(
                            is_string($item[0]) ? $item[0] : __('No Label', AAM_KEY)
                        )
                    ];
                }
            }
        }

        return $response;
    }

    /**
     * Normalize and prepare the menu item model
     *
     * @param array                              $menu_item
     * @param AAM_Framework_Resource_BackendMenu $resource,
     * @param boolean|null                       $default_restriction
     *
     * @return array
     *
     * @since 6.9.36 https://github.com/aamplugin/advanced-access-manager/issues/409
     * @since 6.9.13 Initial implementation of the method
     *
     * @access private
     * @version 6.9.36
     */
    private function _prepare_menu(
        $menu_item, $resource, $default_restriction = null, $parent_menu_item = null
    ) {
        $slug = $menu_item['slug'];

        // Determining if menu item is restricted. The default restriction only
        // applies for submenu
        $is_restricted = $this->is_restricted($slug, $parent_menu_item);

        if (is_null($is_restricted)) {
            $is_restricted = $default_restriction;
        }

        $response = array(
            'slug'          => $slug,
            'uri'           => $this->_prepare_admin_uri($slug),
            'name'          => $this->_filter_menu_name($menu_item['name']),
            'capability'    => $menu_item['cap'],
            'is_restricted' => $is_restricted
        );

        if (is_null($parent_menu_item)) {
            $menu = $this->_get_raw_menu();

            $response['children'] = $this->_get_submenu(
                $slug,
                isset($menu['submenu']) ? $menu['submenu'] : [],
                $resource,
                $is_restricted
            );
        }

        return $response;
    }

    /**
     * Get parent menu
     *
     * @param string $slug
     *
     * @return string|null
     *
     * @access public
     * @global array $submenu
     * @version 7.0.0
     */
    private function _get_parent_item($search)
    {
        global $submenu;

        $result = $this->_find_parent($submenu, $search);

        // If we cannot find parent menu in current $submenu array, try to find it
        // in the cached menu generated by super admin. This is important to cover
        // scenarios where submenus bubble up to menu. E.g. Profile
        if (is_null($result)) {
            $menu   = $this->_get_raw_menu();
            $result = $this->_find_parent(
                isset($menu['submenu']) ? $menu['submenu'] : [], $search
            );
        }

        return $result;
    }

    /**
     * Find parent menu from the array of menu items
     *
     * @param array  $array
     * @param string $search
     *
     * @return null|string
     *
     * @access private
     * @version 7.0.0
     */
    private function _find_parent($array, $search)
    {
        $result = null;

        if (is_array($array)) {
            // Covering scenario when the submenu is also a link to the parent branch
            if (array_key_exists($search, $array)) {
                $result = $search;
            } else {
                foreach ($array as $parent => $subs) {
                    foreach ($subs as $sub) {
                        if (isset($sub[2]) && $sub[2] === $search) {
                            $result = $parent;
                        } else if (isset($sub['slug']) && $sub['slug'] === $search) {
                            $result = $parent;
                        }
                    }

                    if ($result !== null) {
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Prepare admin URI for the menu item
     *
     * @param string $resource
     *
     * @return string
     *
     * @access private
     * @version 6.9.13
     */
    private function _prepare_admin_uri($resource)
    {
        if (!function_exists('get_plugin_page_hook')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $hook = get_plugin_page_hook($resource, 'admin.php');
        $uri  = (!empty($hook) ? 'admin.php?page=' . $resource : $resource);

        return '/wp-admin/' . $uri;
    }

    /**
     * Filter menu name
     *
     * Strip any HTML tags from the menu name and also remove the trailing
     * numbers in case of Plugin or Comments menu name.
     *
     * @param string $name
     *
     * @return string
     *
     * @since 6.9.27 https://github.com/aamplugin/advanced-access-manager/issues/362
     * @since 6.9.18 https://github.com/aamplugin/advanced-access-manager/issues/326
     * @since 6.9.13 Initial implementation of the method
     *
     * @access protected
     * @version 6.9.27
     */
    private function _filter_menu_name($name)
    {
        if (is_string($name)) {
            $filtered = trim(wp_strip_all_tags(base64_decode($name), true));
        } else {
            $filtered = '';
        }

        return preg_replace('/([\d]+)$/', '', $filtered);
    }

    /**
     * Prepare filtered submenu
     *
     * @param string                             $menu
     * @param array                              $submenu,
     * @param AAM_Framework_Resource_BackendMenu $resource
     * @param boolean|null                       $default_restriction
     *
     * @return array
     *
     * @access private
     * @version 6.9.13
     */
    private function _get_submenu($menu, $submenu, $resource, $default_restriction)
    {
        $response = [];

        if (array_key_exists($menu, $submenu) && is_array($submenu[$menu])) {
            foreach ($submenu[$menu] as $item) {
                array_push($response, $this->_prepare_menu(
                    $item, $resource, $default_restriction, $menu
                ));
            }
        }

        return $response;
    }

}