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
 * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/401
 * @since 6.9.13 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.35
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
    public function get_item_list($screen_id = null, $inline_context = null)
    {
        try {
            $result  = array();
            $subject = $this->_get_subject($inline_context);
            $object  = $subject->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE);

            // Getting the menu cache so we can build the list
            $cache = AAM_Service_Metabox::getInstance()->getComponentsCache();

            if (!empty($cache) && is_array($cache)) {
                foreach($cache as $id => $components) {
                    foreach($components as $component) {
                        array_push($result, $this->_prepare_component(
                            $component, $id, $object
                        ));
                    }
                }
            }

            if (!empty($screen_id)) {
                $result = array_values(
                    array_filter($result, function($c) use ($screen_id) {
                        return $c['screen_id'] === $screen_id;
                    })
                );
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
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
     * @throws OutOfRangeException If menu does not exist
     */
    public function get_item_by_id($id, $inline_context = null)
    {
        try {
            $result = false;

            foreach($this->get_item_list($inline_context) as $component) {
                if ($component['id'] === $id) {
                    $result = $component;
                    break;
                }
            }

            if ($result === false) {
                throw new OutOfRangeException('Component does not exist');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
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
     * @throws RuntimeException If fails to persist changes
     */
    public function update_component_permission(
        $id, $is_hidden = true, $inline_context = null
    ) {
        try {
            $component = $this->get_item_by_id($id);
            $subject   = $this->_get_subject($inline_context);
            $object    = $subject->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE);
            $screen_id = $this->_convert_screen_id($component['screen_id']);
            $internal  = $screen_id . '|' . $component['slug'];

            if ($object->store($internal, $is_hidden) === false) {
                throw new RuntimeException('Failed to persist settings');
            }

            $result = $this->get_item_by_id($id);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
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
     * @throws OutOfRangeException If rule does not exist
     * @throws RuntimeException If fails to persist a rule
     */
    public function delete_component_permission($id, $inline_context = null)
    {
        try {
            $subject   = $this->_get_subject($inline_context);
            $object    = $subject->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE);
            $component = $this->get_item_by_id($id);

            $explicit  = $object->getExplicitOption();
            $screen_id = $this->_convert_screen_id($component['screen_id']);
            $internal  = strtolower($screen_id. '|' . $component['slug']);

            if (isset($explicit[$internal])) {
                unset($explicit[$internal]); // Delete the setting

                $success = $object->setExplicitOption($explicit)->save();
            } else {
                $success = true;
            }

            if (!$success) {
                throw new RuntimeException('Failed to persist the settings');
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
     * @param string $screen_id
     * @param array  $inline_context Runtime context
     *
     * @return array
     *
     * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/401
     * @since 6.9.13 Initial implementation of the method
     *
     * @access public
     * @version 6.9.35
     */
    public function reset($screen_id = null, $inline_context = null)
    {
        try {
            // Reset the object
            $subject = $this->_get_subject($inline_context);
            $object  = $subject->getObject(AAM_Core_Object_Metabox::OBJECT_TYPE);

            $success = true;

            if (empty($screen_id)) {
                $object->reset();
            } else {
                $id          = $this->_convert_screen_id($screen_id);
                $new_options = [];

                // Filter out all the components that belong to specified screen
                foreach($object->getExplicitOption() as $key => $data) {
                    $parts = explode('|', $key);

                    if ($parts[0] !== $id) {
                        $new_options[$key] = $data;
                    }
                }

                $success = $object->setExplicitOption($new_options)->save();
            }

            if ($success){
                $result = $this->get_item_list($screen_id);
            } else {
                throw new RuntimeException('Failed to reset settings');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
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