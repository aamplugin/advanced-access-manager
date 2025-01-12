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
     * @param string $area [Optional]
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     * @todo - Consider moving to AAM_Service_Widget
     */
    public function get_items($area = null)
    {
        try {
            $result = [];

            // Getting the menu cache so we can build the list
            $cache = $this->cache->get(self::CACHE_DB_OPTION, []);

            if (!empty($cache) && is_array($cache)) {
                foreach($cache as $s_id => $widgets) {
                    foreach($widgets as $widget) {
                        array_push($result, $this->_prepare_widget($widget, $s_id));
                    }
                }
            }

            if (!empty($screen_id)) {
                $result = array_values(
                    array_filter($result, function($c) use ($area) {
                        return $c['area'] === $area;
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
     * @param string $area [Optional]
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function items($area = null)
    {
        return $this->get_items($area);
    }

    /**
     * Get existing widget by slug
     *
     * @param string $slug Sudo-id for the metabox
     *
     * @return array
     * @access public
     *
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
     * @access public
     *
     * @version 7.0.0
     */
    public function item($slug)
    {
        return $this->get_item($slug);
    }

    /**
     * Restrict/hide widget
     *
     * @param mixed  $widget
     * @param string $website_area [Optional]
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function deny($widget, $website_area = null)
    {
        try {
            $result = $this->_update_item_permission($widget, $website_area, true);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Allow widget
     *
     * @param mixed  $widget
     * @param string $website_area [Optional]
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function allow($widget, $website_area = null)
    {
        try {
            $result = $this->_update_item_permission($widget, $website_area, false);
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
     * @param mixed  $widget [Optional]
     * @param string $area   [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function reset($widget = null, $area = null)
    {
        try {
            $resource = $this->_get_resource();

            if (empty($widget) && empty($area)) {
                $result = $resource->reset();
            } else {
                $result = $resource->reset(
                    $this->_normalize_resource_identifier($widget, $area)
                );
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
     * @access public
     *
     * @version 7.0.0
     */
    public function is_denied($widget)
    {
        try {
            $result     = null;
            $identifier = $this->_normalize_resource_identifier($widget);
            $resource   = $this->_get_resource();
            $permission = $resource->get_permission($identifier, 'access');

            // Determine if widget is restricted
            if (!empty($permission)) {
                $result = $permission['effect'] !== 'allow';
            }

            // Allow third-party implementations to integrate with the
            // decision making process
            $result = apply_filters(
                'aam_widget_is_denied_filter',
                $result,
                $widget,
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
     * Determine if metabox is allowed
     *
     * @param string $slug
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function is_allowed($slug)
    {
        $result = $this->is_denied($slug);

        return is_bool($result) ? !$result : $result;
    }

    /**
     * Get widget resource
     *
     * @return AAM_Framework_Resource_Widget
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource()
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::WIDGET
        );
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
            $result = $this->misc->callable_to_slug($widget['callback']);
        } elseif (is_a($widget, WP_Widget::class)) {
            $result = $this->misc->callable_to_slug($widget);
        } elseif (is_string($widget)) {
            $result = $this->misc->sanitize_slug($widget);
        } else {
            throw new InvalidArgumentException('Invalid metabox provided');
        }

        return $result;
    }

    /**
     * Update existing widget permission
     *
     * @param mixed       $widget
     * @param string|null $area
     * @param bool        $is_denied
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _update_item_permission($widget, $area, $is_denied)
    {
        try {
            $resource = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier($widget, $area);

            // Prepare array of new permissions and save them
            $result = $resource->set_permission(
                $identifier,
                'access',
                $is_denied ? 'deny' : 'allow'
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Convert metabox and screen ID into resource identifier
     *
     * @param mixed       $widget
     * @param string|null $area   [Optional]
     *
     * @return object
     * @access private
     *
     * @version 7.0.0
     */
    private function _normalize_resource_identifier($widget, $area = null)
    {
       return (object) [
            'slug' => $this->_prepare_widget_slug($widget),
            'area' => empty($area) ? $this->_determine_widget_area($area) : $area
       ];
    }

    /**
     * Determine widget's area
     *
     * @param mixed $widget
     *
     * @return string|null
     *
     * @access private
     */
    private function _determine_widget_area($widget)
    {
        global $wp_registered_widgets, $wp_meta_boxes;

        // Prepare the search array
        $collection = [];

        if (isset($wp_meta_boxes['dashboard'])) {
            foreach ($wp_meta_boxes['dashboard'] as $groups) {
                foreach($groups as $widgets) {
                    foreach($widgets as $w) {
                        if (is_array($w)) { // Widget is still valid and not filtered
                            array_push($collection, [
                                'area' => 'dashboard',
                                'slug' => AAM::api()->misc->callable_to_slug(
                                    $w['callback']
                                )
                            ]);
                        }
                    }
                }
            }
        }

        if (is_array($wp_registered_widgets)) {
            array_push(
                $collection,
                ...array_values(array_map(function($w) {
                    return [
                        'area' => 'frontend',
                        'slug' => AAM::api()->misc->callable_to_slug($w['callback'])
                    ];
                }, $wp_registered_widgets))
            );
        }

        $area = null;

        // How are we going to search - by widget array or slug?
        if (is_string($widget)){
            foreach($collection as $item) {
                if ($item['slug'] === $widget) {
                    $area = $item['area'];
                    break;
                }
            }
        } else {
            $widget_slug = AAM::api()->misc->callable_to_slug($widget['callback']);

            foreach($collection as $item) {
                if ($item['slug'] === $widget_slug) {
                    $area = $item['area'];
                    break;
                }
            }
        }

        return $area;
    }

    /**
     * Normalize and prepare the widget model
     *
     * @param array  $widget
     * @param string $screen_id
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     * @todo Move this to RESTful class
     */
    private function _prepare_widget($widget, $screen_id)
    {
        return [
            'slug'          => $widget['slug'],
            'screen_id'     => $screen_id,
            'title'         => base64_decode($widget['title']),
            'is_restricted' => $this->is_denied($widget['slug']),
        ];
    }

}