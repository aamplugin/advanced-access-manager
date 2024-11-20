<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service for widgets
 *
 * Widgets are functional block that are rendered on the admin dashboard and frontend
 * areas
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Service_Widgets
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Collection of captured widgets
     *
     * @version 7.0.0
     */
    const CACHE_DB_OPTION = 'aam_widgets_cache';

    /**
     * Return the complete list of all indexed widgets
     *
     * @param string $screen_id
     * @param array  $inline_context Context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_item_list($screen_id = null, $inline_context = null)
    {
        try {
            $result   = [];
            $resource = $this->get_resource($inline_context);

            // Getting the menu cache so we can build the list
            $cache = AAM_Framework_Utility_Cache::get(self::CACHE_DB_OPTION, []);

            if (!empty($cache) && is_array($cache)) {
                foreach($cache as $s_id => $widgets) {
                    foreach($widgets as $widget) {
                        array_push($result, $this->_prepare_widget(
                            $widget, $s_id, $resource
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
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get existing widget by slug
     *
     * @param string $slug           Sudo-id for the metabox
     * @param array  $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_item($slug, $inline_context = null)
    {
        try {
            $matches = array_filter(
                $this->get_item_list($inline_context),
                function($m) use ($slug) {
                    return $m['slug'] === $slug;
                }
            );

            $result = array_shift($matches);

            if ($result === null) {
                throw new OutOfRangeException('Widget does not exist');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Update existing metabox permission
     *
     * @param string $slug           Sudo-id for the metabox
     * @param bool   $is_hidden      Is hidden or not
     * @param array  $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function update_item_permission(
        $slug, $is_hidden = true, $inline_context = null
    ) {
        try {
            $widget      = $this->get_item($slug);
            $resource    = $this->get_resource($inline_context);
            $permissions = array_merge($resource->get_permissions(true), [
                $widget['slug'] => [ 'effect' => $is_hidden ? 'deny' : 'allow' ]
            ]);

            if (!$resource->set_permissions($permissions)) {
                throw new RuntimeException('Failed to persist settings');
            }

            $result = $this->get_item($slug);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Delete widget permission
     *
     * @param string $slug           Sudo-id for the widget
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
            $resource = $this->get_resource($inline_context);
            $widget   = $this->get_item($slug);
            $explicit = $resource->get_permissions(true);

            if (array_key_exists($widget['slug'], $explicit)) {
                unset($explicit[$widget['slug']]); // Delete the setting

                $success = $resource->set_permissions($explicit);
            } else {
                $success = true;
            }

            if (!$success) {
                throw new RuntimeException('Failed to persist the settings');
            }

            $result = $this->get_item($slug);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
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
            $resource = $this->get_resource($inline_context);
            $success  = true;

            if (empty($screen_id)) {
                $resource->reset();
            } else {
                $success = $resource->set_permissions(array_filter(
                    $resource->get_permissions(true),
                    function($key) use ($screen_id) {
                        return strpos($key, $screen_id) !== 0;
                    }, ARRAY_FILTER_USE_KEY
                ));
            }

            if ($success){
                $result = $this->get_item_list($screen_id);
            } else {
                throw new RuntimeException('Failed to reset settings');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get widget resource
     *
     * @param array $inline_context
     *
     * @return AAM_Framework_Resource_Widget
     *
     * @access public
     * @version 7.0.0
     */
    public function get_resource($inline_context = null)
    {
        try {
            $access_level = $this->_get_access_level($inline_context);
            $result       = $access_level->get_resource(
                AAM_Framework_Type_Resource::WIDGET
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Normalize and prepare the widget model
     *
     * @param array                         $widget
     * @param string                        $screen_id
     * @param AAM_Framework_Resource_Widget $resource
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_widget($widget, $screen_id, $resource)
    {
        $explicit = $resource->get_permissions(true);

        $response = array(
            'slug'         => $widget['slug'],
            'screen_id'    => $screen_id,
            'title'        => base64_decode($widget['title']),
            'is_hidden'    => $resource->is_hidden($widget['slug']),
            'is_inherited' => !array_key_exists($widget['slug'], $explicit)
        );

        return $response;
    }

}