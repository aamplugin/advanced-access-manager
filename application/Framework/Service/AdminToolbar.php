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
 * @since 6.9.27 https://github.com/aamplugin/advanced-access-manager/issues/362
 * @since 6.9.13 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.27
 */
class AAM_Framework_Service_AdminToolbar
{

    use AAM_Framework_Service_BaseTrait;

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
        $response = array();
        $subject  = $this->_get_subject($inline_context);
        $object   = $subject->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE);

        // Getting the menu cache so we can build the list
        $cache = AAM_Service_Toolbar::getInstance()->getToolbarCache();

        //var_dump($cache);

        if (!empty($cache) && is_array($cache)) {
            foreach($cache as $branch) {
                array_push(
                    $response,
                    $this->_prepare_item_branch($branch, $object, true)
                );
            }
        }

        return $response;
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
     * @throws UnderflowException If menu does not exist
     */
    public function get_item_by_id($id, $inline_context = null)
    {
        $found = false;

        foreach($this->get_item_list($inline_context) as $menu) {
            if ($menu['id'] === $id) {
                $found = $menu;
            } elseif (isset($menu['children'])) {
                foreach($menu['children'] as $child) {
                    if ($child['id'] === $id) {
                        $found = $child;
                    }
                }
            }
        }

        if ($found === false) {
            throw new UnderflowException('Admin toolbar menu item does not exist');
        }

        return $found;
    }

    /**
     * Update existing item permission
     *
     * @param int   $id             Sudo-id for the menu item
     * @param bool  $is_hidden      Is hidden or not
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 6.9.13
     * @throws UnderflowException If menu item does not exist
     * @throws Exception If fails to persist changes
     */
    public function update_item_permission(
        $id, $is_hidden = true, $inline_context = null
    ) {
        $menu    = $this->get_item_by_id($id);
        $subject = $this->_get_subject($inline_context);
        $object  = $subject->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE);

        if ($object->store($menu['slug'], $is_hidden) === false) {
            throw new Exception('Failed to persist the admin toolbar permission');
        }

        $subject->flushCache();

        return $this->get_item_by_id($id);
    }

    /**
     * Delete item permission
     *
     * @param int   $id             Sudo-id for the menu item
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 6.9.13
     * @throws UnderflowException If rule does not exist
     * @throws Exception If fails to persist a rule
     */
    public function delete_item_permission($id, $inline_context = null)
    {
        $subject = $this->_get_subject($inline_context);
        $object  = $subject->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE);
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
                throw new UnderflowException(
                    'Setting for the menu item does not exist'
                );
            }
        } else {
            $success = true;
        }

        if (!$success) {
            throw new Exception('Failed to persist the rule');
        }

        $subject->flushCache();

        return $this->get_item_by_id($id);
    }

    /**
     * Reset all permissions
     *
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 6.9.13
     */
    public function reset_permissions($inline_context = null)
    {
        $response = array();

        // Reset the object
        $subject = $this->_get_subject($inline_context);
        $object  = $subject->getObject(AAM_Core_Object_Toolbar::OBJECT_TYPE);

        // Communicate about number of permissions that were deleted
        $response['deleted_permissions_count'] = count($object->getExplicitOption());

        // Reset
        $response['success'] = $object->reset();

        return $response;
    }

    /**
     * Call custom method registered by third-party
     *
     * @param string $name
     * @param array  $args
     *
     * @return mixed
     *
     * @access public
     * @version 6.9.13
     */
    public function __call($name, $args)
    {
        // Assuming that the last argument is always the inline context
        $context = array_pop($args);

        return apply_filters(
            "aam_admin_toolbar_service_{$name}",
            null,
            $args,
            $this->_get_subject($context),
            $this
        );
    }

    /**
     * Prepare the item branch
     *
     * @param object                  $branch
     * @param AAM_Core_Object_Toolbar $object
     * @param boolean                 $is_top_level
     *
     * @return array
     *
     * @access private
     * @version 6.9.13
     */
    private function _prepare_item_branch($branch, $object, $is_top_level = false)
    {
        $response = $this->_prepare_item($branch, $object, $is_top_level);

        if ($is_top_level) {
            $response['children'] = array();

            foreach($branch['children'] as $child) {
                array_push(
                    $response['children'],
                    $this->_prepare_item($child, $object)
                );
            }
        }

        return $response;
    }

    /**
     * Normalize and prepare the menu item model
     *
     * @param object               $menu_item
     * @param AAM_Core_Object_Menu $object,
     * @param bool                 $is_top_level
     *
     * @return array
     *
     * @access private
     * @version 6.9.13
     */
    private function _prepare_item($item, $object, $is_top_level = false) {
        // Add toolbar- prefix to define that this is the top level menu.
        // WordPress by default gives the same menu id to the first
        // submenu
        $slug     = ($is_top_level ? 'toolbar-' : '') . $item['id'];
        $explicit = $object->getExplicitOption();

        $response = array(
            'id'            => abs(crc32($slug)),
            'slug'          => $slug,
            'uri'           => $this->_prepare_item_uri($item['href']),
            'name'          => $this->_prepare_item_name($item),
            'is_hidden'     => $object->isHidden($slug, true),
            'is_inherited'  => !array_key_exists($slug, $explicit)
        );

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
     * Normalize the item title
     *
     * @param object $item
     *
     * @return string
     *
     * @since 6.9.27 https://github.com/aamplugin/advanced-access-manager/issues/362
     * @since 6.9.13 Initial implementation of the method
     *
     * @access protected
     * @version 6.9.27
     */
    protected function _prepare_item_name($item)
    {
        $title = wp_strip_all_tags(
            !empty($item['title']) ? base64_decode($item['title']) : $item['id']
        );

        return ucwords(trim(preg_replace('/[\d]/', '', $title)));
    }

}