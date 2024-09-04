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
            $cache = AAM_Service_AdminMenu::getInstance()->getMenuCache();

            if (!empty($cache) && is_array($cache)) {
                foreach ($cache['menu'] as $item) {
                    if (preg_match('/^separator/', $item['id'])) {
                        continue; //skip separator
                    }

                    array_push($result, $this->_prepare_menu(
                        $item, $resource, true
                    ));
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
                if ($menu['id'] === $slug) {
                    $result = $menu;
                } elseif (isset($menu['children'])) {
                    foreach($menu['children'] as $child) {
                        if ($child['id'] === $slug) {
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
     * @param string $slug           Menu item slug
     * @param string $effect         Wether allow or deny
     * @param array  $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function update_item_permission(
        $slug, $effect = 'deny', $inline_context = null
    ) {
        try {
            $result   = $this->get_item($slug);
            $resource = $this->get_resource(false, $inline_context);

            if (!$resource->set_permission($result['slug'], $effect)) {
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
     * Normalize and prepare the menu item model
     *
     * @param array                              $menu_item
     * @param AAM_Framework_Resource_BackendMenu $resource,
     * @param bool                               $is_top_level
     *
     * @return array
     *
     * @since 6.9.36 https://github.com/aamplugin/advanced-access-manager/issues/409
     * @since 6.9.13 Initial implementation of the method
     *
     * @access private
     * @version 6.9.36
     */
    private function _prepare_menu($menu_item, $resource, $is_top_level = false) {
        // Add menu- prefix to define that this is the top level menu.
        // WordPress by default gives the same menu id to the first
        // submenu
        $menu_id  = strtolower(htmlspecialchars_decode($menu_item['id']));
        $slug     = ($is_top_level ? 'menu-' : '') . $menu_id;
        $explicit = $resource->get_explicit_settings();

        $response = array(
            'slug'          => $slug,
            'uri'           => $this->_prepare_admin_uri($menu_id),
            'name'          => $this->_filter_menu_name($menu_item['name']),
            'capability'    => $menu_item['cap'],
            'is_restricted' => $resource->is_restricted($slug),
            'is_inherited'  => !array_key_exists($slug, $explicit)
        );

        if ($is_top_level) {
            $cache = AAM_Service_AdminMenu::getInstance()->getMenuCache();

            $response['children'] = $this->_get_submenu(
                $menu_id,
                $cache['submenu'],
                $resource
            );
        }

        return $response;
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
     *
     * @return array
     *
     * @access private
     * @version 6.9.13
     */
    private function _get_submenu($menu, $submenu, $resource)
    {
        $response = array();

        if (array_key_exists($menu, $submenu) && is_array($submenu[$menu])) {
            foreach ($submenu[$menu] as $item) {
                array_push($response, $this->_prepare_menu(array(
                    'name' => $item[0],
                    'id'   => $this->_normalize_menu_id($item[2]),
                    'cap'  => $item[1]
                ), $resource));
            }
        }

        return $response;
    }

    /**
     * Normalize menu item
     *
     * @param string $menu
     *
     * @return string
     *
     * @access protected
     * @version 6.9.13
     */
    private function _normalize_menu_id($menu)
    {
        if (strpos($menu, 'customize.php') === 0) {
            $menu = 'customize.php';
        }

        return $menu;
    }

}