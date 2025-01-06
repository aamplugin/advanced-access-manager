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
 *
 * @version 7.0.0
 */
class AAM_Restful_Content
{

    use AAM_Restful_ServiceTrait;

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function __construct()
    {
        add_action('rest_api_init', function() {
            // Get list of all registered post types
            $this->_register_route('/post_types', [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_post_types' ],
                'permission_callback' => [ $this, 'check_permissions' ],
                'args'                => [
                    'return_all' => [
                        'description' => 'Include all post types or only public',
                        'type'        => 'boolean',
                        'default'     => null
                    ]
                ]
            ]);

            // Get list of all registered taxonomies
            $this->_register_route('/taxonomies', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_taxonomies'),
                'permission_callback' => array($this, 'check_permissions')
            ));

            // Get list of terms for given taxonomy
            $this->_register_route('/terms', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_terms'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'taxonomy' => array(
                        'description' => 'Scope for specific taxonomy',
                        'type'        => 'string',
                        'required'    => true
                    ),
                    'post_type' => array(
                        'description' => 'Scope for specific post type',
                        'type'        => 'string'
                    ),
                    'search' => array(
                        'description' => 'Search string',
                        'type'        => 'string'
                    ),
                    'offset' => array(
                        'description' => 'Pagination offset',
                        'type'        => 'number',
                        'default'     => 0
                    ),
                    'per_page' => array(
                        'description' => 'Pagination limit per page',
                        'type'        => 'number',
                        'default'     => 10
                    )
                )
            ));

            // Get list of posts for given post type
            $this->_register_route('/posts', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_posts'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'post_type' => array(
                        'description' => 'Unique post type identifier',
                        'type'        => 'string',
                        'required'    => true
                    ),
                    'search' => array(
                        'description' => 'Search string',
                        'type'        => 'string'
                    ),
                    'offset' => array(
                        'description' => 'Pagination offset',
                        'type'        => 'number',
                        'default'     => 0
                    ),
                    'per_page' => array(
                        'description' => 'Pagination limit per page',
                        'type'        => 'number',
                        'default'     => 10
                    )
                )
            ));

            // Get post permissions
            $this->_register_route('/post/(?P<id>[\d]+)', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_post'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => 'Unique post identifier',
                        'type'        => 'number',
                        'required'    => true
                    )
                )
            ));

            // Update post permissions
            $this->_register_route('/post/(?P<id>[\d]+)', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_post_permissions'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => 'Unique post identifier',
                        'type'        => 'number',
                        'required'    => true
                    ),
                    'permissions' => array(
                        'description' => 'Collection of permissions',
                        'type'        => 'array',
                        'required'    => true,
                        'items'       => array(
                            'type' => 'object',
                            'properties' => array(
                                'permission' => array(
                                    'type'     => 'string',
                                    'required' => true
                                ),
                                'effect' => array(
                                    'type'     => 'string',
                                    'required' => true,
                                    'default'  => 'deny',
                                    'enum'     => [ 'allow', 'deny' ]
                                )
                            )
                        )
                    )
                )
            ));

            // Update post permission
            $this->_register_route('/post/(?P<id>[\d]+)/(?P<permission>[\w]+)', [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [ $this, 'set_post_permission' ],
                'permission_callback' => [ $this, 'check_permissions' ],
                'args'                => [
                    'id' => [
                        'description' => 'Unique post identifier',
                        'type'        => 'number',
                        'required'    => true
                    ],
                    'permission' => [
                        'description' => 'Permission',
                        'type'        => 'string',
                        'required'    => true
                    ],
                    'effect' => [
                        'type'     => 'string',
                        'required' => true,
                        'default'  => 'deny',
                        'enum'     => [ 'allow', 'deny' ]
                    ]
                ]
            ]);

            // Reset all permissions
            $this->_register_route('/post/(?P<id>[\d]+)', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'reset_post_permissions'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => 'Unique post identifier',
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
     * @access public
     *
     * @version 7.0.0
     */
    public function get_post_types(WP_REST_Request $request)
    {
        try {
            $raw_list = AAM::api()->content->get_post_types(
                $this->_determine_access_level($request),
                $request->get_param('return_all')
            );

            $result = [
                'list' => [],
                'summary' => [
                    'total_count'    => count(get_post_types()),
                    'filtered_count' => 0
                ]
            ];

            foreach($raw_list as $post_type) {
                array_push(
                    $result['list'], $this->_prepare_post_type_output($post_type)
                );

                $result['summary']['filtered_count']++;
            }
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get list of taxonomies
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function get_taxonomies(WP_REST_Request $request)
    {
        try {
            $raw_list = AAM::api()->content->get_taxonomies(
                $this->_determine_access_level($request),
                $request->get_param('return_all')
            );

            $result = [
                'list' => [],
                'summary' => [
                    'total_count'    => count(get_taxonomies()),
                    'filtered_count' => 0
                ]
            ];

            foreach($raw_list as $taxonomy) {
                array_push(
                    $result['list'], $this->_prepare_taxonomy_output($taxonomy)
                );

                $result['summary']['filtered_count']++;
            }
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get list of posts
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function get_posts(WP_REST_Request $request)
    {
        try {
            $args = [
                'numberposts'      => $request->get_param('per_page'),
                'post_type'        => $request->get_param('post_type'),
                's'                => $request->get_param('search'),
                'offset'           => $request->get_param('offset')
            ];

            // Getting the list of posts
            // The minimum required attribute is post_type. If it is not defined,
            // throw an error
            if (empty($args['post_type']) || !is_string($args['post_type'])) {
                throw new InvalidArgumentException(
                    'The post_type has to be a valid string'
                );
            }

            if (empty($args['numberposts'])) {
                $args['numberposts'] = 10;
            }

            $result = [
                'list'    => [],
                'summary' => [
                    'total_count' => AAM::api()->content->get_post_count(
                        $args['post_type']
                    ),
                    'filtered_count' => AAM::api()->content->get_post_count(
                        $args['post_type'], $args['s']
                    )
                ]
            ];

            $raw_list = AAM::api()->content->get_posts(
                $args, $this->_determine_access_level($request)
            );

            foreach($raw_list as $item) {
                array_push($result['list'], $this->_prepare_post_output($item));
            }
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get post permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function get_post(WP_REST_Request $request)
    {
        try {
            $result = $this->_prepare_post_output(
                $this->_determine_access_level($request)->get_resource(
                    AAM_Framework_Type_Resource::POST,
                    $request->get_param('id')
                )
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get list of terms
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function get_terms(WP_REST_Request $request)
    {
        try {
            $args = [
                'number'     => $request->get_param('per_page'),
                'taxonomy'   => $request->get_param('taxonomy'),
                'hide_empty' => false,
                'search'     => $request->get_param('search'),
                'offset'     => $request->get_param('offset'),
                'post_type'  => $request->get_param('post_type')
            ];

            // Getting the list of terms
            // The minimum required attribute is taxonomy. If it is not defined,
            // throw an error
            if (empty($args['taxonomy']) || !is_string($args['taxonomy'])) {
                throw new InvalidArgumentException(
                    'The taxonomy has to be a valid string'
                );
            }

            if (empty($args['number'])) {
                $args['number'] = 10;
            }

            $result = [
                'list'    => [],
                'summary' => [
                    'total_count' => AAM::api()->content->get_term_count(
                        $args['taxonomy']
                    ),
                    'filtered_count' => AAM::api()->content->get_term_count(
                        $args['taxonomy'], $args
                    )
                ]
            ];

            $raw_list = AAM::api()->content->get_terms(
                $args,
                $this->_determine_access_level($request),
                $request->get_param('post_type')
            );

            foreach($raw_list as $item) {
                array_push($result['list'], $this->_prepare_term_output($item));
            }
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Update post permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function update_post_permissions(WP_REST_Request $request)
    {
        try {
            $post = $this->_determine_access_level($request)->get_resource(
                AAM_Framework_Type_Resource::POST,
                $request->get_param('id')
            );

            // Normalize array of permissions
            $normalized = [];

            foreach($request->get_param('permissions') as $item) {
                $normalized[$item['permission']] = array_filter($item, function($k) {
                    return $k !== 'permission';
                }, ARRAY_FILTER_USE_KEY);
            }

            $post->set_permissions($normalized);

            $result = $this->_prepare_post_output($post);
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Update single post permission
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function set_post_permission(WP_REST_Request $request)
    {
        try {
            $post = $this->_determine_access_level($request)->get_resource(
                AAM_Framework_Type_Resource::POST,
                $request->get_param('id')
            );

            $post->set_permissions(array_merge(
                $post->get_permissions(true),
                [ $request->get_param('permission') => $request->get_json_params() ]
            ));

            $result = $this->_prepare_post_output($post);
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Delete post permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function reset_post_permissions(WP_REST_Request $request)
    {
        try {
            $post = $this->_determine_access_level($request)->get_resource(
                AAM_Framework_Type_Resource::POST,
                $request->get_param('id')
            );

            $result = [
                'success' => $post->reset()
            ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Check permissions
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function check_permissions()
    {
        return current_user_can('aam_manager')
            && current_user_can('aam_manage_content');
    }

    /**
     * Prepare post output model
     *
     * @param AAM_Framework_Resource_Post $post_resource
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_post_output($post_resource)
    {
        // Get post type to add additional information about post
        $post_type = get_post_type_object($post_resource->post_type);

        $result = [
            'id'              => $post_resource->ID,
            'icon'            => $post_type->menu_icon,
            'is_hierarchical' => $post_type->hierarchical
        ];

        if ($post_resource->post_type === 'nav_menu_item') {
            $result['title'] = wp_setup_nav_menu_item($post_resource)->title;
        } else {
            $result['title'] = $post_resource->post_title;
        }

        $result['permissions']   = $post_resource->get_permissions();
        $result['is_customized'] = $post_resource->is_customized();

        return $result;
    }

    /**
     * Prepare post type item for output
     *
     * @param AAM_Framework_Resource_PostType $post_type
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_post_type_output($post_type)
    {
        return [
            'slug'            => $post_type->name,
            'title'           => $post_type->label,
            'icon'            => $post_type->menu_icon,
            'is_hierarchical' => $post_type->hierarchical,
            'permissions'     => $post_type->get_permissions(),
            'is_customized'   => $post_type->is_customized()
        ];
    }

    /**
     * Prepare taxonomy item for output
     *
     * @param AAM_Framework_Resource_Taxonomy $taxonomy
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_taxonomy_output($taxonomy)
    {
        return [
            'slug'            => $taxonomy->name,
            'title'           => $taxonomy->label,
            'is_hierarchical' => $taxonomy->hierarchical,
            'permissions'     => $taxonomy->get_permissions(),
            'is_customized'   => $taxonomy->is_customized(),
            'post_types'      => array_values($taxonomy->object_type)
        ];
    }

    /**
     * Prepare term item for output
     *
     * @param AAM_Framework_Resource_Term $term
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_term_output($term)
    {
        $result = [
            'id'              => $term->term_id,
            'title'           => $term->name,
            'slug'            => $term->slug,
            'taxonomy'        => $term->taxonomy,
            'is_hierarchical' => get_taxonomy($term->taxonomy)->hierarchical,
            'permissions'     => $term->get_permissions(),
            'is_customized'   => $term->is_customized()
        ];

        // If term is within post type scope, also determine if it is a default
        // term
        $internal_id = $term->get_internal_id(false);

        if (!empty($internal_id['post_type'])) {
            $is_default = $term->taxonomy === 'category'
                && intval(get_option('default_category')) === $term->term_id;

            $result['is_default'] = apply_filters(
                'aam_is_default_term_filter', $is_default, $term
            );

            // Also adding post type scope to the output
            $result['post_type'] = $internal_id['post_type'];
        }

        return $result;
    }

}