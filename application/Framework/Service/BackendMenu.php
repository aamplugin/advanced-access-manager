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
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function get_items()
    {
        try {
            $result = [];

            // Getting the menu cache so we can build the list
            $menu = $this->_get_raw_menu();

            if (!empty($menu)) {
                foreach ($menu['menu'] as $item) {
                    if (preg_match('/^separator/', $item[2])) {
                        continue; //skip separator
                    }

                    array_push($result, $this->_prepare_menu_item($item));
                }
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alias for the get_items method
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function items()
    {
        return $this->get_items();
    }

    /**
     * Get existing menu by ID
     *
     * @param string $menu_slug
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function get_item($menu_slug)
    {
        try {
            $result    = false;
            $menu_slug = $this->_get_normalized_item_slug($menu_slug);

            foreach($this->get_items() as $item) {
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
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * An alias for the get_item method
     *
     * @param string $menu_slug
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function item($menu_slug)
    {
        return $this->get_item($menu_slug);
    }

    /**
     * Restrict access to a given menu item
     *
     * @param string $menu_slug
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function deny($menu_slug)
    {
        try {
            $result = $this->_set_item_permission($menu_slug, 'deny');
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Allow access to a given menu item
     *
     * @param string $menu_slug
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function allow($menu_slug)
    {
        try {
            $result = $this->_set_item_permission($menu_slug, 'allow');
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Reset all backend menu permissions
     *
     * @param string|null $menu_slug [optional]
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function reset($menu_slug = null)
    {
        try {
            if (is_string($menu_slug)) {
                $result = $this->_delete_item_permission(
                    $this->_get_normalized_item_slug($menu_slug)
                );
            } else {
                $result = $this->_get_resource()->reset();
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if menu item is restricted
     *
     * @param string $menu_slug
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function is_denied($menu_slug)
    {
        try {
            $result = null;

            // Normalize the input data based on top level flat
            $slug = $this->_get_normalized_item_slug($menu_slug);

            // The default dashboard landing page is always excluded
            if ($slug !== 'index.php') {
                $resource    = $this->_get_resource();
                $permissions = $resource->get_permissions();

                // Step #1. Check if menu is explicitly restricted
                if (isset($permissions[$slug])) {
                    $result = $permissions[$slug]['effect'] !== 'allow';
                }

                // Step #2. If menu is not top level item and previous step did not
                //          yield definite results, assume that this is a submenu
                //          item and check if parent menu item is restricted
                if (is_null($result) && strpos($slug, 'menu/') !== 0) {
                    $parent_slug = $this->_get_parent_slug($slug);

                    // If we found a parent menu item, check permissions
                    if (!empty($parent_slug) && isset($permissions[$parent_slug])) {
                        $result = $permissions[$parent_slug]['effect'] !== 'allow';
                    }
                }

                // Step #3. Allow third-party services to hook into the decision
                //          process
                $result = apply_filters(
                    'aam_backend_menu_is_denied_filter',
                    $result,
                    $menu_slug,
                    $resource
                );

                // Prepare the final answer
                $result = is_bool($result) ? $result : false;
            } else {
                $result = false;
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if menu item is allowed
     *
     * @param string $slug
     *
     * @return bool|WP_Error
     *
     * @access public
     * @version 7.0.0
     */
    public function is_allowed($menu_slug)
    {
        $result = $this->is_denied($menu_slug);

        return is_bool($result) ? !$result : $result;
    }

    /**
     * Set permissions for a given menu slug
     *
     * @param string $menu_slug      Menu item slug
     * @param string $effect         Wether allow or deny
     *
     * @return boolean
     *
     * @access private
     * @version 7.0.0
     */
    private function _set_item_permission($menu_slug, $effect)
    {
        $menu_slug = $this->_get_normalized_item_slug($menu_slug);
        $resource  = $this->_get_resource();

        // Prepare array of new permissions
        $perms = array_merge($resource->get_permissions(true), [
            $menu_slug => [ 'effect' => $effect ]
        ]);

        if (!$resource->set_permissions($perms)) {
            throw new RuntimeException('Failed to persist settings');
        }

        return true;
    }

    /**
     * Delete menu permission
     *
     * @param string $menu_slug
     *
     * @return bool
     *
     * @access private
     * @version 7.0.0
     */
    private function _delete_item_permission($menu_slug)
    {
        $menu_slug   = $this->_get_normalized_item_slug($menu_slug);
        $resource    = $this->_get_resource();
        $permissions = $resource->get_permissions(true);

        // Note! User can delete only explicitly set rule (overwritten rule)
        if (array_key_exists($menu_slug, $permissions)) {
            unset($permissions[$menu_slug]);

            if (!$resource->set_permissions($permissions)) {
                throw new RuntimeException('Failed to persist changes');
            }
        }

        return true;
    }

    /**
     * Get backend menu resource
     *
     * @return AAM_Framework_Resource_BackendMenu
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_resource()
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::BACKEND_MENU
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
                $this->cache->set(self::CACHE_DB_OPTION, $result, 31536000);
            }

            if (empty($result)) { // Either AJAX or RESTful API call
                $result = $this->cache->get(self::CACHE_DB_OPTION);
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
                $response[$i] = $this->_get_menu_item_attributes($item);
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
                    $response[$menu_id][$i] = $this->_get_menu_item_attributes($item);
                }
            }
        }

        return $response;
    }

    /**
     * Get menu item attributes
     *
     * Return only attributes we are interested in
     *
     * @param array $item
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_menu_item_attributes($item)
    {
       return [
            // Name
            base64_encode(is_string($item[0]) ? $item[0] : __('No Label', AAM_KEY)),
            // Capability
            $item[1],
            // Slug
            $item[2]
        ];
    }

    /**
     * Normalize and prepare the menu item model
     *
     * @param array $menu_item
     * @param bool  $is_top_level
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_menu_item($menu_item, $is_top_level = true)
    {
        $slug = $is_top_level ? 'menu/' . $menu_item[2] : $menu_item[2];

        $response = array(
            'slug'          => $slug,
            'path'          => $this->_prepare_admin_uri($menu_item[2]),
            'name'          => $this->_filter_menu_name($menu_item[0]),
            'capability'    => $menu_item[1],
            'is_restricted' => $this->is_denied($slug)
        );

        if ($is_top_level) {
            $menu = $this->_get_raw_menu();

            $response['children'] = $this->_get_submenu(
                $menu_item[2],
                isset($menu['submenu']) ? $menu['submenu'] : []
            );
        }

        return $response;
    }

    /**
     * Normalize the menu slug
     *
     * @param string  $slug
     *
     * @return string
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_normalized_item_slug($slug)
    {
        if (strpos($slug, '.php') !== false) {
            $parsed_url  = wp_parse_url($slug);
            $parsed_slug = $parsed_url['path'];

            if (isset($parsed_url['query'])) {
                parse_str($parsed_url['query'], $query_params);

                // Removing some redundant query params
                $redundant_params = apply_filters(
                    'aam_ignored_backend_menu_item_query_params_filter',
                    ['return', 'path']
                );

                foreach($redundant_params as $to_remove) {
                    if (array_key_exists($to_remove, $query_params)) {
                        unset($query_params[$to_remove]);
                    }
                }

                // Sorting query params to ensure exactly the same ordering
                ksort($query_params);

                if (count($query_params)) {
                    $parsed_slug .= '?' . http_build_query($query_params);
                }
            }
        } else {
            $parsed_slug = trim($slug);
        }

        return urldecode($parsed_slug);
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

        foreach ($array as $parent => $subs) {
            foreach ($subs as $sub) {
                $slug = $this->_get_normalized_item_slug($sub[2]);

                if ($slug === $search) {
                    $result = 'menu/' . $parent;
                }
            }

            if ($result !== null) {
                break;
            }
        }

        return $result;
    }

    /**
     * Prepare admin URI for the menu item
     *
     * @param string $menu_slug
     *
     * @return string
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_admin_uri($menu_slug)
    {
        if (strpos($menu_slug, '.php') === false) {
            $uri = admin_url('admin.php?page=' . $menu_slug);
        } else {
            $uri = '/wp-admin/' . trim($menu_slug, '/');
        }

        // Only prepare the relative path
        return $this->misc->sanitize_url($uri);
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
     * @param string $menu
     * @param array  $submenu,
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_submenu($parent_slug, $submenu)
    {
        $response = [];

        if (array_key_exists($parent_slug, $submenu)) {
            foreach ($submenu[$parent_slug] as $item) {
                array_push($response, $this->_prepare_menu_item($item, false));
            }
        }

        return $response;
    }

}