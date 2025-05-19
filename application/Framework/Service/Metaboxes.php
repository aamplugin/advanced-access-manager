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
    const CACHE_OPTION = 'aam_metaboxes';

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
     * @access public
     *
     * @version 7.0.0
     * @todo - Move to AAM_Service_Metaboxes
     */
    public function get_items($screen_id = null)
    {
        global $wp_post_types;

        try {
            $result = [];

            // Getting the menu cache so we can build the list
            $cache = $this->cache->get(self::CACHE_OPTION, []);

            if (!empty($cache) && is_array($cache)) {
                foreach($cache as $type => $metaboxes) {
                    // Remove list of metaboxes for indexed post types that no longer
                    // exist
                    if (array_key_exists($type, $wp_post_types)) {
                        foreach($metaboxes as $metabox) {
                            array_push($result, $this->_prepare_metabox(
                                $metabox, $screen_id
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
     * @access public
     *
     * @version 7.0.0
     * @todo - Move to AAM_Service_Metaboxes
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
     * @access public
     *
     * @version 7.0.0
     * @todo - Move to AAM_Service_Metaboxes
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
     * @access public
     *
     * @version 7.0.0
     * @todo - Move to AAM_Service_Metaboxes
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
     * @access public
     *
     * @version 7.0.0
     */
    public function deny($metabox, $screen_id = null)
    {
        try {
            $result = $this->_update_item_permission($metabox, $screen_id, 'deny');
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
     * @access public
     *
     * @version 7.0.0
     */
    public function allow($metabox, $screen_id = null)
    {
        try {
            $result = $this->_update_item_permission($metabox, $screen_id, 'allow');
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
     * @access public
     *
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
                $result = $resource->remove_permission(
                    $this->_normalize_resource_identifier($metabox, $screen_id),
                    'list'
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
     * @param string|array $metabox
     * @param string       $screen_id [Optional]
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function is_denied($metabox, $screen_id = null)
    {
        try {
            $result = $this->_is_denied($metabox, $screen_id);
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
     * @access public
     *
     * @version 7.0.0
     */
    public function is_allowed($metabox, $screen_id = null)
    {
        $result = $this->is_denied($metabox, $screen_id);

        return is_bool($result) ? !$result : $result;
    }

    /**
     * @inheritDoc
     *
     * @param mixed  $metabox   [Optional]
     * @param string $screen_id [Optional]
     */
    public function is_customized($metabox = null, $screen_id = null)
    {
        try {
            if (empty($metabox) && empty($screen_id)) {
                $result = $this->_get_resource()->is_customized();
            } else {
                $result = $this->_get_resource()->is_customized(
                    $this->_normalize_resource_identifier($metabox, $screen_id)
                );
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if metabox is restricted
     *
     * @param mixed       $metabox
     * @param string|null $screen_id
     *
     * @return bool
     * @access private
     *
     * @version 7.0.0
     */
    private function _is_denied($metabox, $screen_id)
    {
        $result     = null;
        $resource   = $this->_get_resource();
        $identifier = $this->_normalize_resource_identifier($metabox, $screen_id);
        $permission = $resource->get_permission($identifier, 'list');

        if (!empty($permission)) {
            $result = $permission['effect'] !== 'allow';
        }

        // Allow third-party implementations to integrate with the
        // decision making process
        $result = apply_filters(
            'aam_metabox_is_denied_filter',
            $result,
            $identifier,
            $resource
        );

        return is_bool($result) ? $result : false;
    }

    /**
     * Get metabox resource
     *
     * @return AAM_Framework_Resource_Metabox
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource()
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::METABOX
        );
    }

    /**
     * Convert metabox and screen ID into resource identifier
     *
     * @param mixed       $metabox
     * @param string|null $screen_id
     *
     * @return object
     * @access private
     *
     * @version 7.0.0
     */
    private function _normalize_resource_identifier($metabox, $screen_id)
    {
       return (object) [
            'slug'      => $this->_prepare_metabox_slug($metabox),
            'screen_id' => $screen_id
       ];
    }

    /**
     * Prepare metabox slug
     *
     * @param string|array $metabox
     *
     * @return string
     * @access private
     *
     * @version 7.0.3
     */
    private function _prepare_metabox_slug($metabox)
    {
        // Determining metabox slug
        if (is_array($metabox) && isset($metabox['callback'])) {
            $result = $this->misc->callable_to_slug($metabox['callback']);
            $tax    = $this->misc->get($metabox, 'args.taxonomy');

            if (!empty($tax) && is_string($tax)) {
                $result = $tax . '_' . $result;
            }

            // Taking into consideration Closures
            if (empty($result)) {
                $result = $this->misc->sanitize_slug($metabox['id']);
            }
        } elseif (is_string($metabox)) {
            $result = $this->misc->sanitize_slug($metabox);
        } else {
            throw new InvalidArgumentException('Invalide metabox provided');
        }

        return $result;
    }

    /**
     * Update existing metabox permission
     *
     * @param mixed       $metabox
     * @param string|null $screen_id
     * @param string      $effect
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _update_item_permission($metabox, $screen_id, $effect)
    {
        try {
            $result = $this->_get_resource()->set_permission(
                $this->_normalize_resource_identifier($metabox, $screen_id),
                'list',
                $effect
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Normalize and prepare the metabox model
     *
     * @param mixed       $metabox
     * @param string|null $screen_id
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     * @todo Move this to RESTful class
     */
    private function _prepare_metabox($metabox, $screen_id)
    {
        return [
            'slug'          => $metabox['slug'],
            'screen_id'     => $metabox['screen_id'],
            'title'         => base64_decode($metabox['title']),
            'is_restricted' => $this->_is_denied(
                $metabox['slug'],
                $screen_id ? $screen_id : $metabox['screen_id']
            )
        ];
    }

}