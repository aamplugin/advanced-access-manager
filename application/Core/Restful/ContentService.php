<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for the Posts & Terms (aka Content) service
 *
 * @package AAM
 * @version 6.9.29
 */
class AAM_Core_Restful_ContentService
{

    use AAM_Core_Restful_ServiceTrait;

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 6.9.29
     */
    protected function __construct()
    {
        add_filter(
            'aam_content_option_to_permission_filter',
            array($this, 'convert_option_to_permission'),
            10,
            3
        );

        add_filter(
            'aam_content_permission_to_option_filter',
            array($this, 'convert_permission_to_option'),
            10,
            2
        );

        add_action('rest_api_init', function() {
            // Get list of all registered post types
            $this->_register_route('/content/types', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_post_types'),
                'permission_callback' => array($this, 'check_permissions')
            ));

            // Get list of all registered taxonomies
            $this->_register_route('/content/taxonomies', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_taxonomies'),
                'permission_callback' => array($this, 'check_permissions')
            ));

            // Get list of posts for given post type
            $this->_register_route('/content/posts', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_posts'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'type' => array(
                        'description' => __('Unique post type identifier', AAM_KEY),
                        'type'        => 'string',
                        'required'    => true
                    ),
                    'search' => array(
                        'description' => __('Search string', AAM_KEY),
                        'type'        => 'string'
                    ),
                    'page' => array(
                        'description' => __('Pagination page number', AAM_KEY),
                        'type'        => 'number',
                        'default'     => 0
                    ),
                    'offset' => array(
                        'description' => __('Pagination offset', AAM_KEY),
                        'type'        => 'number',
                        'default'     => 0
                    ),
                    'per_page' => array(
                        'description' => __('Pagination limit per page', AAM_KEY),
                        'type'        => 'number',
                        'default'     => 10
                    )
                )
            ));

            // Get list of terms for given taxonomy
            $this->_register_route('/content/terms', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_terms'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'taxonomy' => array(
                        'description' => __('Unique taxonomy identifier', AAM_KEY),
                        'type'        => 'string',
                        'required'    => true
                    ),
                    'scope' => array(
                        'description' => __('Permissions scope', AAM_KEY),
                        'type'        => 'string',
                        'enum'        => [ 'term', 'post' ]
                    ),
                    'search' => array(
                        'description' => __('Search string', AAM_KEY),
                        'type'        => 'string'
                    ),
                    'page' => array(
                        'description' => __('Pagination page number', AAM_KEY),
                        'type'        => 'number',
                        'default'     => 0
                    ),
                    'offset' => array(
                        'description' => __('Pagination offset', AAM_KEY),
                        'type'        => 'number',
                        'default'     => 0
                    ),
                    'per_page' => array(
                        'description' => __('Pagination limit per page', AAM_KEY),
                        'type'        => 'number',
                        'default'     => 10
                    )
                )
            ));

            // Update permissions
            $this->_register_route('/content/post/(?<id>[\d]+)', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_permissions'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => __('Unique post identifier', AAM_KEY),
                        'type'        => 'number',
                        'required'    => true
                    ),
                    'permissions' => array(
                        'description' => __('Collection of permissions', AAM_KEY),
                        'type'        => 'array',
                        'required'    => true,
                        'items'       => array(
                            'type' => 'object',
                            'properties' => array(
                                'permission' => array(
                                    'type'     => 'string',
                                    'required' => true
                                ),
                                'effect'     => array(
                                    'type'     => 'string',
                                    'required' => true,
                                    'enum'     => array('allow', 'deny')
                                )
                            )
                        )
                    )
                )
            ));

            // Delete all permissions
            $this->_register_route('/content/post/(?<id>[\d]+)', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_permissions'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => __('Unique post identifier', AAM_KEY),
                        'type'        => 'number',
                        'required'    => true
                    )
                )
            ));
        });
    }

    /**
     * Get the list of all registered post type
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.29
     */
    public function get_post_types($request)
    {
        $subject = $this->_determine_subject($request);

        // Preparing the list of
        if (AAM::api()->getConfig('core.service.content.manageAllPostTypes', false)) {
            $args = [];
        } else {
            $args = [
                'public'            => true,
                'show_ui'           => true,
                'show_in_menu'      => true,
                'show_in_rest'      => true,
                'show_in_nav_menus' => true,
                'show_in_admin_bar' => true
            ];
        }

        // Convert the list to models
        $raw_list = get_post_types($args, 'objects', 'or');

        $response = [
            'list'  => [],
            'stats' => [
                'total_count'    => count($raw_list),
                'filtered_count' => count($raw_list)
            ]
        ];

        foreach($raw_list as $type) {
            $item = [
                'title'           => $type->label,
                'slug'            => $type->name,
                'icon'            => $type->menu_icon,
                'is_hierarchical' => $type->hierarchical
            ];

            array_push($response['list'], apply_filters(
                'aam_rest_get_post_type_filter', $item, $type, $subject, $request
            ));
        }

        return rest_ensure_response($response);
    }

    /**
     * Get list of taxonomies
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.29
     */
    public function get_taxonomies($request)
    {
        $subject = $this->_determine_subject($request);

        // Preparing the list of
        if (AAM::api()->getConfig('core.service.content.manageAllTaxonomies', false)) {
            $args = [];
        } else {
            $args = [
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
        $raw_list = get_taxonomies($args, 'objects', 'or');
        $response = [
            'list'  => [],
            'stats' => [
                'total_count'    => count($raw_list),
                'filtered_count' => count($raw_list)
            ]
        ];

        foreach($raw_list as $taxonomy) {
            $item = [
                'title'           => $taxonomy->label,
                'slug'            => $taxonomy->name,
                'is_hierarchical' => $taxonomy->hierarchical,
                'post_types'      => array_values($taxonomy->object_type)
            ];

            array_push($response['list'], apply_filters(
                'aam_rest_get_taxonomy_filter', $item, $taxonomy, $subject, $request
            ));
        }

        return rest_ensure_response($response);
    }

    /**
     * Get list of posts
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.29
     */
    public function get_posts($request)
    {
        $subject = $this->_determine_subject($request);
        $type    = $request->get_param('type');
        $search  = $request->get_param('search');
        $posts   = get_posts([
            'numberposts'      => $request->get_param('per_page'),
            'post_type'        => $type,
            'suppress_filters' => false,
            's'                => $search,
            'offset'           => $request->get_param('offset')
        ]);

        // Prep some stats
        $total_count = $this->get_post_count($type);

        if ($search) {
            $filtered_count = $this->get_post_count($type, $search);
        } else {
            $filtered_count = $total_count;
        }

        $response = [
            'list'  => [],
            'stats' => [
                'total_count'    => $total_count,
                'filtered_count' => $filtered_count
            ]
        ];

        foreach($posts as $post) {
            array_push(
                $response['list'], $this->_prepare_post($post, $subject, $request)
            );
        }

        return rest_ensure_response($response);
    }

    /**
     * Prepare post model
     *
     * @param WP_Post          $post
     * @param AAM_Core_Subject $subject
     * @param WP_REST_Request  $request
     *
     * @return array
     *
     * @access private
     * @version 6.9.29
     */
    private function _prepare_post($post, $subject, $request)
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
        $object = $subject->getObject('post', $post->ID);

        if (is_object($object)) {
            $item['permissions'] = $this->_convert_to_permissions(
                $object->getOption()
            );
            $item['inheritance'] = $object->getInheritance();
        }

        return apply_filters(
            'aam_rest_get_post_filter', $item, $post, $subject, $request
        );
    }

    /**
     * Get list of terms
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.29
     */
    public function get_terms($request)
    {
        $subject  = $this->_determine_subject($request);
        $taxonomy = $request->get_param('taxonomy');
        $search   = $request->get_param('search');
        $terms    = get_terms([
            'number'           => $request->get_param('per_page'),
            'taxonomy'         => $taxonomy,
            'suppress_filters' => false,
            'hide_empty'       => false,
            'search'           => $search,
            'offset'           => $request->get_param('offset')
        ]);

        // Prep some stats
        $total_count = get_terms([
            'fields'     => 'count',
            'hide_empty' => false,
            'taxonomy'   => $taxonomy
        ]);

        if ($search) {
            $filtered_count = get_terms([
                'fields'     => 'count',
                'hide_empty' => false,
                'search'     => $search,
                'taxonomy'   => $taxonomy
            ]);
        } else {
            $filtered_count = $total_count;
        }

        $response = [
            'list'  => [],
            'stats' => [
                'total_count'    => $total_count,
                'filtered_count' => $filtered_count
            ]
        ];

        // Get post type to add additional information about post
        $taxonomy = get_taxonomy($taxonomy);

        foreach($terms as $term) {
            $item = [
                'title'           => $term->name,
                'id'              => $term->term_id,
                'taxonomy'        => $term->taxonomy,
                'is_hierarchical' => $taxonomy->hierarchical
            ];

            array_push($response['list'], apply_filters(
                'aam_rest_get_term_filter', $item, $term, $subject, $request
            ));
        }

        return rest_ensure_response($response);
    }

    /**
     * Update post permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.29
     */
    public function update_permissions($request)
    {
        // Get list of all permissions and convert them back to AAM internal
        // content settings
        $options = [];

        foreach($request->get_param('permissions') as $permission) {
            $converted = apply_filters(
                'aam_content_permission_to_option_filter',
                null,
                $permission
            );

            if (!is_null($converted)) {
                $options = array_merge($options, $converted);
            }
        }

        $subject = $this->_determine_subject($request);
        $post    = $subject->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, $request->get_param('id')
        );

        // Set new permissions
        if (!empty($options)) {
            $post->setExplicitOption($options)->save();
        }

        return rest_ensure_response($this->_prepare_post(
            $post, $subject, $request
        ));
    }

    /**
     * Delete post permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.29
     */
    public function delete_permissions($request)
    {
        $subject = $this->_determine_subject($request);
        $post    = $subject->getObject(
            AAM_Core_Object_Post::OBJECT_TYPE, $request->get_param('id')
        );
        $post->reset();

        return rest_ensure_response($this->_prepare_post(
            $post, $subject, $request
        ));
    }

    /**
     * Convert AAM internal options into models
     *
     * @param mixed      $response
     * @param string     $action
     * @param array|null $data
     *
     * @return array|null
     *
     * @access public
     * @version 6.9.29
     */
    public function convert_option_to_permission($response, $action, $data)
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
        }

        return $response;
    }

    /**
     * Convert models into AAM internal settings
     *
     * @param mixed $response
     * @param array $data
     *
     * @return array
     *
     * @access public
     * @version 6.9.29
     */
    public function convert_permission_to_option($response, $data)
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
        }

        return $response;
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
     * @access protected
     * @global WPDB $wpdb
     * @version 6.9.29
     */
    protected function get_post_count($type, $search = null)
    {
        global $wpdb;

        $query  = "SELECT COUNT(*) AS total FROM {$wpdb->posts} ";
        $query .= 'WHERE (post_type = %s)';

        if (!empty($search)) {
            $query .= ' AND (post_title LIKE %s || ';
            $query .= "post_excerpt LIKE %s || post_content LIKE %s)";
            $args   = array($type, "%{$search}%", "%{$search}%", "%{$search}%");
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
     * Convert "read" permissions to internal AAM settings
     *
     * @param array $data
     *
     * @return array
     *
     * @access private
     * @version 6.9.29
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
            $response = [
                'restricted' => $this->_convert_permission_effect_to_bool($data)
            ];
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
     * @version 6.9.29
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
     * @version 6.9.29
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
     * Convert AAM internal options to array of permissions
     *
     * @param array $options
     *
     * @return array
     *
     * @access private
     * @version 6.9.29
     */
    private function _convert_to_permissions($options)
    {
        $response = [];

        if (is_array($options)) {
            foreach($options as $action => $data) {
                $converted = apply_filters(
                    'aam_content_option_to_permission_filter', null, $action, $data
                );

                if ($converted !== null) {
                    array_push($response, $converted);
                }
            }
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
     * @version 6.9.29
     */
    private function _convert_simple_action($type, $data)
    {
        return [
            'permission' => $type,
            'effect'     => $this->_convert_to_permission_effect($data)
        ];
    }

    /**
     * Convert "HIDDEN" action to list permission
     *
     * @param array $data
     *
     * @return array
     *
     * @access private
     * @version 6.9.29
     */
    private function _convert_list_action($data)
    {
        $response = [
            'permission' => 'list'
        ];

        if (is_bool($data)) {
            $effect = $this->_convert_to_permission_effect($data);
            $response['areas'] = [
                'frontend' => $effect,
                'backend'  => $effect,
                'api'      => $effect
            ];
        } elseif (is_array($data)) {
            $response['areas'] = [
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
     * Convert "list" permission to "HIDDEN" internal AAM access control
     *
     * @param array $data
     *
     * @return array
     *
     * @access private
     * @version 6.9.29
     */
    private function _convert_list_permission($data)
    {
        $effect   = $this->_convert_permission_effect_to_bool($data);
        $areas    = isset($data['areas']) && is_array($data['areas']) ? $data['areas'] : [];
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
     * Convert "Expires After" internal AAM access control to permission model
     *
     * @param array $data
     *
     * @return array
     *
     * @access private
     * @version 6.9.29
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
     * @version 6.9.29
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
     * @version 6.9.29
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
     * @version 6.9.29
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
     * Convert "Redirect" permission model to internal AAM control action
     *
     * @param array $data
     *
     * @return array
     *
     * @access private
     * @version 6.9.29
     */
    private function _convert_redirect_permission_to_option($data)
    {
        $props = [ 'type' => $data['redirect_type'] ];

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
            'redirect' => array_merge([
                'enabled' => $this->_convert_permission_effect_to_bool($data)
            ], $props)
        ];
    }

    /**
     * Convert "Password Protected" access control to permission model
     *
     * @param array $data
     *
     * @return array
     *
     * @access private
     * @version 6.9.29
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
     * @version 6.9.29
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
     * Check permissions
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.29
     */
    public function check_permissions()
    {
        return current_user_can('aam_manager')
            && current_user_can('aam_manage_content');
    }

}