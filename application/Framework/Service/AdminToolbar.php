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
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Service_AdminToolbar implements AAM_Framework_Service_Interface
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * DB cache option
     *
     * @version 7.0.0
     */
    const CACHE_OPTION = 'aam_admin_toolbar';

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
     * @return array
     * @access public
     *
     * @version 7.0.0
     * @todo - Move to AAM_Service_AdminToolbar
     */
    public function get_items()
    {
        try {
            $result = [];

            // Getting the admin toolbar cache so we can build the list
            $cache = $this->_get_raw_menu();

            if (!empty($cache) && is_array($cache)) {
                foreach($cache as $branch) {
                    array_push($result, $this->_prepare_item_branch($branch));
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
     * @todo - Move to AAM_Service_AdminToolbar
     */
    public function items()
    {
        return $this->get_items();
    }

    /**
     * Get existing menu by ID
     *
     * @param string $slug
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     * @todo - Move to AAM_Service_AdminToolbar
     */
    public function get_item($slug)
    {
        try {
            $result = $this->_find_item_by_slug($slug, $this->get_items());

            if ($result === null) {
                throw new OutOfRangeException('Admin toolbar item does not exist');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alias for the get_item method
     *
     * @param string $item_id
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     * @todo - Move to AAM_Service_AdminToolbar
     */
    public function item($item_id)
    {
        return $this->get_item($item_id);
    }

    /**
     * Restrict/hide menu item
     *
     * @param string $item_id
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function deny($item_id)
    {
        try {
            $result = $this->_update_item_permission(
                $this->_normalize_resource_identifier($item_id),
                true
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Allow menu item
     *
     * @param string $item_id
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function allow($item_id)
    {
        try {
            $result = $this->_update_item_permission(
                $this->_normalize_resource_identifier($item_id),
                false
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Reset admin toolbar permissions
     *
     * @param string $item_id [Optional]
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function reset($item_id = null)
    {
        try {
            if (!empty($resource_identifier)) {
                $result = $this->_remove_item_permission(
                    $this->_normalize_resource_identifier($item_id)
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
     * Check if toolbar item is denied
     *
     * @param string $slug
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function is_denied($slug)
    {
        $result = null;

        try {
            // Getting all the defined permissions
            $resource   = $this->_get_resource();
            $permission = $resource->get_permission(
                $this->_normalize_resource_identifier($slug),
                'list'
            );

            // Step #1. Checking if provided item has any access controls defined
            if (!empty($permission)) {
                $result = $permission['effect'] !== 'allow';
            }

            // Step #2. Checking if item has parent item and if so, determining if
            // parent item is restricted
            if (is_null($result)) {
                // Find the item so we can check if it is a subitem
                $item = $this->_find_item_by_slug($slug, $this->_get_raw_menu());

                if (!empty($item['parent_id'])) {
                    $permission = $resource->get_permission(
                        $this->_normalize_resource_identifier($item['parent_id']),
                        'list'
                    );

                    if (!empty($permission)) {
                        $result = $permission['effect'] !== 'allow';
                    }
                }
            }

            // Step #3. Allow third-party implementations to integrate with the
            // decision making process
            $result = apply_filters(
                'aam_admin_toolbar_is_denied_filter',
                $result,
                $slug,
                $resource
            );

            // Prepare the final answer
            $result = is_bool($result) ? $result : false;
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if menu item is allowed
     *
     * @param string $item_id
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function is_allowed($item_id)
    {
        $result = $this->is_denied($item_id);

        return is_bool($result) ? !$result : $result;
    }

    /**
     * Get Admin Toolbar resource
     *
     * @return AAM_Framework_Resource_AdminToolbar
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource()
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::TOOLBAR
        );
    }

    /**
     * Update existing item permission
     *
     * @param string $item_id
     * @param bool   $is_hidden [Optional]
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _update_item_permission($item_id, $is_hidden = true)
    {
        return $this->_get_resource()->set_permission(
            $this->_normalize_resource_identifier($item_id),
            'list',
            $is_hidden
        );
    }

    /**
     * Delete item permission
     *
     * @param string $item_id Menu item id
     *
     * @return bool
     * @access private
     *
     * @version 7.0.0
     */
    private function _remove_item_permission($item_id)
    {
        return $this->_get_resource()->remove_permission(
            $this->_normalize_resource_identifier($item_id),
            'list'
        );
    }

    /**
     * Find item by slug
     *
     * @param string $slug
     * @param array  $items
     *
     * @return array|null
     * @access private
     *
     * @version 7.0.0
     */
    private function _find_item_by_slug($slug, $items)
    {
        $result = null;

        foreach($items as $item) {
            if ($item['slug'] === $slug) {
                $result = $item;
                break;
            } elseif (isset($item['children'])) {
                foreach($item['children'] as $child) {
                    if ($child['slug'] === $slug) {
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
     * @param array $branch
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_item_branch($branch)
    {
        $response = $this->_prepare_item($branch);

        if (empty($branch['parent_id'])) {
            $response['children'] = [];

            foreach($branch['children'] as $child) {
                array_push($response['children'], $this->_prepare_item($child));
            }
        }

        return $response;
    }

    /**
     * Normalize and prepare the menu item model
     *
     * @param array $item
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_item($item)
    {
        $response = [
            'slug'          => $item['slug'],
            'uri'           => $this->_prepare_item_uri($item['href']),
            'name'          => base64_decode($item['title']),
            'is_restricted' => $this->is_denied($item['slug'])
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
     * @access private
     *
     * @version 7.0.0
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
     * @access private
     *
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

                    $this->cache->set(self::CACHE_OPTION, $response, 31536000);
                }
            }
        }

        if (empty($response)) { // Try to pull it from the cache
            $response = $this->cache->get(self::CACHE_OPTION, []);
        }

        return $response;
    }

    /**
     * Prepare the item branch
     *
     * @param array $items
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _normalize_items($items)
    {
        $response = [];

        // Make a copy of the admin toolbar items before traversing to avoid any
        // modifications
        foreach (json_decode(json_encode($items), true) as $branch) {
            array_push($response, array(
                'slug'     => $branch['id'],
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
     * @access private
     *
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
                    'slug'      => $child['id'],
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
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_item_title($item)
    {
        if (isset($item['title']) && is_string($item['title'])) {
            $filtered = trim(wp_strip_all_tags($item['title'], true));
        } else {
            $filtered = __('Invalid Title', 'advanced-access-manager');
        }

        return base64_encode(preg_replace('/([\d]+)$/', '', $filtered));
    }

}