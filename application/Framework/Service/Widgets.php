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
     * @param string $screen_id [optional]
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_items($screen_id = null)
    {
        try {
            $result = [];

            // Getting the menu cache so we can build the list
            $cache = AAM_Framework_Manager::_()->cache->get(
                self::CACHE_DB_OPTION, []
            );

            if (!empty($cache) && is_array($cache)) {
                foreach($cache as $s_id => $widgets) {
                    foreach($widgets as $widget) {
                        array_push($result, $this->_prepare_widget($widget, $s_id));
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
     * Alias for the get_items method
     *
     * @param string $screen_id [optional]
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function items($screen_id = null)
    {
        return $this->get_items($screen_id);
    }

    /**
     * Get existing widget by slug
     *
     * @param string $slug Sudo-id for the metabox
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_item($slug)
    {
        try {
            $matches = array_filter($this->get_items(), function($m) use ($slug) {
                return $m['slug'] === $slug;
            });

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
     * Alias for the get_item method
     *
     * @param string $slug
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function item($slug)
    {
        return $this->get_item($slug);
    }

    /**
     * Restrict/hide widget
     *
     * @param mixed $widget
     *
     * @return bool|WP_Error
     *
     * @access public
     * @version 7.0.0
     */
    public function restrict($widget)
    {
        try {
            $slug   = $this->_prepare_widget_slug($widget);
            $result = $this->_update_item_permission($slug, true);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Allow widget
     *
     * @param mixed $widget
     *
     * @return bool|WP_Error
     *
     * @access public
     * @version 7.0.0
     */
    public function allow($widget)
    {
        try {
            $slug   = $this->_prepare_widget_slug($widget);
            $result = $this->_update_item_permission($slug, false);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Reset permissions
     *
     * Resets all permissions if no $widget is provided. Otherwise, try to reset
     * permissions for a given widget or trigger a filter that invokes third-party
     * implementation.
     *
     * @param mixed $widget_or_area [Optional]
     *
     * @return bool
     *
     * @access public
     * @version 7.0.0
     */
    public function reset($widget_or_area = null)
    {
        try {
            $resource = $this->_get_resource();

            if (empty($widget_or_area)) {
                $result = $resource->reset();
            } else {
                $slug     = $this->_prepare_widget_slug($widget_or_area);
                $settings = $resource->get_permissions(true);

                if (array_key_exists($slug, $settings)) {
                    unset($settings[$slug]);
                } else {
                    $settings = apply_filters(
                        'aam_reset_widgets_permissions_filter',
                        $settings,
                        $slug,
                        $resource
                    );
                }

                $result = $resource->set_permissions($settings, true);
            }

        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Determine if metabox is restricted/hidden
     *
     * @param mixed $widget
     *
     * @return bool|WP_Error
     *
     * @access public
     * @version 7.0.0
     */
    public function is_restricted($widget)
    {
        try {
            $slug     = $this->_prepare_widget_slug($widget);
            $resource = $this->_get_resource();

            // Determine if widget is restricted
            $result = $resource->is_restricted($slug);

            // Allow third-party implementations to integrate with the
            // decision making process
            $result = apply_filters(
                'aam_widget_is_restricted_filter',
                $result,
                $resource,
                $widget
            );

            // Prepare the final answer
            $result = is_bool($result) ? $result : false;
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Determine if metabox is allowed
     *
     * @param string $slug
     *
     * @return bool|WP_Error
     *
     * @access public
     * @version 7.0.0
     */
    public function is_allowed($slug)
    {
        $result = $this->is_restricted($slug);

        return is_bool($result) ? !$result : $result;
    }

    /**
     * Get widget resource
     *
     * @return AAM_Framework_Resource_Widget
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_resource()
    {
        try {
            $result = $this->_get_access_level()->get_resource(
                AAM_Framework_Type_Resource::WIDGET
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Prepare widget slug
     *
     * @param mixed $widget
     *
     * @return string
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_widget_slug($widget)
    {
        // Determining metabox slug
        if (is_array($widget) && isset($widget['callback'])) {
            $result = AAM_Framework_Manager::_()->misc->callable_to_slug(
                $widget['callback']
            );
        } elseif (is_a($widget, WP_Widget::class)) {
            $result = AAM_Framework_Manager::_()->misc->callable_to_slug($widget);
        } elseif (is_string($widget)) {
            $result = AAM_Framework_Manager::_()->misc->sanitize_slug($widget);
        } else {
            throw new InvalidArgumentException('Invalid metabox provided');
        }

        return $result;
    }

    /**
     * Update existing widget permission
     *
     * @param string $slug
     * @param bool   $is_restricted
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _update_item_permission($slug, $is_restricted)
    {
        try {
            $resource = $this->_get_resource();

            // Prepare array of new permissions and save them
            $result = $resource->set_permissions(array_merge(
                $resource->get_permissions(true),
                [ $slug => [ 'effect' => $is_restricted ? 'deny' : 'allow' ] ]
            ));
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Normalize and prepare the widget model
     *
     * @param array  $widget
     * @param string $screen_id
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_widget($widget, $screen_id)
    {
        return [
            'slug'          => $widget['slug'],
            'screen_id'     => $screen_id,
            'title'         => base64_decode($widget['title']),
            'is_restricted' => $this->is_restricted($widget['slug']),
        ];
    }

}