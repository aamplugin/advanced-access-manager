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
     * @param array  $inline_context Context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_item_list($post_type = null, $inline_context = null)
    {
        global $wp_post_types;

        try {
            $result   = [];
            $resource = $this->get_resource($inline_context);

            // Getting the menu cache so we can build the list
            $cache = AAM_Framework_Utility_Cache::get(self::CACHE_DB_OPTION, []);

            if (!empty($cache) && is_array($cache)) {
                foreach($cache as $p_type => $metaboxes) {
                    // Remove list of metaboxes for indexed post types that no longer
                    // exist
                    if (array_key_exists($p_type, $wp_post_types)) {
                        foreach($metaboxes as $metabox) {
                            array_push($result, $this->_prepare_metabox(
                                $metabox, $p_type, $resource
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
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get existing metabox by slug
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
                throw new OutOfRangeException('Metabox does not exist');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
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
            $metabox  = $this->get_item($slug);
            $resource = $this->get_resource($inline_context);

            // Prepare array of new permissions
            $perms = array_merge($resource->get_permissions(true), [
                $metabox['slug'] => [ 'effect' => $is_hidden ? 'deny' : 'allow' ]
            ]);

            if (!$resource->set_permissions($perms)) {
                throw new RuntimeException('Failed to persist settings');
            }

            $result = $this->get_item($slug);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Delete metabox permission
     *
     * @param string $slug           Sudo-id for the metabox
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
            $resource  = $this->get_resource($inline_context);
            $metabox   = $this->get_item($slug);
            $explicit  = $resource->get_permissions(true);

            if (array_key_exists($metabox['slug'], $explicit)) {
                unset($explicit[$metabox['slug']]); // Delete the setting

                $success = $resource->set_permissions($explicit);
            } else {
                $success = true;
            }

            if (!$success) {
                throw new RuntimeException('Failed to persist the settings');
            }

            $result = $this->get_item($slug);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Reset all permissions
     *
     * @param string $post_type
     * @param array  $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function reset($post_type = null, $inline_context = null)
    {
        try {
            $resource = $this->get_resource($inline_context);
            $success  = true;

            if (empty($post_type)) {
                $resource->reset();
            } else {
                $success = $resource->set_permissions(array_filter(
                    $resource->get_permissions(true),
                    function($key) use ($post_type) {
                        return strpos($key, $post_type) !== 0;
                    }, ARRAY_FILTER_USE_KEY
                ));
            }

            if ($success){
                $result = $this->get_item_list($post_type);
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
    public function get_resource($inline_context = null)
    {
        try {
            $access_level = $this->_get_access_level($inline_context);
            $result       = $access_level->get_resource(
                AAM_Framework_Type_Resource::METABOX
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Normalize and prepare the metabox model
     *
     * @param array                          $metabox
     * @param string                         $post_type
     * @param AAM_Framework_Resource_Metabox $resource
     *
     * @return array
     *
     * @access private
     * @version 6.9.13
     */
    private function _prepare_metabox($metabox, $post_type, $resource)
    {
        $explicit = $resource->get_permissions(true);

        $response = array(
            'slug'         => $metabox['slug'],
            'post_type'    => $post_type,
            'title'        => base64_decode($metabox['title']),
            'is_hidden'    => $resource->is_hidden($metabox['slug']),
            'is_inherited' => !array_key_exists($metabox['slug'], $explicit)
        );

        return $response;
    }

}