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
     * Return the list of all indexed metaboxes
     *
     * If `$screen_id` is provided, the method returns only metaboxes that are
     * rendered on this screen. In WordPress, it appears that screen_id directly
     * correlates to the post type, so when you edit let's say page, the screen id is
     * `page` or if you edit a post, the screen id is `post`.
     *
     * @param string $screen_id
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_items($screen_id = null)
    {
        global $wp_post_types;

        try {
            $result = [];

            // Getting the menu cache so we can build the list
            $cache = $this->cache->get(self::CACHE_DB_OPTION, []);

            if (!empty($cache) && is_array($cache)) {
                foreach($cache as $type => $metaboxes) {
                    // Remove list of metaboxes for indexed post types that no longer
                    // exist
                    if (array_key_exists($type, $wp_post_types)) {
                        foreach($metaboxes as $metabox) {
                            array_push($result, $this->_prepare_metabox($metabox));
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
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alias for the get_items method
     *
     * @param string $screen_id [Optional]
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
     * Get existing metabox by slug for given screen id
     *
     * If screen id is not provided, AAM assumes that we are trying to get global
     * access controls to the metabox.
     *
     * @param string $slug
     * @param string $screen_id [Optional]
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_item($slug, $screen_id = null)
    {
        try {
            $matches = array_filter(
                $this->get_items($screen_id),
                function($metabox) use ($slug) {
                    return $metabox['slug'] === $slug;
                }
            );

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
     * @param string $screen_id [Optional]
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function item($slug, $screen_id = null)
    {
        return $this->get_item($slug, $screen_id);
    }

    /**
     * Restrict/hide metabox
     *
     * @param string|array $metabox
     * @param string       $screen_id [Optional]
     *
     * @return bool|WP_Error
     *
     * @access public
     * @version 7.0.0
     */
    public function restrict($metabox, $screen_id = null)
    {
        try {
            $slug   = $this->_prepare_metabox_slug($metabox);
            $result = $this->_update_item_permission(
                is_null($screen_id) ? $slug : $screen_id . '_' . $slug,
                true
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Allow metabox
     *
     * @param string|array $metabox
     * @param string       $screen_id [Optional]
     *
     * @return bool|WP_Error
     *
     * @access public
     * @version 7.0.0
     */
    public function allow($metabox, $screen_id = null)
    {
        try {
            $slug   = $this->_prepare_metabox_slug($metabox);
            $result = $this->_update_item_permission(
                is_null($screen_id) ? $slug : $screen_id . '_' . $slug,
                false
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Reset permissions
     *
     * This method resets all permissions. If no `$metabox` provided, all
     * permissions are reset. If only `$metabox` metabox provided - the specific
     * settings for that metabox are reset. Otherwise the scoped metabox settings are
     * reset.
     *
     * @param string|array $metabox   [Optional]
     * @param string       $screen_id [Optional]
     *
     * @return bool
     *
     * @access public
     * @version 7.0.0
     */
    public function reset($metabox = null, $screen_id = null)
    {
        try {
            $resource = $this->_get_resource();

            // If neither metabox nor screen id provided, assume that we would like
            // to reset all metabox settings
            if (is_null($metabox) && is_null($screen_id)) {
                $result = $resource->reset();
            } else {
                $settings = $resource->get_permissions(true);
                $slug     = $this->_prepare_metabox_slug($metabox);

                // Check if we have any permissions defined just for a given slug.
                // Keep in mind that the $slug can be also a screen id because the
                // reset method has two overloads
                if (array_key_exists($slug, $settings)) {
                    unset($settings[$slug]);
                } elseif(array_key_exists("{$screen_id}_{$slug}", $settings))  {
                    unset($settings["{$screen_id}_{$slug}"]);
                } else {
                    $settings = apply_filters(
                        'aam_reset_metaboxes_permissions_filter',
                        $settings,
                        $slug,
                        $screen_id,
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
     * @param string|array $metabox
     * @param string       $screen_id [Optional]
     *
     * @return bool|WP_Error
     *
     * @access public
     * @version 7.0.0
     */
    public function is_restricted($metabox, $screen_id = null)
    {
        try {
            $result = null;

            // Determining metabox slug
            $slug = $this->_prepare_metabox_slug($metabox);

            // Getting resource
            $resource = $this->_get_resource();

            // Step #1. Check if there are any settings for metabox and specific
            // screen ID
            $screen_id = $this->_prepare_screen_id($screen_id);

            if (!is_null($screen_id)) {
                $result = $resource->is_restricted($screen_id . '_'. $slug);
            }

            // Step #2. If there are no scoped access controls defined to a given
            // metabox, check if there are any settings for it by the slug as-is
            if (is_null($result)) {
                $result = $resource->is_restricted($slug);
            }

            // Allow third-party implementations to integrate with the
            // decision making process
            $result = apply_filters(
                'aam_metabox_is_restricted_filter',
                $result,
                $resource,
                $screen_id,
                $slug
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
     * @param string|array $metabox
     * @param string       $screen_id [Optional]
     *
     * @return bool|WP_Error
     *
     * @access public
     * @version 7.0.0
     */
    public function is_allowed($metabox, $screen_id = null)
    {
        $result = $this->is_restricted($metabox, $screen_id);

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
     * Prepare metabox slug
     *
     * @param string|array $metabox
     *
     * @return string
     *
     * @access private
     */
    private function _prepare_metabox_slug($metabox)
    {
        // Determining metabox slug
        if (is_array($metabox) && isset($metabox['callback'])) {
            $result = $this->misc->callable_to_slug($metabox['callback']);
        } elseif (is_string($metabox)) {
            $result = $this->misc->sanitize_slug($metabox);
        } else {
            throw new InvalidArgumentException('Invalid metabox provided');
        }

        return $result;
    }

    /**
     * Prepare screen ID
     *
     * @param string|null $screen_id
     *
     * @return string|null
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_screen_id($screen_id)
    {
        $result = null;

        if (is_null($screen_id)) {
            if (function_exists('get_current_screen')) {
                $screen = get_current_screen();

                if (is_a($screen, WP_Screen::class)) {
                    $result = $screen->id;
                }
            }
        } elseif (is_string($screen_id)) {
            $result = $screen_id;
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
     * @param array $metabox
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_metabox($metabox)
    {
        return [
            'slug'          => $metabox['slug'],
            'screen_id'     => $metabox['screen_id'],
            'title'         => base64_decode($metabox['title']),
            'is_restricted' => $this->is_restricted(
                $metabox['slug'], $metabox['screen_id']
            )
        ];
    }

}