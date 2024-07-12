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
     * Get list of registered post type
     *
     * @param array $args
     * @param array $inline_context
     *
     * @return array
     *
     * @access public
     * @version 6.9.31
     */
    public function get_post_types(array $args = [], $inline_context = null)
    {
        try {
            $args = array_merge([
                'include_all' => false,  // Only return manageable post types
                'result_type' => 'full', // Return both list and summary,
                'scope'       => 'all'   // Permission scopes to return all
            ], $args);

            // Preparing the list of
            if (!empty($args['include_all'])) {
                $filters = [];
            } else {
                $filters = [
                    'public'            => true,
                    'show_ui'           => true,
                    'show_in_menu'      => true,
                    'show_in_rest'      => true,
                    'show_in_nav_menus' => true,
                    'show_in_admin_bar' => true
                ];
            }

            // Convert the list to models
            $raw_list = get_post_types($filters, 'objects', 'or');
            $subject  = $this->_get_subject($inline_context);

            $result = [
                'list'    => [],
                'summary' => [
                    'total_count'    => count($raw_list),
                    'filtered_count' => count($raw_list)
                ]
            ];

            foreach($raw_list as $post_type) {
                if ($this->_extended_method_exists('get_post_type')) {
                    $item = $this->get_post_type(
                        $post_type, $args['scope'], $inline_context
                    );
                } else {
                    $item = $this->_prepare_post_type_item($post_type);
                }

                array_push($result['list'], apply_filters(
                    'get_post_type_filter',
                    $item,
                    $post_type,
                    $subject
                ));
            }

            // Determine what to return
            if ($args['result_type'] === 'list') {
                $result = $result['list'];
            } elseif ($args['result_type'] === 'summary') {
                $result = $result['summary'];
            }
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
    public function get_taxonomies(array $args = [], $inline_context = null)
    {
        try {
            $args = array_merge([
                'include_all' => false,  // Only return manageable taxonomies
                'result_type' => 'full', // Return both list and summary
                'scope'       => 'all'   // Permission scopes to return all
            ], $args);

            // Preparing the list of
            if (!empty($args['include_all'])) {
                $filters = [];
            } else {
                $filters = [
                    'public'             => true,
                    'show_ui'            => true,
                    'show_in_rest'       => true,
                    'show_in_menu'       => true,
                    'show_in_quick_edit' => true,
                    'show_in_nav_menus'  => true,
                    'show_in_admin_bar'  => true
                ];
            }

            // Convert the list to models
            $raw_list = get_taxonomies($filters, 'objects', 'or');
            $subject  = $this->_get_subject($inline_context);
            $result   = [
                'list'    => [],
                'summary' => [
                    'total_count'    => count($raw_list),
                    'filtered_count' => count($raw_list)
                ]
            ];

            foreach($raw_list as $taxonomy) {
                if ($this->_extended_method_exists('get_taxonomy')) {
                    $item = $this->get_taxonomy(
                        $taxonomy, $args['scope'], $inline_context
                    );
                } else {
                    $item = $this->_prepare_taxonomy_item($taxonomy);
                }

                array_push(
                    $result['list'],
                    apply_filters('get_taxonomy_filter', $item, $taxonomy, $subject)
                );
            }

            // Determine what to return
            if ($args['result_type'] === 'list') {
                $result = $result['list'];
            } elseif ($args['result_type'] === 'summary') {
                $result = $result['summary'];
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get list of posts
     *
     * @param array $args
     * @param array $inline_context
     *
     * @return array
     *
     * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/399
     * @since 6.9.31 Initial implementation of the method
     *
     * @access public
     * @version 6.9.35
     */
    public function get_posts(array $args = [], $inline_context = null)
    {
        try {
            $subject = $this->_get_subject($inline_context);
            $args    = array_merge([
                'numberposts'      => 10,   // By default, only top 10
                'result_type'      => 'full', // Return both list and summary
                'suppress_filters' => true,
                'post_status'      => 'any',
                'search_columns'   => ['post_title']
            ], $args);

            // The minimum required attribute is post_type. If it is not defined,
            // throw an error
            if (empty($args['post_type']) || !is_string($args['post_type'])) {
                throw new InvalidArgumentException(
                    'The post_type has to be a valid string'
                );
            }

            // If result type is either "full" or "summary", gather additional info
            if (in_array($args['result_type'], ['full', 'summary'], true)) {
                // Prep some stats
                $total_count = $this->_get_post_count($args['post_type']);

                if ($args['s']) {
                    $filtered_count = $this->_get_post_count(
                        $args['post_type'], $args['s']
                    );
                } else {
                    $filtered_count = $total_count;
                }
            }

            if ($args['result_type'] !== 'summary') {
                $list = [];

                foreach(get_posts($args) as $post) {
                    array_push($list, $this->_prepare_post_item($post, $subject));
                }
            }

            // Determine what to return
            if ($args['result_type'] === 'list') {
                $result = $list;
            } elseif ($args['result_type'] === 'summary') {
                $result = [
                    'total_count'    => $total_count,
                    'filtered_count' => $filtered_count
                ];
            } else {
                $result = [
                    'list'    => $list,
                    'summary' => [
                        'total_count'    => $total_count,
                        'filtered_count' => $filtered_count
                    ]
                ];
            }
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

            $result = $this->_prepare_post_item(
                get_post($post_id),
                $this->_get_subject($inline_context)
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get list of terms
     *
     * @param array $args
     * @param array $inline_context
     *
     * @return array
     *
     * @access public
     * @version 6.9.31
     */
    public function get_terms(array $args = [], $inline_context = null)
    {
        try {
            $subject = $this->_get_subject($inline_context);
            $args    = array_merge([
                'number'           => 10,     // By default, only top 10
                'result_type'      => 'full', // Return both list and summary
                'suppress_filters' => true,
                'hide_empty'       => false,
                'scope'            => 'all'
            ], $args);

            // The minimum required attribute is taxonomy. If it is not defined,
            // throw an error
            if (empty($args['taxonomy']) || !is_string($args['taxonomy'])) {
                throw new InvalidArgumentException(
                    'The taxonomy has to be a valid string'
                );
            }

            // If result type is either "full" or "summary", gather additional info
            if (in_array($args['result_type'], ['full', 'summary'], true)) {
                $total_count = get_terms([
                    'fields'     => 'count',
                    'hide_empty' => false,
                    'taxonomy'   => $args['taxonomy']
                ]);

                if ($args['search']) {
                    $filtered_count = get_terms([
                        'fields'     => 'count',
                        'hide_empty' => false,
                        'search'     => $args['search'],
                        'taxonomy'   => $args['taxonomy']
                    ]);
                } else {
                    $filtered_count = $total_count;
                }
            }

            if ($args['result_type'] !== 'summary') {
                $list = [];

                foreach(get_terms($args) as $term) {
                    if ($this->_extended_method_exists('get_term')) {
                        $item = $this->get_term($term, $args, $inline_context);
                    } else {
                        $item = $this->_prepare_term_item($term);
                    }

                    array_push($list, apply_filters(
                        'get_term_filter',
                        $item,
                        $term,
                        $subject
                    ));
                }
            }

            // Determine what to return
            if ($args['result_type'] === 'list') {
                $result = $list;
            } elseif ($args['result_type'] === 'summary') {
                $result = [
                    'total_count'    => $total_count,
                    'filtered_count' => $filtered_count
                ];
            } else {
                $result = [
                    'list'    => $list,
                    'summary' => [
                        'total_count'    => $total_count,
                        'filtered_count' => $filtered_count
                    ]
                ];
            }
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
            $subject = $this->_get_subject($inline_context);

            // Get list of all permissions and convert them back to AAM internal
            // content settings
            $options = [];

            foreach($permissions as $permission) {
                $converted = $this->_convert_permission_to_option($permission);

                if (!is_null($converted)) {
                    $options = array_merge($options, $converted);
                }
            }

            $post = $subject->getObject(AAM_Core_Object_Post::OBJECT_TYPE, $post_id);

            // Set new permissions
            if (!empty($options)) {
                $result = $post->setExplicitOption($options)->save();
            } else {
                $result = $this->delete_post_permissions($post_id, $inline_context);
            }
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
            $subject = $this->_get_subject($inline_context);
            $post    = $subject->getObject(
                AAM_Core_Object_Post::OBJECT_TYPE, $post_id
            );

            // Reset post permissions
            $post->reset();

            $result = $this->get_post($post_id, $inline_context);
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
     * Prepare post model
     *
     * @param WP_Post          $post
     * @param AAM_Core_Subject $subject
     *
     * @return array
     *
     * @access private
     * @version 6.9.29
     */
    private function _prepare_post_item($post, $subject)
    {
        // Get post type to add additional information about post
        $post_type = get_post_type_object($post->post_type);

        $item = [
            'id'              => $post->ID,
            'icon'            => $post_type->menu_icon,
            'is_hierarchical' => $post_type->hierarchical
        ];

        if ($post->post_type === 'nav_menu_item') {
            $item['title'] = wp_setup_nav_menu_item($post)->title;
        } else {
            $item['title'] = $post->post_title;
        }

        // Get permissions
        $object = $subject->reloadObject('post', $post->ID);

        if (is_object($object)) {
            $item = array_merge($item, [
                'permissions'  => $this->_convert_to_permissions($object->getOption()),
                'is_inherited' => !$object->isOverwritten()
            ]);
        }

        return apply_filters('aam_get_post_filter', $item, $post, $subject);
    }

    /**
     * Prepare post type
     *
     * @param string|WP_Post_Type $post_type
     *
     * @return array
     *
     * @access private
     * @version 6.9.31
     */
    private function _prepare_post_type_item($post_type)
    {
        if (is_string($post_type)) {
            $post_type_obj = get_post_type_object($post_type);
        } else {
            $post_type_obj = $post_type;
        }

        if (!is_a($post_type_obj, 'WP_Post_Type')) {
            throw new OutOfRangeException("The post type {$post_type} is not valid");
        }

        return [
            'title'           => $post_type_obj->label,
            'slug'            => $post_type_obj->name,
            'icon'            => $post_type_obj->menu_icon,
            'is_hierarchical' => $post_type_obj->hierarchical
        ];
    }

    /**
     * Prepare taxonomy
     *
     * @param string|WP_Taxonomy $taxonomy
     *
     * @return array
     *
     * @access private
     * @version 6.9.31
     */
    private function _prepare_taxonomy_item($taxonomy)
    {
        if (is_string($taxonomy)) {
            $taxonomy_obj = get_taxonomy($taxonomy);
        } else {
            $taxonomy_obj = $taxonomy;
        }

        if (!is_a($taxonomy_obj, 'WP_Taxonomy')) {
            throw new OutOfRangeException("The taxonomy {$taxonomy} is not valid");
        }

        return [
            'title'           => $taxonomy_obj->label,
            'slug'            => $taxonomy_obj->name,
            'is_hierarchical' => $taxonomy_obj->hierarchical,
            'post_types'      => array_values($taxonomy_obj->object_type)
        ];
    }

    /**
     * Prepare term
     *
     * @param int|WP_Term $term
     *
     * @return array
     *
     * @access private
     * @version 6.9.31
     */
    private function _prepare_term_item($term)
    {
        if (is_numeric($term)) {
            $term_obj = get_term($term);
        } else {
            $term_obj = $term;
        }

        if (!is_a($term_obj, 'WP_Term')) {
            throw new OutOfRangeException("The term {$term} is not valid");
        }

        // Get post type to add additional information about post
        $taxonomy = get_taxonomy($term_obj->taxonomy);

        return [
            'title'           => $term_obj->name,
            'id'              => $term_obj->term_id,
            'taxonomy'        => $term_obj->taxonomy,
            'is_hierarchical' => $taxonomy->hierarchical
        ];
    }

    /**
     * Convert AAM internal options to array of permissions
     *
     * @param array  $options
     * @param string $scope
     *
     * @return array
     *
     * @access private
     * @version 6.9.29
     * @todo Weak link
     */
    private function _convert_to_permissions($options, $scope = null)
    {
        $response = [];

        if (is_array($options)) {
            foreach($options as $action => $data) {
                $converted = $this->_convert_option_to_permission($action, $data);

                if ($converted !== null) {
                    if (!is_null($scope)) {
                        $converted['scope'] = $scope;
                    }

                    array_push($response, $converted);
                }
            }
        }

        return $response;
    }

    /**
     * Convert AAM internal options into models
     *
     * @param string     $action
     * @param array|null $data
     *
     * @return array|null
     *
     * @access private
     * @version 6.9.31
     */
    private function _convert_option_to_permission($action, $data)
    {
        if ($action === 'restricted') {
            $response = $this->_convert_simple_action('read', $data);
        } elseif ($action === 'hidden') {
            $response = $this->_convert_list_action($data);
        } elseif ($action === 'comment') {
            $response = $this->_convert_simple_action('comment', $data);
        } elseif ($action === 'ceased') {
            $response = $this->_convert_ceased_action($data);
        } elseif ($action === 'limited') {
            $response = $this->_convert_limited_action($data);
        } elseif ($action === 'teaser') {
            $response = $this->_convert_teaser_action($data);
        } elseif ($action === 'redirected') {
            $response = $this->_convert_redirect_action($data);
        } elseif ($action === 'protected') {
            $response = $this->_convert_protected_action($data);
        } elseif ($action === 'edit') {
            $response = $this->_convert_simple_action('edit', $data);
        } elseif ($action === 'delete') {
            $response = $this->_convert_simple_action('delete', $data);
        } elseif ($action === 'publish') {
            $response = $this->_convert_simple_action('publish', $data);
        } else {
            $response = apply_filters(
                'aam_content_option_to_permission_filter', null, $action, $data
            );
        }

        return $response;
    }

    /**
     * Convert "HIDDEN" action to list permission
     *
     * @param array $data
     *
     * @return array
     *
     * @access private
     * @version 6.9.31
     */
    private function _convert_list_action($data)
    {
        $response = [
            'permission' => 'list'
        ];

        if (is_bool($data)) {
            $effect = $this->_convert_to_permission_effect($data);
            $response['on'] = [
                'frontend' => $effect,
                'backend'  => $effect,
                'api'      => $effect
            ];
        } elseif (is_array($data)) {
            $response['on'] = [
                'frontend' => $this->_convert_to_permission_effect(
                    isset($data['frontend']) ? $data['frontend'] : false
                ),
                'backend'  => $this->_convert_to_permission_effect(
                    isset($data['backend']) ? $data['backend'] : false
                ),
                'api'      => $this->_convert_to_permission_effect(
                    isset($data['api']) ? $data['api'] : false
                )
            ];
        }

        return $response;
    }

    /**
     * Convert primitive AAM internal access controls to permission model
     *
     * @param string $type
     * @param mixed  $data
     *
     * @return array
     *
     * @access private
     * @version 6.9.31
     */
    private function _convert_simple_action($type, $data)
    {
        return [
            'permission' => $type,
            'effect'     => $this->_convert_to_permission_effect($data)
        ];
    }

     /**
     * Convert "Expires After" internal AAM access control to permission model
     *
     * @param array $data
     *
     * @return array
     *
     * @access private
     * @version 6.9.31
     */
    private function _convert_ceased_action($data)
    {
        $response = [
            'permission' => 'read',
            'effect'     => $this->_convert_to_permission_effect($data)
        ];

        if (!empty($data['after'])) {
            $response['after_timestamp'] = $data['after'];
            $response['after_datetime']  = date(
                DATE_RSS, $data['after']
            );
        }

        return $response;
    }

    /**
     * Convert "LIMITED" access control to permission model
     *
     * @param array $data
     *
     * @return array
     *
     * @access private
     * @version 6.9.31
     */
    private function _convert_limited_action($data)
    {
        $response = [
            'permission' => 'read',
            'effect'     => $this->_convert_to_permission_effect($data)
        ];

        if (isset($data['threshold'])) {
            $response['read_max_count'] = intval($data['threshold']);
        }

        return $response;
    }

    /**
     * Convert "Teaser" access control to permission model
     *
     * @param array $data
     *
     * @return array
     *
     * @access private
     * @version 6.9.31
     */
    private function _convert_teaser_action($data)
    {
        $response = [
            'permission' => 'read',
            'effect'     => $this->_convert_to_permission_effect($data)
        ];

        if (!empty($data['message'])) {
            $response['teaser_message'] = $data['message'];
        }

        return $response;
    }

    /**
     * Convert "Redirect" access control to permission model
     *
     * @param array $data
     *
     * @return array
     *
     * @access private
     * @version 6.9.31
     */
    private function _convert_redirect_action($data)
    {
        $response = [
            'permission' => 'read',
            'effect'     => $this->_convert_to_permission_effect($data)
        ];

        if (!empty($data['type'])) {
            $response['redirect_type'] = $data['type'];

            if ($data['type'] === 'page') {
                $response['redirect_page_id'] = $data['destination'];
            } elseif ($data['type'] === 'url') {
                $response['redirect_url'] = $data['destination'];
            } elseif ($data['type'] === 'callback') {
                $response['redirect_callback'] = $data['destination'];
            }

            if ($data['type'] !== 'login' && isset($data['httpCode'])) {
                $response['redirect_http_code'] = intval(
                    $data['httpCode']
                );
            }
        }

        return $response;
    }

    /**
     * Convert "Password Protected" access control to permission model
     *
     * @param array $data
     *
     * @return array
     *
     * @access private
     * @version 6.9.31
     */
    private function _convert_protected_action($data)
    {
        $response = [
            'permission' => 'read',
            'effect'     => $this->_convert_to_permission_effect($data)
        ];

        if (!empty($data['password'])) {
            $response['password'] = $data['password'];
        }

        return $response;
    }

    /**
     * Convert AAM access control flag to permission effect
     *
     * @param mixed $setting
     *
     * @return string
     *
     * @access private
     * @version 6.9.31
     */
    private function _convert_to_permission_effect($setting)
    {
        $response = 'allow';

        if(is_bool($setting)) {
            $response = $setting ? 'deny' : 'allow';
        } elseif(is_numeric($setting)) { // Legacy
            $response = intval($setting) === 1 ? 'deny' : 'allow';
        } elseif(is_array($setting)) {
            $response = $this->_convert_to_permission_effect(
                isset($setting['enabled']) ? $setting['enabled'] : false
            );
        }

        return $response;
    }

    /**
     * Convert models into AAM internal settings
     *
     * @param array $data
     *
     * @return array
     *
     * @access public
     * @version 6.9.31
     */
    private function _convert_permission_to_option($data)
    {
        $action = isset($data['permission']) ? $data['permission'] : null;

        if ($action === 'read') {
            $response = $this->_convert_read_permission($data);
        } elseif ($action === 'list') {
            $response = $this->_convert_list_permission($data);
        } elseif ($action === 'comment') {
            $response = [
                'comment' => $this->_convert_permission_effect_to_bool($data)
            ];
        } elseif ($action === 'edit') {
            $response = [
                'edit' => $this->_convert_permission_effect_to_bool($data)
            ];
        } elseif ($action === 'delete') {
            $response = [
                'delete' => $this->_convert_permission_effect_to_bool($data)
            ];
        } elseif ($action === 'publish') {
            $response = [
                'publish' => $this->_convert_permission_effect_to_bool($data)
            ];
        } else {
            $response = apply_filters(
                'aam_content_permission_to_option_filter',
                null,
                $data
            );
        }

        return $response;
    }

    /**
     * Convert "read" permissions to internal AAM settings
     *
     * @param array $data
     *
     * @return array
     *
     * @access private
     * @version 6.9.31
     */
    private function _convert_read_permission($data)
    {
        $response = null;

        // Checking if it is "ceased"
        if (isset($data['after_timestamp']) || isset($data['after_datetime'])) {
            $response = [
                'ceased' => [
                    'enabled' => $this->_convert_permission_effect_to_bool($data),
                    'after'   => $this->_prepare_ceased_after_value($data)
                ]
            ];
        } elseif (isset($data['read_max_count'])) {
            $response = [
                'limited' => [
                    'enabled'   => $this->_convert_permission_effect_to_bool($data),
                    'threshold' => intval($data['read_max_count'])
                ]
            ];
        } elseif (isset($data['teaser_message'])) {
            $response = [
                'teaser' => [
                    'enabled' => $this->_convert_permission_effect_to_bool($data),
                    'message' => trim($data['teaser_message'])
                ]
            ];
        } elseif (isset($data['redirect_type'])) {
            $response = $this->_convert_redirect_permission_to_option($data);
        } elseif (isset($data['password'])) {
            $response = [
                'protected' => [
                    'enabled' => $this->_convert_permission_effect_to_bool($data),
                    'password' => trim($data['password'])
                ]
            ];
        } else {
            $response = apply_filters('aam_content_permission_to_option_filter', [
                'restricted' => $this->_convert_permission_effect_to_bool($data)
            ], $data);
        }

        return $response;
    }

    /**
     * Convert "list" permission to "HIDDEN" internal AAM access control
     *
     * @param array $data
     *
     * @return array
     *
     * @access private
     * @version 6.9.31
     */
    private function _convert_list_permission($data)
    {
        $effect   = $this->_convert_permission_effect_to_bool($data);
        $areas    = isset($data['on']) && is_array($data['on']) ? $data['on'] : [];
        $response = [
            'hidden' => [
                'enabled' => true
            ]
        ];

        foreach(['frontend', 'backend', 'api'] as $area) {
            if (in_array($area, $areas, true)) {
                $response['hidden'][$area] = $effect;
            } else {
                $response['hidden'][$area] = !$effect;
            }
        }

        return $response;
    }

    /**
     * Convert permission "effect" into boolean representation
     *
     * @param string $data
     *
     * @return boolean
     *
     * @access private
     * @version 6.9.31
     */
    private function _convert_permission_effect_to_bool($data)
    {
        if (is_string($data)) {
            $effect = $data;
        } else {
            $effect = isset($data['effect']) ? strtolower($data['effect']) : 'deny';
        }

        return $effect === 'deny';
    }

    /**
     * Convert "Expires After" values into timestamp
     *
     * @param array $data
     *
     * @return int|null
     *
     * @access private
     * @version 6.9.31
     */
    private function _prepare_ceased_after_value($data)
    {
        $response = null;

        if (isset($data['after_timestamp'])) {
            $response = intval($data['after_timestamp']);
        } elseif (isset($data['after_datetime'])) {
            $response = strtotime($data['after_datetime']);
        }

        return $response;
    }

    /**
     * Convert "Redirect" permission model to internal AAM control action
     *
     * @param array $data
     *
     * @return array
     *
     * @access private
     * @version 6.9.31
     */
    private function _convert_redirect_permission_to_option($data)
    {
        $props = [
            'type' => $data['redirect_type']
        ];

        if ($props['type'] === 'page') {
            $props['destination'] = $data['redirect_page_id'];
        } elseif ($props['type'] === 'url') {
            $props['destination'] = $data['redirect_url'];
        } elseif ($props['type'] === 'callback') {
            $props['destination'] = $data['redirect_callback'];
        }

        if (isset($data['redirect_http_code'])) {
            $props['httpCode'] = intval($data['redirect_http_code']);
        }

        return [
            'redirected' => array_merge([
                'enabled' => $this->_convert_permission_effect_to_bool($data)
            ], $props)
        ];
    }

}