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
            $result  = array();
            $subject = $this->_get_subject($inline_context);
            $object  = $subject->reloadObject(
                AAM_Core_Object_Menu::OBJECT_TYPE
            );

            // Getting the menu cache so we can build the list
            $cache = AAM_Service_AdminMenu::getInstance()->getMenuCache();

            if (!empty($cache) && is_array($cache)) {
                foreach ($cache['menu'] as $item) {
                    if (preg_match('/^separator/', $item['id'])) {
                        continue; //skip separator
                    }

                    array_push($result, $this->_prepare_menu(
                        $item, $object, true
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
     * @param int   $id             Sudo-id for the menu item
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 6.9.13
     * @throws OutOfRangeException If menu does not exist
     */
    public function get_item_by_id($id, $inline_context = null)
    {
        try {
            $result = false;

            foreach($this->get_item_list($inline_context) as $menu) {
                if ($menu['id'] === $id) {
                    $result = $menu;
                } elseif (isset($menu['children'])) {
                    foreach($menu['children'] as $child) {
                        if ($child['id'] === $id) {
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
     * @param int   $id             Sudo-id for the menu item
     * @param bool  $is_restricted  Is restricted or not
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 6.9.13
     * @throws RuntimeException If fails to persist changes
     */
    public function update_menu_permission(
        $id, $is_restricted = true, $inline_context = null
    ) {
        try {
            $result  = $this->get_item_by_id($id);
            $subject = $this->_get_subject($inline_context);
            $object  = $subject->getObject(AAM_Core_Object_Menu::OBJECT_TYPE);

            if ($object->store($result['slug'], $is_restricted) === false) {
                throw new RuntimeException('Failed to persist settings');
            }

            $result = $this->get_item_by_id($id);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Delete menu permission
     *
     * @param int   $id             Sudo-id for the menu item
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 6.9.13
     * @throws OutOfRangeException If rule does not exist
     * @throws RuntimeException If fails to persist a rule
     */
    public function delete_item_permission($id, $inline_context = null)
    {
        try {
            $subject = $this->_get_subject($inline_context);
            $object  = $subject->getObject(AAM_Core_Object_Menu::OBJECT_TYPE);
            $menu    = $this->get_item_by_id($id);

            // Note! User can delete only explicitly set rule (overwritten rule)
            if ($menu['is_inherited'] === false) {
                $found       = false;
                $new_options = array();

                foreach($object->getExplicitOption() as $slug => $is_restricted) {
                    if ($slug === $menu['slug']) {
                        $found = true;
                    } else {
                        $new_options[$slug] = $is_restricted;
                    }
                }

                if ($found) {
                    $object->setExplicitOption($new_options);
                    $success = $object->save();
                } else {
                    throw new OutOfRangeException('Menu item does not exist');
                }
            } else {
                $success = true;
            }

            if (!$success) {
                throw new RuntimeException('Failed to persist the rule');
            }

            $result = $this->get_item_by_id($id);
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
            // Reset the object
            $subject = $this->_get_subject($inline_context);
            $object  = $subject->getObject(AAM_Core_Object_Menu::OBJECT_TYPE);

            // Reset settings to default
            $object->reset();

            $result = $this->get_item_list($inline_context);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Normalize and prepare the menu item model
     *
     * @param array                $menu_item
     * @param AAM_Core_Object_Menu $object,
     * @param bool                 $is_top_level
     *
     * @return array
     *
     * @since 6.9.36 https://github.com/aamplugin/advanced-access-manager/issues/409
     * @since 6.9.13 Initial implementation of the method
     *
     * @access private
     * @version 6.9.36
     */
    private function _prepare_menu($menu_item, $object, $is_top_level = false) {
        // Add menu- prefix to define that this is the top level menu.
        // WordPress by default gives the same menu id to the first
        // submenu
        $menu_id  = strtolower(htmlspecialchars_decode($menu_item['id']));
        $slug     = ($is_top_level ? 'menu-' : '') . $menu_id;
        $explicit = $object->getExplicitOption();

        $response = array(
            'id'            => abs(crc32($slug)),
            'slug'          => $slug,
            'uri'           => $this->_prepare_admin_uri($menu_id),
            'name'          => $this->_filter_menu_name($menu_item['name']),
            'capability'    => $menu_item['cap'],
            'is_restricted' => $object->isRestricted($slug),
            'is_inherited'  => !array_key_exists($slug, $explicit)
        );

        if ($is_top_level) {
            $cache = AAM_Service_AdminMenu::getInstance()->getMenuCache();

            $response['children'] = $this->_get_submenu(
                $menu_id,
                $cache['submenu'],
                $object
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
     * @param string               $menu
     * @param array                $submenu,
     * @param AAM_Core_Object_Menu $object
     *
     * @return array
     *
     * @access private
     * @version 6.9.13
     */
    private function _get_submenu($menu, $submenu, $object)
    {
        $response = array();

        if (array_key_exists($menu, $submenu) && is_array($submenu[$menu])) {
            foreach ($submenu[$menu] as $item) {
                array_push($response, $this->_prepare_menu(array(
                    'name' => $item[0],
                    'id'   => $this->_normalize_menu_id($item[2]),
                    'cap'  => $item[1]
                ), $object));
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