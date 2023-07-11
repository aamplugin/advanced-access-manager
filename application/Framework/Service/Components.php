<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service for Metaboxes & Widgets
 *
 * @package AAM
 * @version 6.9.13
 */
class AAM_Framework_Service_Components
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Return the complete list of all indexed metaboxes & widgets
     *
     * @param array $inline_context Context
     *
     * @return array
     *
     * @access public
     * @version 6.9.13
     */
    public function get_component_list($screen = null, $inline_context = null)
    {
        $response = array();
        $subject  = $this->_get_subject($inline_context);
        $object   = $subject->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE);

        // Getting the menu cache so we can build the list
        $cache = AAM_Service_Metabox::getInstance()->getComponentsCache();

        if (!empty($cache) && is_array($cache)) {
            foreach($cache as $screen_id => $components) {
                foreach($components as $component) {
                    array_push($response, $this->_prepare_component(
                        $component, $screen_id, $object
                    ));
                }
            }
        }

        if (!empty($screen)) {
            $response = array_filter($response, function($c) use ($screen) {
                return $c['screen_id'] === $screen;
            });
        }

        return $response;
    }

    /**
     * Get existing component by ID
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
    public function get_component_by_id($id, $inline_context = null)
    {
        $found = false;

        foreach($this->get_component_list($inline_context) as $component) {
            if ($component['id'] === $id) {
                $found = $component;
                break;
            }
        }

        if ($found === false) {
            throw new UnderflowException('Component does not exist');
        }

        return $found;
    }

    /**
     * Update existing component permission
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
    public function update_component_permission(
        $id, $is_hidden = true, $inline_context = null
    ) {
        $component = $this->get_component_by_id($id);
        $subject   = $this->_get_subject($inline_context);
        $object    = $subject->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE);
        $screen_id = $this->_convert_screen_id($component['screen_id']);
        $internal  = $screen_id . '|' . $component['slug'];

        if ($object->store($internal, $is_hidden) === false) {
            throw new Exception('Failed to persist permissions');
        }

        return $this->get_component_by_id($id);
    }

    /**
     * Delete component permission
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
    public function delete_component_permission($id, $inline_context = null)
    {
        $subject   = $this->_get_subject($inline_context);
        $object    = $subject->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE);
        $component = $this->get_component_by_id($id);

        // Note! User can delete only explicitly set rule (overwritten rule)
        if ($component['is_inherited'] === false) {
            $explicit  = $object->getExplicitOption();
            $screen_id = $this->_convert_screen_id($component['screen_id']);
            $internal  = strtolower($screen_id. '|' . $component['slug']);

            if (isset($explicit[$internal])) {
                unset($explicit[$internal]); // Delete the setting

                $object->setExplicitOption($explicit);
                $success = $object->save();

                $subject->flushCache();
            } else {
                throw new UnderflowException(
                    'Settings for the component do not exist'
                );
            }
        } else {
            $success = true;
        }

        if (!$success) {
            throw new Exception('Failed to persist the settings');
        }

        return $this->get_component_by_id($id);
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
        $object  = $subject->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE);

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
            "aam_component_service_{$name}",
            null,
            $args,
            $this->_get_subject($context),
            $this
        );
    }

    /**
     * Normalize and prepare the component model
     *
     * @param array                   $component
     * @param string                  $screen_id
     * @param AAM_Core_Object_Metabox $object
     *
     * @return array
     *
     * @access private
     * @version 6.9.13
     */
    private function _prepare_component($component, $screen_id, $object)
    {
        $explicit = $object->getExplicitOption();
        $internal = strtolower($screen_id . '|' . $component['slug']);

        $response = array(
            'id'           => abs(crc32($internal)),
            'slug'         => strtolower($component['slug']),
            'screen_id'    => $this->_convert_screen_id($screen_id),
            'name'         => $this->_prepare_component_name($component),
            'is_hidden'    => $object->isHidden($screen_id, $component['slug']),
            'is_inherited' => !array_key_exists($internal, $explicit)
        );

        return $response;
    }

    /**
     * Convert legacy naming
     *
     * @param string $screen_id
     *
     * @return string
     *
     * @access private
     * @version 6.9.13
     */
    private function _convert_screen_id($screen_id)
    {
        if ($screen_id === 'widgets') {
            $response = 'frontend';
        } else if ($screen_id === 'frontend') {
            $response = 'widgets';
        } else {
            $response = $screen_id;
        }

        return strtolower($response);
    }

    /**
     * Normalize the component title
     *
     * @param object $item
     *
     * @return string
     *
     * @access private
     * @version 6.9.13
     */
    private function _prepare_component_name($item)
    {
        $title = wp_strip_all_tags(
            !empty($item['title']) ? $item['title'] : $item['slug']
        );

        return ucwords(trim(preg_replace('/[\d]/', '', $title)));
    }

}