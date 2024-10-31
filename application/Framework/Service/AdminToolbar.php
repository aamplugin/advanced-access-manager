<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service for Admin Toolbar
 *
 * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/401
 * @since 6.9.27 https://github.com/aamplugin/advanced-access-manager/issues/362
 * @since 6.9.13 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.35
 */
class AAM_Framework_Service_AdminToolbar
implements
    AAM_Framework_Service_Interface
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * DB cache option
     *
     * @version 7.0.0
     */
    const CACHE_DB_OPTION = 'aam_admin_toolbar_cache';

    /**
     * Initialized Admin Toolbar items
     *
     * @var array
     *
     * @access private
     * @version 7.0.0
     */
    private $_toolbar_items = null;

    /**
     * Return the complete admin toolbar item list with permissions
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
            $resource = $this->get_resource($inline_context);

            // Getting the admin toolbar cache so we can build the list
            $cache = $this->_get_raw_menu();

            if (!empty($cache) && is_array($cache)) {
                foreach($cache as $branch) {
                    array_push(
                        $result, $this->_prepare_item_branch($branch, $resource)
                    );
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
     * @param string $item_id        Menu item ID
     * @param array  $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_item_by_id($item_id, $inline_context = null)
    {
        try {
            $result = $this->_find_item_by_id(
                $item_id, $this->get_item_list($inline_context)
            );

            if ($result === null) {
                throw new OutOfRangeException(
                    'Admin toolbar menu item does not exist'
                );
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Update existing item permission
     *
     * @param string $item_id        Menu item ID
     * @param bool   $is_hidden      Is hidden or not
     * @param array  $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function update_item_permission(
        $item_id, $is_hidden = true, $inline_context = null
    ) {
        try {
            $resource   = $this->get_resource($inline_context);
            $permission = [ 'effect' => $is_hidden ? 'deny' : 'allow' ];

            if (!$resource->set_permission($item_id, $permission)) {
                throw new RuntimeException('Failed to persist settings');
            }

            $result = $this->get_item_by_id($item_id);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Delete item permission
     *
     * @param string $item_id        Menu item id
     * @param array  $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 6.9.13
     */
    public function delete_item_permission($item_id, $inline_context = null)
    {
        try {
            $resource = $this->get_resource($inline_context);
            $item     = $this->get_item_by_id($item_id);

            // Note! User can delete only explicitly set rule (overwritten rule)
            if (!empty($item) && $item['is_inherited'] === false) {
                $found    = false;
                $settings = [];

                foreach($resource->get_permissions(true) as $id => $permission) {
                    if ($id === $item['id']) {
                        $found = true;
                    } else {
                        $settings[$id] = $permission;
                    }
                }

                if ($found) {
                    $success = $resource->set_permissions($settings);
                } else {
                    throw new OutOfRangeException(
                        'Setting for the menu item does not exist'
                    );
                }
            } else {
                $success = true;
            }

            if (!$success) {
                throw new RuntimeException('Failed to persist settings');
            }

            $result = $this->get_item_by_id($id);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Reset all permissions
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
            // Resetting settings to default
            $this->get_resource($inline_context)->reset();

            $result = $this->get_item_list($inline_context);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get Admin Toolbar resource
     *
     * @param array $inline_context
     *
     * @return AAM_Framework_Resource_AdminToolbar
     *
     * @access public
     * @version 7.0.0
     */
    public function get_resource($inline_context = null)
    {
        try {
            $result = $this->_get_access_level($inline_context)->get_resource(
                AAM_Framework_Type_Resource::TOOLBAR
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Check if toolbar item is hidden
     *
     * @param string $item_id
     * @param array  $inline_context
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_hidden($item_id, $inline_context = null)
    {
        try {
            $resource = $this->get_resource($inline_context);

            // Step #1. Checking if provided item has any access controls defined
            $result = $resource->is_hidden($item_id);

            // Step #2. Checking if item has parent item and if so, determining if
            // parent item is restricted
            if (is_null($result)) {
                // Find the item so we can check if it is a subitem
                $item = $this->_find_item_by_id($item_id, $this->_get_raw_menu());

                if (!empty($item) && !empty($item['parent_id'])) {
                    $parent_id = $item['parent_id'];
                } else {
                    $parent_id = null;
                }

                $result = $resource->is_hidden($parent_id);
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Find item by ID
     *
     * @param string $item_id
     * @param array  $items
     *
     * @return array|null
     *
     * @access private
     * @version 7.0.0
     */
    private function _find_item_by_id($item_id, $items)
    {
        $result = null;

        foreach($items as $item) {
            if ($item['id'] === $item_id) {
                $result = $item;
                break;
            } elseif (isset($item['children'])) {
                foreach($item['children'] as $child) {
                    if ($child['id'] === $item_id) {
                        $result = $child;
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Prepare the item branch
     *
     * @param object                         $branch
     * @param AAM_Framework_Resource_Toolbar $resource
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_item_branch($branch, $resource)
    {
        $response = $this->_prepare_item($branch, $resource);

        if (empty($branch['parent_id'])) {
            $response['children'] = [];

            foreach($branch['children'] as $child) {
                array_push(
                    $response['children'],
                    $this->_prepare_item($child, $resource)
                );
            }
        }

        return $response;
    }

    /**
     * Normalize and prepare the menu item model
     *
     * @param object                         $menu_item
     * @param AAM_Framework_Resource_Toolbar $resource,
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_item($item, $resource)
    {
        $response = [
            'id'           => $item['id'],
            'uri'          => $this->_prepare_item_uri($item['href']),
            'name'         => base64_decode($item['title']),
            'is_hidden'    => $this->is_hidden($item['id']),
            'is_inherited' => !array_key_exists(
                $item['id'], $resource->get_permissions(true)
            )
        ];

        if (!empty($item['parent_id'])) {
            $response['parent_id'] = $item['parent_id'];
        }

        return $response;
    }

    /**
     * Prepare the URL that item links to
     *
     * @param string $href
     *
     * @return string
     *
     * @access private
     * @version 6.9.13
     */
    private function _prepare_item_uri($href)
    {
        return urldecode(str_replace(site_url(), '', $href));
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
        global $wp_admin_bar;

        $response = [];

        if (!is_null($this->_toolbar_items)) {
            $response = $this->_toolbar_items;
        } elseif (is_object($wp_admin_bar)) {
            $admin_bar = new ReflectionClass(get_class($wp_admin_bar));

            if ($admin_bar->hasProperty('nodes')) {
                // The "bound" property at this point is already set to true, so we
                // cannot get the list of nodes. This is why we use Reflection
                $prop = $admin_bar->getProperty('nodes');
                $prop->setAccessible(true);

                $nodes = $prop->getValue($wp_admin_bar);

                if (array_key_exists('root', $nodes)) {
                    foreach ($nodes['root']->children as $node) {
                        $response = array_merge($response, $node->children);
                    }

                    // Do some cleanup
                    foreach ($response as $i => $node) {
                        if ($node->id === 'menu-toggle') {
                            unset($response[$i]);
                        }
                    }

                    $response = $this->_toolbar_items = $this->_normalize_items(
                        $response
                    );

                    AAM_Framework_Utility_Cache::set(
                        self::CACHE_DB_OPTION, $response, 31536000
                    );
                }
            }
        }

        if (empty($response)) { // Try to pull it from the cache
            $response = AAM_Framework_Utility_Cache::get(self::CACHE_DB_OPTION, []);
        }

        return $response;
    }

    /**
     * Prepare the item branch
     *
     * @param array $items
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _normalize_items($items)
    {
        $response = [];

        // Make a copy of the admin toolbar items before traversing to avoid any
        // modifications
        foreach (json_decode(json_encode($items), true) as $branch) {
            array_push($response, array(
                'id'       => $branch['id'],
                'href'     => $branch['href'],
                'title'    => $this->_prepare_item_title($branch),
                'children' => $this->_get_branch_children($branch, $branch['id'])
            ));
        }

        return $response;
    }

    /**
     * Get list of child items
     *
     * @param array  $branch
     * @param string $parent_id
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    private function _get_branch_children($branch, $parent_id)
    {
        $children = [];

        foreach ($branch['children'] as $child) {
            $type = empty($child['type']) ? '' : $child['type'];

            // Ignore groups and containers. These are special types for the admin
            // toolbar menu and are used only to organize toolbar items, so - ignore
            // them
            if (!in_array($type, [ 'container', 'group' ], true)) {
                $children[] = array(
                    'id'        => $child['id'],
                    'href'      => $child['href'],
                    'title'     => $this->_prepare_item_title($child),
                    // Persist the parent ID so we can easier identify if parent item
                    // is hidden during access control check
                    'parent_id' => $parent_id
                );
            }

            if (!empty($child['children'])) {
                $children = array_merge(
                    $children,
                    $this->_get_branch_children($child, $parent_id)
                );
            }
        }

        return $children;
    }

    /**
     * Filter item title
     *
     * Strip any HTML tags from the item name and also remove the trailing numbers
     *
     * @param array $item
     *
     * @return string
     *
     * @access protected
     * @version 7.0.0
     */
    private function _prepare_item_title($item)
    {
        if (isset($item['title']) && is_string($item['title'])) {
            $filtered = trim(wp_strip_all_tags($item['title'], true));
        } else {
            $filtered = __('Invalid Title', AAM_KEY);
        }

        return base64_encode(preg_replace('/([\d]+)$/', '', $filtered));
    }

}