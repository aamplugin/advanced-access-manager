<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Content service
 *
 * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/399
 *               https://github.com/aamplugin/advanced-access-manager/issues/401
 * @since 6.9.31 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.35
 */
class AAM_Framework_Service_Content
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Get list of registered post types
     *
     * @param array  $args
     * @param string $result_type
     * @param array  $inline_context
     *
     * @return array
     *
     * @access public
     * @version 6.9.31
     */
    public function get_post_types(
        array $args = [], $result_type = 'list', $inline_context = null
    ) {
        try {
            // Get the list of all registered post types based on the provided
            // filters
            $raw_list = get_post_types($args, 'names', 'or');

            if ($result_type === 'summary') {
                $result = [
                    'total_count'    => count($raw_list),
                    'filtered_count' => count(get_post_types())
                ];
            } else {
                $result = [];

                foreach($raw_list as $post_type) {
                    array_push(
                        $result,
                        $this->get_post_type($post_type, $inline_context)
                    );
                }
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get a single post type resource
     *
     * @param string $post_type
     * @param array  $inline_context
     *
     * @return AAM_Framework_Resource_PostType
     *
     * @access public
     * @version 7.0.0
     */
    public function get_post_type($post_type, $inline_context = null)
    {
        try {
            if (!is_string($post_type)) {
                throw new InvalidArgumentException(
                    "The post_type argument has to be a valid string"
                );
            }

            $result = $this->_get_access_level($inline_context)->get_resource(
                AAM_Framework_Type_Resource::POST_TYPE, $post_type, true
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get list of taxonomies
     *
     * @param array $args
     * @param array $inline_context
     *
     * @return array
     *
     * @access public
     * @version 6.9.31
     */
    public function get_taxonomies(
        array $args = [], $result_type = 'list', $inline_context = null
    ) {
        try {
            // Convert the list to models
            $raw_list = get_taxonomies($args, 'names', 'or');

            if ($result_type === 'summary') {
                $result = [
                    'total_count'    => count($raw_list),
                    'filtered_count' => count(get_taxonomies())
                ];
            } else {
                $result = [];

                foreach($raw_list as $taxonomy) {
                    array_push(
                        $result, $this->get_taxonomy($taxonomy, $inline_context)
                    );
                }
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get a single taxonomy resource
     *
     * @param string $taxonomy
     * @param array  $inline_context
     *
     * @return AAM_Framework_Resource_Taxonomy
     *
     * @access public
     * @version 7.0.0
     */
    public function get_taxonomy($taxonomy, $inline_context = null)
    {
        try {
            if (!is_string($taxonomy)) {
                throw new InvalidArgumentException(
                    "The taxonomy argument has to be a valid string"
                );
            }

            $result = $this->_get_access_level($inline_context)->get_resource(
                AAM_Framework_Type_Resource::TAXONOMY, $taxonomy, true
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get list of posts
     *
     * @param array  $args
     * @param string $result_type
     * @param array  $inline_context
     *
     * @return array
     *
     * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/399
     * @since 6.9.31 Initial implementation of the method
     *
     * @access public
     * @version 6.9.35
     */
    public function get_posts(
        array $args = [],  $result_type = 'list', $inline_context = null
    ) {
        try {
            // The minimum required attribute is post_type. If it is not defined,
            // throw an error
            if (empty($args['post_type']) || !is_string($args['post_type'])) {
                throw new InvalidArgumentException(
                    'The post_type has to be a valid string'
                );
            }

            if ($result_type === 'summary') {
                $result = [
                    'total_count'    => $this->_get_post_count($args['post_type']),
                    'filtered_count' => $this->_get_post_count(
                        $args['post_type'], $args['s']
                    )
                ];
            } else {
                $result   = [];
                $raw_list = get_posts(array_merge([
                    'numberposts'      => 10,   // By default, only top 10
                    'suppress_filters' => true,
                    'post_status'      => 'any',
                    'search_columns'   => ['post_title']
                ], $args, [ 'fields' => 'ids' ]));

                foreach($raw_list as $id) {
                    array_push(
                        $result, $this->get_post($id, $inline_context)
                    );
                }
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get list of terms
     *
     * @param array  $args
     * @param string $result_type
     * @param array  $inline_context
     *
     * @return array
     *
     * @access public
     * @version 6.9.31
     */
    public function get_terms(
        array $args = [], $result_type = 'list', $inline_context = null
    ) {
        try {
            // The minimum required attribute is taxonomy. If it is not defined,
            // throw an error
            if (empty($args['taxonomy']) || !is_string($args['taxonomy'])) {
                throw new InvalidArgumentException(
                    'The taxonomy has to be a valid string'
                );
            }

            if ($result_type === 'summary') {
                $result = [
                    'total_count'    => intval(get_terms([
                        'fields'           => 'count',
                        'hide_empty'       => false,
                        'suppress_filters' => true,
                        'taxonomy'         => $args['taxonomy']
                    ])),
                    'filtered_count' => intval(get_terms(array_merge(
                        $args, [ 'fields' => 'count', 'suppress_filters' => true ]
                    )))
                ];
            } else {
                // Get the paginated list of terms
                $terms = get_terms(array_merge([
                    'number'           => 10,
                    'fields'           => 'ids',
                    'suppress_filters' => true,
                    'hide_empty'       => false
                ], $args));

                $result = [];

                foreach($terms as $term_id) {
                    array_push(
                        $result, $this->get_term($term_id, $inline_context)
                    );
                }
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get a single term resource
     *
     * @param int|array $term_id
     * @param array     $inline_context
     *
     * @return AAM_Framework_Resource_Term
     *
     * @access public
     * @version 7.0.0
     */
    public function get_term($term_id, $inline_context = null)
    {
        try {
            if (is_array($term_id)) {
                if (!isset($term_id['id']) || !is_numeric($term_id['id'])) {
                    throw new InvalidArgumentException(
                        "The term_id has to have a valid numeric id"
                    );
                }

                if (!isset($term_id['taxonomy']) || !is_string($term_id['taxonomy'])) {
                    throw new InvalidArgumentException(
                        "The term_id has to have a valid string taxonomy"
                    );
                }
            } elseif (!is_numeric($term_id)) {
                throw new InvalidArgumentException(
                    "The term_id argument has to be a valid numeric value"
                );
            }

            $result = $this->_get_access_level($inline_context)->get_resource(
                AAM_Framework_Type_Resource::TERM, $term_id, true
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get a post
     *
     * @param int   $post_id
     * @param array $inline_context
     *
     * @return array
     *
     * @access public
     * @version 6.9.31
     */
    public function get_post($post_id, $inline_context = null)
    {
        try {
            $post = get_post($post_id);

            if (!is_a($post, 'WP_Post')) {
                throw new OutOfRangeException(
                    "Post with ID {$post_id} does not exist"
                );
            }

            $result = $this->_get_access_level($inline_context)->get_resource(
                AAM_Framework_Type_Resource::POST, $post_id, true
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Update post permissions
     *
     * @param int   $post_id
     * @param array $permissions
     * @param array $inline_context
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.31
     */
    public function update_post_permissions(
        $post_id, array $permissions = [], $inline_context = null
    ) {
        try {
            $access_level = $this->_get_access_level($inline_context);
            $post         = $access_level->get_resource(
                AAM_Framework_Type_Resource::POST, $post_id
            );

            // Set new permissions
            $result = $post->set_explicit_settings($permissions);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Set post permission
     *
     * @param int    $post_id
     * @param string $permission
     * @param array  $settings
     * @param array  $inline_context
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function set_post_permission(
        $post_id, $permission, $settings, $inline_context = null
    ) {
        try {
            $access_level = $this->_get_access_level($inline_context);
            $post         = $access_level->get_resource(
                AAM_Framework_Type_Resource::POST, $post_id
            );

            $settings = $this->_validate_permission($permission, $settings);

            // Get list of explicitly defined permissions and override existing
            // permission or add it to the list, if not yet defined
            $is_replaced       = false;
            $explicit_settings = [];

            foreach($post->get_explicit_settings() as $p) {
                if ($p['permission'] === $permission) {
                    array_push($explicit_settings, array_merge(
                        [ 'permission' => $permission ],
                        $settings
                    ));

                    $is_replaced = true;
                } else {
                    array_push($explicit_settings, $p);
                }
            }

            if (!$is_replaced) {
                array_push($explicit_settings, array_merge(
                    [ 'permission' => $permission ],
                    $settings
                ));
            }

            // Set new permissions
            $result = $post->set_explicit_settings($explicit_settings);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Delete post permissions
     *
     * @param int   $post_id
     * @param array $inline_context
     *
     * @return boolean
     *
     * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/401
     * @since 6.9.31 Initial implementation of the method
     *
     * @access public
     * @version 6.9.35
     */
    public function delete_post_permissions($post_id, $inline_context = null)
    {
        try {
            $access_level = $this->_get_access_level($inline_context);
            $post         = $access_level->get_resource(
                AAM_Framework_Type_Resource::POST, $post_id
            );

            return $post->reset();
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get list of posts
     *
     * Perform separate computation for the list of posts based on type and search
     * criteria
     *
     * @param string $type
     * @param string $search
     *
     * @return int
     *
     * @access private
     * @global WPDB $wpdb
     * @version 6.9.29
     */
    private function _get_post_count($type, $search = null)
    {
        global $wpdb;

        $query  = "SELECT COUNT(*) AS total FROM {$wpdb->posts} ";
        $query .= 'WHERE (post_type = %s)';

        if (!empty($search)) {
            $query .= ' AND (post_title LIKE %s)';
            $args   = array($type, "%{$search}%");
        } else {
            $args = array($type);
        }

        if ($type === 'attachment') {
            $query .= " AND ({$wpdb->posts}.post_status = %s)";
            $args[] = 'inherit';
        } else {
            $statuses = get_post_stati(array('show_in_admin_all_list' => false));
            foreach ($statuses as $status) {
                $query .= " AND ({$wpdb->posts}.post_status <> %s)";
                $args[] = $status;
            }
        }

        return $wpdb->get_var($wpdb->prepare($query, $args));
    }

    /**
     * Validate permission's settings
     *
     * @param string $permission
     * @param array  $settings
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _validate_permission($permission, $settings)
    {
        $result = [];

        if ($permission === 'list') {
            $result = $this->_validate_list_permission($settings);
        } else {
            $result = apply_filters(
                'aam_validate_content_permission_filter', $settings, $permission
            );
        }

        return $result;
    }

    /**
     * Validate "LIST" permission
     *
     * Validating the list permission and enriching with default values if not fully
     * provided
     *
     * @param array $settings
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _validate_list_permission($settings)
    {
        if (!isset($settings['effect'])
            || !in_array($settings['effect'], [ 'allow', 'deny' ], true)
        ) {
            throw new InvalidArgumentException(
                'The required effect property is missing or invalid'
            );
        }

        // By default the "on" will contain all areas
        if (!isset($settings['on']) || !is_array($settings['on'])) {
            $settings['on'] = [ 'frontend', 'backend', 'api' ];
        }

        return $settings;
    }

    /**
     * Validate effect
     *
     * @param array $settings
     *
     * @return void
     *
     * @access private
     * @version 7.0.0
     */
    private function _validate_effect($settings)
    {
        if (!isset($settings['effect'])
            || !in_array($settings['effect'], [ 'allow', 'deny' ], true)
        ) {
            throw new InvalidArgumentException(
                'The required effect property is missing or invalid'
            );
        }
    }

}