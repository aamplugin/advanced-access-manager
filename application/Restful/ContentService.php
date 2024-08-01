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
 * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/386
 * @since 6.9.29 Initial implementation of the class
 *
 * @version 6.9.31
 */
class AAM_Restful_ContentService
{

    use AAM_Restful_ServiceTrait;

    /**
     * Constructor
     *
     * @return void
     *
     * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/386
     * @since 6.9.29 Initial implementation of the method
     *
     * @access protected
     * @version 6.9.31
     */
    protected function __construct()
    {
        add_action('rest_api_init', function() {
            // Get list of all registered post types
            $this->_register_route('/content/post_types', [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_post_types' ],
                'permission_callback' => [ $this, 'check_permissions' ],
                'args'                => [
                    'include_all' => [
                        'description' => 'Include all post types or only public',
                        'type'        => 'boolean',
                        'default'     => false
                    ]
                ]
            ]);

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

            // Get list of terms for given taxonomy
            $this->_register_route('/content/terms', array(
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

            // Get post permissions
            $this->_register_route('/content/post/(?P<id>[\d]+)', array(
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
            $this->_register_route('/content/post/(?P<id>[\d]+)', array(
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
            $this->_register_route('/content/post/(?P<id>[\d]+)/(?P<permission>[\w-]+)', [
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

            // Delete all permissions
            $this->_register_route('/content/post/(?P<id>[\d]+)', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_post_permissions'),
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

        add_filter('aam_rest_prepare_content_item_filter', function($item, $request) {
            if (is_a($item, AAM_Framework_Resource_PostType::class)) {
                $result = $this->_prepare_post_type_item($item, $request);
            } elseif (is_a($item, AAM_Framework_Resource_Taxonomy::class)) {
                $result = $this->_prepare_taxonomy_item($item, $request);
            } elseif (is_a($item, AAM_Framework_Resource_Term::class)) {
                $result = $this->_prepare_term_item($item, $request);
            } elseif (is_a($item, AAM_Framework_Resource_Post::class)) {
                $result = $this->_prepare_post_item($item, $request);
            } else {
                $result = null;
            }

            return $result;
        }, 10, 2);
    }

    /**
     * Get the list of all registered post type
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/386
     * @since 6.9.29 Initial implementation of the method
     *
     * @access public
     * @version 6.9.31
     */
    public function get_post_types(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $configs = AAM::api()->configs();

            // Determine the filters
            $manage_all = $request->get_param('include_all') || $configs->get_config(
                'service.content.manage_all_post_types'
            );

            if ($manage_all) {
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

            $result = [
                'list' => $this->_prepare_post_type_list(
                    $service->get_post_types($filters),
                    $request
                ),
                'summary' => $service->get_post_types($filters, 'summary')
            ];
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
     *
     * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/386
     * @since 6.9.29 Initial implementation of the method
     *
     * @access public
     * @version 6.9.31
     */
    public function get_taxonomies(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $configs = AAM::api()->configs();

            // Determine the filters
            $manage_all = $request->get_param('include_all') || $configs->get_config(
                'service.content.manage_all_taxonomies'
            );

            if ($manage_all) {
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

            $result = [
                'list' => $this->_prepare_taxonomy_list(
                    $service->get_taxonomies($filters),
                    $request
                ),
                'summary' => $service->get_taxonomies($filters, 'summary')
            ];
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
     *
     * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/386
     * @since 6.9.29 Initial implementation of the method
     *
     * @access public
     * @version 6.9.31
     */
    public function get_posts(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $filters = [
                'numberposts' => $request->get_param('per_page'),
                'post_type'   => $request->get_param('post_type'),
                's'           => $request->get_param('search'),
                'offset'      => $request->get_param('offset')
            ];

            $result = [
                'list' => $this->_prepare_post_list(
                    $service->get_posts($filters),
                    $request
                ),
                'summary' => $service->get_posts($filters, 'summary')
            ];

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
     *
     * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/386
     * @since 6.9.29 Initial implementation of the method
     *
     * @access public
     * @version 6.9.31
     */
    public function get_post(WP_REST_Request $request)
    {
        try {
            $service = AAM::api()->content([
                'access_level' => $this->_determine_access_level($request)
            ]);

            $result = apply_filters(
                'aam_rest_prepare_content_item_filter',
                $service->get_post($request->get_param('id')),
                $request
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
     * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/386
     * @since 6.9.29 Initial implementation of the method
     *
     * @access public
     * @version 6.9.31
     */
    public function get_terms(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $filters = [
                'number'     => $request->get_param('per_page'),
                'taxonomy'   => $request->get_param('taxonomy'),
                'hide_empty' => false,
                'search'     => $request->get_param('search'),
                'offset'     => $request->get_param('offset'),
                'post_type'  => $request->get_param('post_type')
            ];

            $result = [
                'list' => $this->_prepare_term_list(
                    $service->get_terms($filters),
                    $request
                ),
                'summary' => $service->get_terms($filters, 'summary')
            ];
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
     *
     * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/386
     * @since 6.9.29 Initial implementation of the method
     *
     * @access public
     * @version 6.9.31
     */
    public function update_post_permissions(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);

            $service->update_post_permissions(
                $request->get_param('id'),
                $request->get_param('permissions')
            );

            $result = apply_filters(
                'aam_rest_prepare_content_item_filter',
                $service->get_post($request->get_param('id')),
                $request
            );
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
     *
     * @access public
     * @version 7.0.0
     */
    public function set_post_permission(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);

            $service->set_post_permission(
                $request->get_param('id'),
                $request->get_param('permission'),
                $request->get_body_params()
            );

            $result = apply_filters(
                'aam_rest_prepare_content_item_filter',
                $service->get_post($request->get_param('id')),
                $request
            );
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
     *
     * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/386
     * @since 6.9.29 Initial implementation of the method
     *
     * @access public
     * @version 6.9.31
     */
    public function delete_post_permissions(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);

            $service->delete_post_permissions($request->get_param('id'));

            $result = apply_filters(
                'aam_rest_prepare_content_item_filter',
                $service->get_post($request->get_param('id')),
                $request
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
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

    /**
     * Prepare post type list response
     *
     * @param array           $items
     * @param WP_REST_Request $request
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_post_type_list($items, $request)
    {
        $result = [];

        foreach($items as $item) {
            array_push($result, $this->_prepare_post_type_item($item, $request));
        }

        return $result;
    }

    /**
     * Prepare post type item
     *
     * @param AAM_Framework_Resource_PostType $item
     * @param WP_REST_Request                 $request
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_post_type_item($item, $request)
    {
        return apply_filters('aam_rest_get_post_type_filter', [
            'title'           => $item->label,
            'slug'            => $item->name,
            'icon'            => $item->menu_icon,
            'is_hierarchical' => $item->hierarchical
        ], $item, $request);
    }

    /**
     * Prepare taxonomy list response
     *
     * @param array           $items
     * @param WP_REST_Request $request
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_taxonomy_list($items, $request)
    {
        $result = [];

        foreach($items as $item) {
            array_push($result, $this->_prepare_taxonomy_item($item, $request));
        }

        return $result;
    }

    /**
     * Prepare post type item
     *
     * @param AAM_Framework_Resource_Taxonomy $item
     * @param WP_REST_Request                 $request
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_taxonomy_item($item, $request)
    {
        return apply_filters('aam_rest_get_taxonomy_filter', [
            'title'           => $item->label,
            'slug'            => $item->name,
            'is_hierarchical' => $item->hierarchical,
            'post_types'      => array_values($item->object_type)
        ], $item, $request);
    }

    /**
     * Prepare term list response
     *
     * @param array           $items
     * @param WP_REST_Request $request
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_term_list($items, $request)
    {
        $result = [];

        foreach($items as $item) {
            array_push($result, $this->_prepare_term_item($item, $request));
        }

        return $result;
    }

    /**
     * Prepare term item
     *
     * @param AAM_Framework_Resource_Term $item
     * @param WP_REST_Request             $request
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_term_item($item, $request)
    {
        return apply_filters('aam_rest_get_term_filter', [
            'id'              => $item->term_id,
            'title'           => $item->name,
            'slug'            => $item->slug,
            'taxonomy'        => $item->taxonomy,
            'is_hierarchical' => get_taxonomy($item->taxonomy)->hierarchical
        ], $item, $request);
    }

    /**
     * Prepare post list response
     *
     * @param array           $items
     * @param WP_REST_Request $request
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_post_list($items, $request)
    {
        $result = [];

        foreach($items as $item) {
            array_push($result, $this->_prepare_post_item($item, $request));
        }

        return $result;
    }

    /**
     * Prepare post item
     *
     * @param AAM_Framework_Resource_Post $item
     * @param WP_REST_Request             $request
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_post_item($item, $request)
    {
        // Get post type to add additional information about post
        $post_type = get_post_type_object($item->post_type);

        $data = [
            'id'              => $item->ID,
            'icon'            => $post_type->menu_icon,
            'is_hierarchical' => $post_type->hierarchical
        ];

        if ($item->post_type === 'nav_menu_item') {
            $data['title'] = wp_setup_nav_menu_item($item)->title;
        } else {
            $data['title'] = $item->post_title;
        }

        $data['permissions']  = $item->get_settings();
        $data['is_inherited'] = !$item->is_overwritten();

        return apply_filters('aam_rest_get_post_filter', $data, $item, $request);
    }

    /**
     * Get service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_Content
     *
     * @access private
     * @version 6.9.33
     */
    private function _get_service(WP_REST_Request $request)
    {
        return AAM_Framework_Manager::content([
            'access_level'   => $this->_determine_access_level($request),
            'error_handling' => 'exception'
        ]);
    }

}