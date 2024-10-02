<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service for Metaboxes
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Service_Metaboxes
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Collection of captured metaboxes
     *
     * @version 7.0.0
     */
    const CACHE_DB_OPTION = 'aam_metabox_cache';

    /**
     * Return the complete list of all indexed metaboxes & widgets
     *
     * @param array $inline_context Context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_item_list($screen_id = null, $inline_context = null)
    {
        global $wp_post_types;

        try {
            $result   = [];
            $resource = $this->get_resource(true, $inline_context);

            // Getting the menu cache so we can build the list
            $cache = AAM_Framework_Utility_Cache::get(self::CACHE_DB_OPTION, []);

            if (!empty($cache) && is_array($cache)) {
                foreach($cache as $s_id => $components) {
                    // Remove list of metaboxes for indexed post types that no longer
                    // exist
                    if (array_key_exists($s_id, $wp_post_types)) {
                        foreach($components as $component) {
                            array_push($result, $this->_prepare_component(
                                $component, $s_id, $resource
                            ));
                        }
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
     * Get the complete list of admin screens AAM uses to index metaboxes
     *
     * @param array $inline_context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_screen_list($inline_context = null)
    {
        global $wp_post_types;

        try {
            $result = [];

            foreach (array_keys($wp_post_types) as $type) {
                if ($wp_post_types[$type]->show_ui) {
                    $result[] = add_query_arg(
                        'init',
                        'metabox',
                        admin_url('post-new.php?post_type=' . $type)
                    );
                }
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
     * @version 7.0.0
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
     * @version 7.0.0
     */
    public function update_component_permission(
        $id, $is_hidden = true, $inline_context = null
    ) {
        try {
            $component = $this->get_item_by_id($id);
            $resource  = $this->get_resource(false, $inline_context);
            $screen_id = $this->_convert_screen_id($component['screen_id']);
            $internal  = $screen_id . '|' . $component['slug'];

            if (!$resource->set_permission($internal, $is_hidden)) {
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
     * @version 7.0.0
     */
    public function delete_component_permission($id, $inline_context = null)
    {
        try {
            $resource  = $this->get_resource(false, $inline_context);
            $component = $this->get_item_by_id($id);
            $explicit  = $resource->get_permissions(true);
            $screen_id = $this->_convert_screen_id($component['screen_id']);
            $internal  = strtolower($screen_id. '|' . $component['slug']);

            if (isset($explicit[$internal])) {
                unset($explicit[$internal]); // Delete the setting

                $success = $resource->set_permissions($explicit);
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
     * @access public
     * @version 7.0.0
     */
    public function reset($screen_id = null, $inline_context = null)
    {
        try {
            $resource = $this->get_resource(false, $inline_context);
            $success  = true;

            if (empty($screen_id)) {
                $resource->reset();
            } else {
                $id          = $this->_convert_screen_id($screen_id);
                $new_options = [];

                // Filter out all the components that belong to specified screen
                foreach($resource->get_permissions(true) as $key => $data) {
                    $parts = explode('|', $key);

                    if ($parts[0] !== $id) {
                        $new_options[$key] = $data;
                    }
                }

                $success = $resource->set_permissions($new_options);
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
     * Get metabox resource
     *
     * @param array $inline_context
     *
     * @return AAM_Framework_Resource_Metabox
     *
     * @access public
     * @version 7.0.0
     */
    public function get_resource($reload = false, $inline_context = null)
    {
        try {
            $access_level = $this->_get_access_level($inline_context);
            $result       = $access_level->get_resource(
                AAM_Framework_Type_Resource::METABOX, null, $reload
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Normalize and prepare the component model
     *
     * @param array                          $component
     * @param string                         $screen_id
     * @param AAM_Framework_Resource_Metabox $resource
     *
     * @return array
     *
     * @access private
     * @version 6.9.13
     */
    private function _prepare_component($component, $screen_id, $resource)
    {
        $explicit = $resource->get_permissions(true);
        $internal = strtolower($screen_id . '|' . $component['slug']);

        $response = array(
            'id'           => abs(crc32($internal)),
            'slug'         => strtolower($component['slug']),
            'screen_id'    => $this->_convert_screen_id($screen_id),
            'name'         => $this->_prepare_component_name($component),
            'is_hidden'    => $resource->is_hidden($screen_id, $component['slug']),
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