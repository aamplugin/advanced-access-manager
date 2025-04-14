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
     * Necessary permissions to access endpoint
     *
     * @version 7.0.0
     */
    const PERMISSIONS = [
        'aam_manager',
        'aam_manage_content'
    ];

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
                'methods'  => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_post_types' ],
                'args'     => [
                    'return_all' => [
                        'description' => 'Include all post types or only public',
                        'type'        => 'boolean',
                        'default'     => null
                    ]
                ]
            ], self::PERMISSIONS);

            // Get list of all registered taxonomies
            $this->_register_route('/taxonomies', array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_taxonomies')
            ), self::PERMISSIONS);

            // Get list of terms for given taxonomy
            $this->_register_route('/terms', array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_terms'),
                'args'     => array(
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
            ), self::PERMISSIONS);

            // Get list of posts for given post type
            $this->_register_route('/posts', array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_posts'),
                'args'     => array(
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
            ), self::PERMISSIONS);

            // Get post permissions
            $this->_register_route('/post/(?P<id>[\d]+)', array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_post'),
                'args'     => array(
                    'id' => array(
                        'description' => 'Unique post identifier',
                        'type'        => 'number',
                        'required'    => true
                    )
                )
            ), self::PERMISSIONS);

            // Update post permissions
            $this->_register_route('/post/(?P<id>[\d]+)', array(
                'methods'  => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_post_permissions'),
                'args'     => array(
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
            ), self::PERMISSIONS);

            // Update post permission
            $this->_register_route('/post/(?P<id>[\d]+)/(?P<permission>[\w]+)', [
                'methods'  => WP_REST_Server::EDITABLE,
                'callback' => [ $this, 'set_post_permission' ],
                'args'     => [
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
            ], self::PERMISSIONS);

            // Reset post permissions
            $this->_register_route('/post/(?P<id>[\d]+)', array(
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'reset_post_permissions'),
                'args'     => array(
                    'id' => array(
                        'description' => 'Unique post identifier',
                        'type'        => 'number',
                        'required'    => true
                    )
                )
            ), self::PERMISSIONS);
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
            $access_level = $this->_determine_access_level($request);
            $raw_list     = AAM::api()->content->get_post_types(
                $access_level,
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
                    $result['list'], $this->_prepare_post_type_output(
                        $access_level,
                        $post_type
                    )
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
            $access_level = $this->_determine_access_level($request);
            $raw_list     = AAM::api()->content->get_taxonomies(
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
                    $result['list'], $this->_prepare_taxonomy_output(
                        $access_level, $taxonomy
                    )
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

            $access_level = $this->_determine_access_level($request);

            foreach(AAM::api()->content->get_posts($args) as $item) {
                array_push($result['list'], $this->_prepare_post_output(
                    $item, $access_level
                ));
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
                get_post($request->get_param('id')),
                $this->_determine_access_level($request)
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
     * @access public
     *
     * @version 7.0.0
     */
    public function get_terms(WP_REST_Request $request)
    {
        try {
            $access_level = $this->_determine_access_level($request);
            $args         = [
                'number'     => $request->get_param('per_page'),
                'taxonomy'   => $request->get_param('taxonomy'),
                'hide_empty' => false,
                'search'     => $request->get_param('search'),
                'offset'     => $request->get_param('offset')
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
                $args, $request->get_param('post_type')
            );

            foreach($raw_list as $item) {
                array_push($result['list'], $this->_prepare_term_output(
                    $access_level, $item
                ));
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
            $post         = get_post($request->get_param('id'));
            $access_level = $this->_determine_access_level($request);
            $resource     = $access_level->get_resource(
                AAM_Framework_Type_Resource::POST
            );

            // Normalize array of permissions
            $normalized = [];

            foreach($request->get_param('permissions') as $item) {
                $normalized[$item['permission']] = array_filter($item, function($k) {
                    return $k !== 'permission';
                }, ARRAY_FILTER_USE_KEY);
            }

            $resource->set_permissions($normalized, $post);

            $result = $this->_prepare_post_output($post, $access_level);
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
            $post         = get_post($request->get_param('id'));
            $access_level = $this->_determine_access_level($request);
            $resource     = $access_level->get_resource(
                AAM_Framework_Type_Resource::POST
            );

            $resource->set_permission(
                $post,
                $request->get_param('permission'),
                $request->get_json_params()
            );

            $result = $this->_prepare_post_output($post, $access_level);
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
            $post     = get_post($request->get_param('id'));
            $resource = $this->_determine_access_level($request)->get_resource(
                AAM_Framework_Type_Resource::POST

            );

            $result = [
                'success' => $resource->reset($post)
            ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Prepare post output model
     *
     * @param WP_Post                             $post
     * @param AAM_Framework_AccessLevel_Interface $access_level
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_post_output($post, $access_level)
    {
        // Get post type to add additional information about post
        $post_type = get_post_type_object($post->post_type);
        $resource   = $access_level->get_resource(
            AAM_Framework_Type_Resource::POST
        );

        $result = [
            'id'              => $post->ID,
            'icon'            => $post_type->menu_icon,
            'is_hierarchical' => $post_type->hierarchical
        ];

        if ($post->post_type === 'nav_menu_item') {
            $result['title'] = wp_setup_nav_menu_item($post)->title;
        } else {
            $result['title'] = $post->post_title;
        }

        $result['permissions']   = $resource->get_permissions($post);
        $result['is_customized'] = $resource->is_customized($post);

        return $result;
    }

    /**
     * Prepare post type item for output
     *
     * @param AAM_Framework_AccessLevel_Interface $access_level
     * @param WP_Post_Type                        $post_type
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_post_type_output($access_level, $post_type)
    {
        $resource = $access_level->get_resource(
            AAM_Framework_Type_Resource::POST_TYPE
        );

        return [
            'slug'            => $post_type->name,
            'title'           => $post_type->label,
            'icon'            => $post_type->menu_icon,
            'is_hierarchical' => $post_type->hierarchical,
            'permissions'     => $resource->get_permissions($post_type),
            'is_customized'   => $resource->is_customized($post_type)
        ];
    }

    /**
     * Prepare taxonomy item for output
     *
     * @param AAM_Framework_AccessLevel_Interface $access_level
     * @param \WP_Taxonomy                        $taxonomy
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_taxonomy_output($access_level, $taxonomy)
    {
        $resource = $access_level->get_resource(
            AAM_Framework_Type_Resource::TAXONOMY
        );

        return [
            'slug'            => $taxonomy->name,
            'title'           => $taxonomy->label,
            'is_hierarchical' => $taxonomy->hierarchical,
            'permissions'     => $resource->get_permissions($taxonomy),
            'is_customized'   => $resource->is_customized($taxonomy),
            'post_types'      => array_values($taxonomy->object_type)
        ];
    }

    /**
     * Prepare term item for output
     *
     * @param AAM_Framework_AccessLevel_Interface $access_level
     * @param WP_Term                             $term
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_term_output($access_level, $term)
    {
        $resource = $access_level->get_resource(AAM_Framework_Type_Resource::TERM);
        $result   = [
            'id'              => $term->term_id,
            'title'           => $term->name,
            'slug'            => $term->slug,
            'taxonomy'        => $term->taxonomy,
            'is_hierarchical' => get_taxonomy($term->taxonomy)->hierarchical,
            'permissions'     => $resource->get_permissions($term),
            'is_customized'   => $resource->is_customized($term)
        ];

        if (!empty($post_type_scope)) {
            $is_default = $term->taxonomy === 'category'
                && intval(get_option('default_category')) === $term->term_id;

            $result['is_default'] = apply_filters(
                'aam_is_default_term_filter', $is_default, $term, $access_level
            );

            // Also adding post type scope to the output
            $result['post_type'] = $post_type_scope;
        }

        return $result;
    }

}