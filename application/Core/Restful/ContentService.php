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
class AAM_Core_Restful_ContentService
{

    use AAM_Core_Restful_ServiceTrait;

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
                        'default'     => 'all',
                        'enum'        => [ 'term', 'post', 'all' ]
                    ),
                    'post_type' => array(
                        'description' => __('Scope for specific post type', AAM_KEY),
                        'type'        => 'string'
                    ),
                    'search' => array(
                        'description' => __('Search string', AAM_KEY),
                        'type'        => 'string'
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

            // Get post permissions
            $this->_register_route('/content/post/(?<id>[\d]+)', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_post'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => __('Unique post identifier', AAM_KEY),
                        'type'        => 'number',
                        'required'    => true
                    )
                )
            ));

            // Update permissions
            $this->_register_route('/content/post/(?<id>[\d]+)', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_post_permissions'),
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
                                    'required' => false,
                                    'default'  => 'deny',
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
     * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/386
     * @since 6.9.29 Initial implementation of the method
     *
     * @access public
     * @version 6.9.31
     */
    public function get_post_types($request)
    {
        $subject = $this->_determine_subject($request);
        $service = AAM_Framework_Manager::content(
            new AAM_Framework_Model_ServiceContext([
                'subject' => $subject
            ])
        );

        $result = $service->get_post_types([
            'include_all' => AAM::api()->getConfig(
                'core.service.content.manageAllPostTypes', false
            )
        ]);

        // Additionally, to support legacy setup, iterate over the list of taxonomies
        // and enrich them
        // TODO: Remove in January 2025
        foreach($result['list'] as $i => $item) {
            $result['list'][$i] = apply_filters(
                'aam_rest_get_post_type_filter',
                $item,
                (object)['name' => $item['slug']],
                $subject
            );
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
    public function get_taxonomies($request)
    {
        $subject = $this->_determine_subject($request);
        $service = AAM_Framework_Manager::content(
            new AAM_Framework_Model_ServiceContext([
                'subject' => $subject
            ])
        );

        $result = $service->get_taxonomies([
            'include_all' => AAM::api()->getConfig(
                'core.service.content.manageAllTaxonomies', false
            )
        ]);

        // Additionally, to support legacy setup, iterate over the list of taxonomies
        // and enrich them
        // TODO: Remove in January 2025
        foreach($result['list'] as $i => $item) {
            $result['list'][$i] = apply_filters(
                'aam_rest_get_taxonomy_filter',
                $item,
                (object)['name' => $item['slug']],
                $subject
            );
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
    public function get_posts($request)
    {
        $service = AAM_Framework_Manager::content(
            new AAM_Framework_Model_ServiceContext([
                'subject' => $this->_determine_subject($request)
            ])
        );

        return rest_ensure_response($service->get_posts([
            'numberposts'      => $request->get_param('per_page'),
            'post_type'        => $request->get_param('type'),
            's'                => $request->get_param('search'),
            'offset'           => $request->get_param('offset')
        ]));
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
    public function get_post($request)
    {
        $service = AAM_Framework_Manager::content(
            new AAM_Framework_Model_ServiceContext([
                'subject' => $this->_determine_subject($request)
            ])
        );

        return rest_ensure_response($service->get_post($request->get_param('id')));
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
    public function get_terms($request)
    {
        $subject  = $this->_determine_subject($request);
        $service = AAM_Framework_Manager::content(
            new AAM_Framework_Model_ServiceContext([
                'subject' => $subject
            ])
        );

        $result = $service->get_terms([
            'number'           => $request->get_param('per_page'),
            'taxonomy'         => $request->get_param('taxonomy'),
            'suppress_filters' => false,
            'hide_empty'       => false,
            'search'           => $request->get_param('search'),
            'offset'           => $request->get_param('offset'),
            'scope'            => $request->get_param('scope'),
            'post_type'        => $request->get_param('post_type')
        ]);

        // Additionally, to support legacy setup, iterate over the list of terms
        // and enrich them
        // TODO: Remove in January 2025
        foreach($result['list'] as $i => $item) {
            $result['list'][$i] = apply_filters(
                'aam_rest_get_term_filter',
                $item,
                (object)['term_id' => $item['id'], 'taxonomy' => $item['taxonomy']],
                $subject,
                $request
            );
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
    public function update_post_permissions($request)
    {
        $service = AAM_Framework_Manager::content(
            new AAM_Framework_Model_ServiceContext([
                'subject' => $this->_determine_subject($request)
            ])
        );

        $service->update_post_permissions(
            $request->get_param('id'),
            $request->get_param('permissions')
        );

        return rest_ensure_response($service->get_post($request->get_param('id')));
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
    public function delete_permissions($request)
    {
        $service = AAM_Framework_Manager::content(
            new AAM_Framework_Model_ServiceContext([
                'subject' => $this->_determine_subject($request)
            ])
        );

        $service->delete_post_permissions($request->get_param('id'));

        return rest_ensure_response($service->get_post($request->get_param('id')));
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