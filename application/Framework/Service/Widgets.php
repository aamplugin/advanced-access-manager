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
            $cache = AAM_Framework_Utility_Cache::get(self::CACHE_DB_OPTION, []);

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
     * @param string $slug
     *
     * @return bool|WP_Error
     *
     * @access public
     * @version 7.0.0
     */
    public function restrict($slug)
    {
        try {
            $result = $this->_update_item_permission($slug, true);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Allow widget
     *
     * @param string $slug
     *
     * @return bool|WP_Error
     *
     * @access public
     * @version 7.0.0
     */
    public function allow($slug)
    {
        try {
            $result = $this->_update_item_permission($slug, false);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Reset permissions
     *
     * This method resets all permissions if no $prefix is provided. Otherwise, if
     * the $prefix contains widget slug, only explicit settings for given widget
     * will be reset, otherwise, all the widget slugs that start with given prefix
     * will be reset.
     *
     * @param string $prefix [optional] Either widget slug or screen ID
     *
     * @return bool
     *
     * @access public
     * @version 7.0.0
     */
    public function reset($prefix = null)
    {
        try {
            $resource = $this->_get_resource();

            if (empty($post_type)) {
                $result = $resource->reset();
            } else {
                $settings = $resource->get_permissions(true);

                if (array_key_exists($prefix, $settings)) {
                    unset($settings[$prefix]);
                } else {
                    $settings = array_filter($settings, function($k) use ($prefix) {
                        return strpos($k, $prefix) !== 0;
                    }, ARRAY_FILTER_USE_KEY);
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
     * @param string $slug
     *
     * @return bool|WP_Error
     *
     * @access public
     * @version 7.0.0
     */
    public function is_restricted($slug)
    {
        try {
            $resource = $this->_get_resource($slug);
            $result   = $resource->is_restricted();

            // Allow third-party implementations to integrate with the
            // decision making process
            $result = apply_filters(
                'aam_widget_is_restricted_filter',
                $result,
                $slug,
                $resource->get_permissions()
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
     * @param string $slug
     *
     * @return AAM_Framework_Resource_Widget
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_resource($slug = null)
    {
        try {
            $access_level = $this->_get_access_level();
            $result       = $access_level->get_resource(
                AAM_Framework_Type_Resource::WIDGET, $slug
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Update existing widget permission
     *
     * @param string $slug          Widget slug/id
     * @param bool   $is_restricted
     *
     * @return array
     *
     * @access private
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
        $resource = $this->_get_resource($widget['slug']);
        $explicit = $resource->get_permissions(true);

        $response = array(
            'slug'         => $widget['slug'],
            'screen_id'    => $screen_id,
            'title'        => base64_decode($widget['title']),
            'is_hidden'    => $this->is_restricted($widget['slug']),
            'is_inherited' => !array_key_exists($widget['slug'], $explicit)
        );

        return $response;
    }

}