<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Framework service to manage access to the backend (admin) menu
 *
 * @method string get_access_mode() **[Premium Feature!]** Available only with
 *         premium add-on
 * @method boolean set_access_mode(string $mode) **[Premium Feature!]** Set current
 *         access mode
 * @method string|boolean access_mode(string $mode = null) **[Premium Feature!]** Set
 *         or get current access mode. If the _$mode_ argument is not provided, the
 *         method returns current access mode. Otherwise it sets specified.
 *
 * @package AAM
 * @version 7.0.0
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
     * @param mixed $inline_context [optional]
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_items($inline_context = null)
    {
        try {
            $result   = [];
            $resource = $this->_get_resource(true, $inline_context);

            // Getting the menu cache so we can build the list
            $menu = $this->_get_raw_menu();

            if (!empty($menu)) {
                foreach ($menu['menu'] as $item) {
                    if (preg_match('/^separator/', $item['slug'])) {
                        continue; //skip separator
                    }

                    array_push($result, $this->_prepare_menu_item(
                        $item, $resource, true, $inline_context
                    ));
                }
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Alias for the get_items method
     *
     * @param mixed $inline_context [optional]
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function items($inline_context = null)
    {
        return $this->get_items($inline_context);
    }

    /**
     * Get existing menu by ID
     *
     * @param string  $menu_slug
     * @param boolean $is_top_level
     * @param mixed   $inline_context [optional]
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_item(
        $menu_slug, $is_top_level = false, $inline_context = null
    ) {
        try {
            $result    = false;
            $menu_slug = $this->_get_normalized_item_slug($menu_slug, $is_top_level);

            foreach($this->get_items($inline_context) as $item) {
                if ($item['slug'] === $menu_slug) {
                    $result = $item;
                } elseif (isset($item['children'])) {
                    foreach($item['children'] as $child) {
                        if ($child['slug'] === $menu_slug) {
                            $result = $child;
                        }
                    }
                }

                // If we found menu, just break the search
                if ($result !== false) { break; }
            }

            if ($result === false) {
                throw new OutOfRangeException('Backend menu item does not exist');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * An alias for the get_item method
     *
     * @param string  $menu_slug
     * @param boolean $is_top_level
     * @param mixed   $inline_context [optional]
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function item($menu_slug, $is_top_level = false, $inline_context = null)
    {
        return $this->get_item($menu_slug, $is_top_level, $inline_context);
    }

    /**
     * Restrict access to a given menu item
     *
     * @param string $menu_slug
     * @param string $is_top_level
     * @param mixed  $inline_context [optional]
     *
     * @return boolean|WP_Error|null
     *
     * @access public
     * @version 7.0.0
     */
    public function restrict(
        $menu_slug,
        $is_top_level = false,
        $inline_context = null
    ) {
        try {
            $result = $this->_set_item_permission(
                $menu_slug, 'deny', $is_top_level, $inline_context
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Allow access to a given menu item
     *
     * @param string  $menu_slug
     * @param boolean $is_top_level
     * @param mixed   $inline_context [optional]
     *
     * @return boolean|WP_Error|null
     *
     * @access public
     * @version 7.0.0
     */
    public function allow(
        $menu_slug, $is_top_level = false, $inline_context = null
    ) {
        try {
            $result = $this->_set_item_permission(
                $menu_slug, 'allow', $is_top_level, $inline_context
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Reset all backend menu permissions
     *
     * @param string  $menu_slug      [optional]
     * @param boolean $is_top_level   [optional]
     * @param mixed   $inline_context [optional]
     *
     * @return boolean|WP_Error|null
     *
     * @access public
     * @version 7.0.0
     */
    public function reset(
        $menu_slug = null,
        $is_top_level = false,
        $inline_context = null
    ) {
        try {
            if (is_string($menu_slug)) {
                $result = $this->_delete_item_permission(
                    $menu_slug, $is_top_level, $inline_context
                );
            } else {
                // Determine which argument may contain a context
                $context = is_null($inline_context) ? $menu_slug : $inline_context;

                // Reset all permissions to default
                $result = $this->_get_resource(false, $context)->reset();
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Check if menu item is restricted
     *
     * @param string  $menu_slug
     * @param boolean $is_top_level
     * @param mixed   $inline_context [optional]
     *
     * @return boolean|WP_Error|null
     *
     * @access public
     * @version 7.0.0
     */
    public function is_restricted(
        $menu_slug,
        $is_top_level = false,
        $inline_context = null
    ) {
        $result = null;

        try {
            $resource = $this->_get_resource(false, $inline_context);

            // Normalize the input data based on top level flat
            $slug = $this->_get_normalized_item_slug($menu_slug, $is_top_level);

            // Step #1. Check if menu is explicitly restricted
            $result = $resource->is_restricted($slug);

            // Step #2. If menu is not top level item and previous step did not yield
            //          definite results, assume that this was a submenu item and
            //          check if parent menu item is restricted
            if (!$is_top_level && is_null($result)) {
                $parent_menu_slug = $this->_get_parent_slug($menu_slug);

                // If we found a parent menu item, check its access
                if (!empty($parent_menu_slug)) {
                    $result = $resource->is_restricted($parent_menu_slug, true);
                }
            }

            // Step #3. Allow third-party services to hook into the decision process
            $result = apply_filters(
                'aam_backend_menu_is_restricted_filter',
                $result,
                $this,
                $menu_slug,
                $is_top_level
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Check if menu item is allowed
     *
     * @param string  $slug
     * @param boolean $is_top_level
     * @param mixed   $inline_context
     *
     * @return boolean|WP_Error|null
     *
     * @access public
     * @version 7.0.0
     */
    public function is_allowed(
        $menu_slug,
        $is_top_level = false,
        $inline_context = null
    ) {
        $result = $this->is_restricted($menu_slug, $is_top_level, $inline_context);

        return is_bool($result) ? !$result : $result;
    }

    /**
     * Set permissions for a given menu slug
     *
     * @param string  $menu_slug      Menu item slug
     * @param string  $effect         Wether allow or deny
     * @param boolean $is_top_level
     * @param mixed   $inline_context Inline context
     *
     * @return boolean
     *
     * @access private
     * @version 7.0.0
     */
    private function _set_item_permission(
        $menu_slug,
        $effect,
        $is_top_level,
        $inline_context
    ) {
        $resource   = $this->_get_resource(false, $inline_context);
        $permission = [ 'effect' => $effect ];
        $menu_slug  = $this->_get_normalized_item_slug($menu_slug, $is_top_level);

        if (!$resource->set_permission($menu_slug, $permission)) {
            throw new RuntimeException('Failed to persist settings');
        }

        return true;
    }

    /**
     * Delete menu permission
     *
     * @param string  $slug           Menu item slug
     * @param boolean $is_top_level
     * @param mixed   $inline_context Inline context
     *
     * @return boolean
     *
     * @access private
     * @version 7.0.0
     */
    private function _delete_item_permission($slug, $is_top_level, $inline_context)
    {
        $resource    = $this->_get_resource(false, $inline_context);
        $permissions = $resource->get_permissions(true);
        $slug        = $this->_get_normalized_item_slug($slug, $is_top_level);

        // Note! User can delete only explicitly set rule (overwritten rule)
        if (array_key_exists($slug, $permissions)) {
            unset($permissions[$slug]);

            if (!$resource->set_permissions($permissions)) {
                throw new RuntimeException('Failed to persist changes');
            }
        }

        return true;
    }

    /**
     * Get backend menu resource
     *
     * @param boolean $reload
     * @param mixed   $inline_context
     *
     * @return AAM_Framework_Resource_BackendMenu
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_resource($reload, $inline_context)
    {
        return $this->_get_access_level($inline_context)->get_resource(
            AAM_Framework_Type_Resource::BACKEND_MENU, null, $reload
        );
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
     * @param boolean                            $is_top_level
     * @param mixed                              $inline_context
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_menu_item(
        $menu_item, $resource, $is_top_level, $inline_context
    ) {
        $slug = $this->_get_normalized_item_slug($menu_item['slug'], $is_top_level);

        // Determining if menu item is restricted. The default restriction only
        // applies for submenu
        $is_restricted = $this->is_restricted($slug, $inline_context);

        $response = array(
            'slug'          => $slug,
            'path'          => $this->_prepare_admin_uri($slug),
            'name'          => $this->_filter_menu_name($menu_item['name']),
            'capability'    => $menu_item['cap'],
            'is_restricted' => $is_restricted
        );

        if ($is_top_level) {
            $menu = $this->_get_raw_menu();

            $response['children'] = $this->_get_submenu(
                $slug,
                isset($menu['submenu']) ? $menu['submenu'] : [],
                $resource,
                $inline_context
            );
        }

        return $response;
    }

    /**
     * Normalize the menu slug
     *
     * If the menu slug is top level menu item, prepend the "menu/"
     *
     * @param string  $slug
     * @param boolean &$is_top_level
     *
     * @return string
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_normalized_item_slug($slug, &$is_top_level)
    {
        if (strpos($slug, '.php') !== false) {
            $parsed_url  = wp_parse_url($slug);
            $parsed_slug = $parsed_url['path'];

            // Removing some redundant query params
            if (isset($parsed_url['query'])) {
                parse_str($parsed_url['query'], $query_params);

                foreach(['return', 'path'] as $to_remove) {
                    if (array_key_exists($to_remove, $query_params)) {
                        unset($query_params[$to_remove]);
                    }
                }

                if (count($query_params)) {
                    $parsed_slug .= '?' . http_build_query($query_params);
                }
            }
        } else {
            $parsed_slug = trim($slug);
        }

        $slug = urldecode($parsed_slug);

        // Normalize the input data based on top level flat
        if (strpos($slug , 'menu/') === false) {
            if ($is_top_level) {
                $slug = 'menu/' . $slug;
            }
        } else {
            $is_top_level = true;
        }

        return $slug;
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
    private function _get_parent_slug($search)
    {
        global $submenu;

        $result = $this->_find_parent(
            is_array($submenu) ? $submenu : [],
            $search
        );

        // If we cannot find parent menu in current $submenu array, try to find it
        // in the cached menu generated by super admin. This is important to cover
        // scenarios where submenus bubble up to menu. E.g. Profile
        if (is_null($result)) {
            $menu   = $this->_get_raw_menu();
            $result = $this->_find_parent(
                isset($menu['submenu']) ? $menu['submenu'] : [],
                $search
            );
        }

        return is_null($result) ? null : 'menu/' . $result;
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

        return $result;
    }

    /**
     * Prepare admin URI for the menu item
     *
     * @param string $slug
     *
     * @return string
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_admin_uri($slug)
    {
        if (strpos($slug, '.php') === false) {
            $uri = admin_url('admin.php?page=' . $slug);
        } else {
            $uri = '/wp-admin/' . trim($slug, '/');
        }

        // Only prepare the relative path
        $relative_path = AAM_Framework_Utility_Misc::sanitize_url($uri);

        return $relative_path;
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
     * @access protected
     * @version 7.0.0
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
     * @param mixed                              $inline_context
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_submenu(
        $parent_slug, $submenu, $resource, $inline_context = null
    ) {
        $response = [];

        if (array_key_exists($parent_slug, $submenu)) {
            foreach ($submenu[$parent_slug] as $item) {
                array_push($response, $this->_prepare_menu_item(
                    $item, $resource, false, $inline_context
                ));
            }
        }

        return $response;
    }

}