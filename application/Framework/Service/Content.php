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
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Service_Content
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Get list of registered post types
     *
     * @param array  $args
     * @param string $result_type
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_post_types(array $args = [], $result_type = 'list')
    {
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
                        $this->get_post_type($post_type)
                    );
                }
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get a single post type resource
     *
     * @param string $post_type
     *
     * @return AAM_Framework_Resource_PostType|WP_Error|null
     *
     * @access public
     * @version 7.0.0
     */
    public function get_post_type($post_type)
    {
        try {
            if (!is_string($post_type)) {
                throw new InvalidArgumentException(
                    "The post_type argument has to be a valid string"
                );
            }

            $post_type_instance = get_post_type_object($post_type);

            if (!is_a($post_type_instance, WP_Post_Type::class)) {
                throw new OutOfBoundsException(
                    sprintf('The post_type %s does not exist', $post_type)
                );
            }

            $result = $this->_get_access_level()->get_resource(
                AAM_Framework_Type_Resource::POST_TYPE, $post_type
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get a single post type resource
     *
     * @param string $post_type
     *
     * @return AAM_Framework_Resource_PostType|WP_Error|null
     *
     * @access public
     * @version 7.0.0
     */
    public function post_type($post_type)
    {
        return $this->get_post_type($post_type);
    }

    /**
     * Get list of taxonomies
     *
     * @param array $args
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_taxonomies(array $args = [], $result_type = 'list')
    {
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
                        $result, $this->get_taxonomy($taxonomy)
                    );
                }
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get a single taxonomy resource
     *
     * @param string $taxonomy
     *
     * @return AAM_Framework_Resource_Taxonomy|WP_Error|null
     *
     * @access public
     * @version 7.0.0
     */
    public function get_taxonomy($taxonomy)
    {
        try {
            if (!is_string($taxonomy)) {
                throw new InvalidArgumentException(
                    "The taxonomy argument has to be a valid string"
                );
            }

            $result = $this->_get_access_level()->get_resource(
                AAM_Framework_Type_Resource::TAXONOMY, $taxonomy
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get a single taxonomy resource
     *
     * @param string $taxonomy
     *
     * @return AAM_Framework_Resource_Taxonomy|WP_Error|null
     *
     * @access public
     * @version 7.0.0
     */
    public function taxonomy($taxonomy)
    {
        return $this->get_taxonomy($taxonomy);
    }

    /**
     * Get list of posts
     *
     * @param array  $args
     * @param string $result_type
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_posts(array $args = [],  $result_type = 'list')
    {
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
                        $result, $this->get_post($id)
                    );
                }
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get list of terms
     *
     * @param array  $args
     * @param string $result_type
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_terms(array $args = [], $result_type = 'list')
    {
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
                    // Prepare compound term_id
                    $id = [
                        'id'       => $term_id,
                        'taxonomy' => $args['taxonomy']
                    ];

                    if (isset($args['post_type'])) {
                        $id['post_type'] = $args['post_type'];
                    }

                    array_push($result, $this->get_term($id));
                }
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get a single term resource
     *
     * @param int|array $term_identifier
     *
     * @return AAM_Framework_Resource_Term|WP_Error|null
     *
     * @access public
     * @version 7.0.0
     */
    public function get_term($term_identifier)
    {
        try {
            if (is_array($term_identifier)) {
                if (!isset($term_identifier['id'])
                    || !is_numeric($term_identifier['id'])
                ) {
                    throw new InvalidArgumentException(
                        "The term_id has to have a valid numeric id"
                    );
                }

                if (!isset($term_identifier['taxonomy'])
                    || !is_string($term_identifier['taxonomy'])
                ) {
                    throw new InvalidArgumentException(
                        "The term_id has to have a valid string taxonomy"
                    );
                }
            } elseif (!is_numeric($term_identifier)) {
                throw new InvalidArgumentException(
                    "The term_id argument has to be a valid numeric value"
                );
            }

            $result = $this->_get_access_level()->get_resource(
                AAM_Framework_Type_Resource::TERM, $term_identifier
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get a single term resource
     *
     * @param int|array $term_identifier
     *
     * @return AAM_Framework_Resource_Term|WP_Error|null
     *
     * @access public
     * @version 7.0.0
     */
    public function term($term_identifier)
    {
        return $this->get_term($term_identifier);
    }

    /**
     * Get a post
     *
     * @param int|string $post_identifier
     * @param string     $post_type
     *
     * @return AAM_Framework_Resource_Post|WP_Error|null
     *
     * @access public
     * @version 7.0.0
     */
    public function get_post($post_identifier, $post_type = '')
    {
        try {
            // Determining if we are dealing with post ID or post slug
            if (is_numeric($post_identifier) || is_int($post_identifier)) {
                // Fetching post by ID
                $post = get_post(intval(intval($post_identifier)));
            } elseif (!is_string($post_type) || empty($post_type)) {
                throw new InvalidArgumentException(
                    'The post_type has to be a string value'
                );
            } else {
                $post = get_page_by_path($post_identifier, OBJECT, $post_type);
            }

            if (!is_a($post, 'WP_Post')) {
                throw new OutOfRangeException(
                    "Post '{$post_identifier}' does not exist"
                );
            }

            $result = $this->_get_access_level()->get_resource(
                AAM_Framework_Type_Resource::POST, $post->ID
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alias for the get_post method
     *
     * @param int|string $post_identifier
     * @param string     $post_type
     *
     * @return AAM_Framework_Resource_Post
     *
     * @access public
     * @version 7.0.0
     */
    public function post($post_id, $post_type = '')
    {
        return $this->get_post($post_id, $post_type);
    }

    /**
     * Check if post or term is hidden
     *
     * @param mixed $resource
     *
     * @return boolean|WP_Error
     *
     * @access public
     * @version 7.0.0
     */
    public function is_hidden($resource)
    {
        try {
            $resource  = $this->_get_resource($resource);
            $is_hidden = $resource->is_hidden();
            $result    = is_bool($is_hidden) ? $is_hidden : false;
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if post or term is restricted
     *
     * @param mixed $resource
     *
     * @return bool|WP_Error
     *
     * @access public
     * @version 7.0.0
     */
    public function is_restricted($resource)
    {
        try {
            $resource      = $this->_get_resource($resource);
            $is_restricted = $resource->is_restricted();
            $result        = is_bool($is_restricted) ? $is_restricted : false;
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if current access level has permission for give post or term
     *
     * @param mixed  $resource
     * @param string $permission
     *
     * @return bool|WP_Error
     * @access public
     * @version 7.0.0
     */
    public function is_allowed_to($resource, $permission)
    {
        try {
            $resource   = $this->_get_resource($resource);
            $is_allowed = $resource->is_allowed_to($permission);
            $result     = is_bool($is_allowed) ? $is_allowed : true;
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if current access level does not have permission for give post or term
     *
     * @param mixed  $resource
     * @param string $permission
     *
     * @return boolean
     * @access public
     * @version 7.0.0
     */
    public function is_denied_to($resource, $permission)
    {
        try {
            $resource  = $this->_get_resource($resource);
            $is_denied = $resource->is_denied_to($permission);
            $result    = is_bool($is_denied) ? $is_denied : false;
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get proper resource based on input argument
     *
     * @param mixed $input
     *
     * @return AAM_Framework_Resource_Post|AAM_Framework_Resource_Term
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_resource($input)
    {
        if (is_a($input, AAM_Framework_Resource_PermissionInterface::class)) {
            $resource = $input;
        } elseif (is_a($input, WP_Post::class)) {
            $resource = $this->post($input->ID);
        } elseif (is_a($input, WP_Term::class)) {
            $resource = $this->term([
                'id'       => $input->term_id,
                'taxonomy' => $input->taxonomy
            ]);
        } elseif (is_array($input) && isset($input['resource_type'])) {
            if ($input['resource_type'] === AAM_Framework_Type_Resource::POST) {
                if (!empty($input['slug'])) {
                    $resource = $this->post($input['slug'], $input['post_type']);
                } else {
                    $resource = $this->post($input['id']);
                }
            } elseif ($input['resource_type'] === AAM_Framework_Type_Resource::TERM) {
                $resource = $this->term($input);
            }
        }

        if (empty($resource)) {
            throw new InvalidArgumentException(
                'The input arguments cannot be used to obtain valid resource'
            );
        }

        return $resource;
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
     * @version 7.0.0
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

}