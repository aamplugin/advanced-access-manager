<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service for metaboxes
 *
 * Metaboxes are functional block that are rendered on the post edit admin screen
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
    const CACHE_DB_OPTION = 'aam_metaboxes_cache';

    /**
     * Return the complete list of all indexed metaboxes
     *
     * @param string $post_type
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_items($post_type = null)
    {
        global $wp_post_types;

        try {
            $result = [];

            // Getting the menu cache so we can build the list
            $cache = AAM_Framework_Utility_Cache::get(self::CACHE_DB_OPTION, []);

            if (!empty($cache) && is_array($cache)) {
                foreach($cache as $type => $metaboxes) {
                    // Remove list of metaboxes for indexed post types that no longer
                    // exist
                    if (array_key_exists($type, $wp_post_types)) {
                        foreach($metaboxes as $metabox) {
                            array_push($result, $this->_prepare_metabox(
                                $metabox, $type
                            ));
                        }
                    }
                }
            }

            if (!empty($post_type)) {
                $result = array_values(
                    array_filter($result, function($c) use ($post_type) {
                        return $c['post_type'] === $post_type;
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
     * @param string $post_type [optional]
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function items($post_type = null)
    {
        return $this->get_items($post_type);
    }

    /**
     * Get existing metabox by slug
     *
     * @param string $slug
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
                throw new OutOfRangeException('Metabox does not exist');
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
     * Restrict/hide metabox
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
     * Allow metabox
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
     * the $prefix contains metabox slug, only explicit settings for given metabox
     * will be reset, otherwise, all the metabox slugs that start with given prefix
     * will be reset.
     *
     * @param string $prefix [optional] Either metabox slug or post type
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
                'aam_metabox_is_restricted_filter',
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
     * Get metabox resource
     *
     * @return AAM_Framework_Resource_Metabox
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_resource($slug = null)
    {
        try {
            $access_level = $this->_get_access_level();
            $result       = $access_level->get_resource(
                AAM_Framework_Type_Resource::METABOX, $slug
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Update existing metabox permission
     *
     * @param string $slug          Sudo-id for the metabox
     * @param bool   $is_restricted Is hidden or not
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
     * Normalize and prepare the metabox model
     *
     * @param array  $metabox
     * @param string $post_type
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_metabox($metabox, $post_type)
    {
        $resource = $this->_get_resource($metabox['slug']);
        $explicit = $resource->get_permissions(true);

        $response = array(
            'slug'          => $metabox['slug'],
            'post_type'     => $post_type,
            'title'         => base64_decode($metabox['title']),
            'is_restricted' => $this->is_restricted($metabox['slug']),
            'is_inherited'  => !array_key_exists($metabox['slug'], $explicit)
        );

        return $response;
    }

}