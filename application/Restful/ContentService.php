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
            $this->_register_route('/content/types', [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_post_types' ],
                'permission_callback' => [ $this, 'check_permissions' ],
                'args'                => [
                    'scope' => [
                        'description' => 'Permissions scope',
                        'type'        => 'string',
                        'default'     => 'all',
                        'enum'        => [ 'term', 'post', 'all' ]
                    ]
                ]
            ]);

            // Get list of all registered taxonomies
            $this->_register_route('/content/taxonomies', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_taxonomies'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => [
                    'scope' => [
                        'description' => 'Permissions scope',
                        'type'        => 'string',
                        'default'     => 'all',
                        'enum'        => [ 'term', 'post', 'all' ]
                    ]
                ]
            ));

            // Get list of posts for given post type
            $this->_register_route('/content/posts', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_posts'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'type' => array(
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
                        'description' => 'Unique taxonomy identifier',
                        'type'        => 'string',
                        'required'    => true
                    ),
                    'scope' => array(
                        'description' => 'Permissions scope',
                        'type'        => 'string',
                        'default'     => 'all',
                        'enum'        => [ 'term', 'post', 'all' ]
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

            // Update permissions
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
            $this->_register_route('/content/post/(?P<id>[\d]+)', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_permissions'),
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
            $result  = $service->get_post_types([
                'include_all' => AAM::api()->configs()->get_config(
                    'core.service.content.manageAllPostTypes'
                ),
                'scope'       => $request->get_param('scope')
            ]);

            // Additionally, to support legacy setup, iterate over the list of
            // taxonomies and enrich them
            // TODO: Remove in January 2025
            foreach($result['list'] as $i => $item) {
                $result['list'][$i] = apply_filters(
                    'aam_rest_get_post_type_filter',
                    $item,
                    (object)['name' => $item['slug']],
                    $service->access_level
                );
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
            $result  = $service->get_taxonomies([
                'include_all' => AAM::api()->configs()->get_config(
                    'core.service.content.manageAllTaxonomies'
                ),
                'scope'       => $request->get_param('scope')
            ]);

            // Additionally, to support legacy setup, iterate over the list of
            // taxonomies and enrich them
            // TODO: Remove in January 2025
            foreach($result['list'] as $i => $item) {
                $result['list'][$i] = apply_filters(
                    'aam_rest_get_taxonomy_filter',
                    $item,
                    (object)['name' => $item['slug']],
                    $service->access_level
                );
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
            $result  = $service->get_posts([
                'numberposts'      => $request->get_param('per_page'),
                'post_type'        => $request->get_param('type'),
                's'                => $request->get_param('search'),
                'offset'           => $request->get_param('offset')
            ]);
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
            $service = $this->_get_service($request);
            $result  = $service->get_post($request->get_param('id'));
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
            $result  = $service->get_terms([
                'number'     => $request->get_param('per_page'),
                'taxonomy'   => $request->get_param('taxonomy'),
                'hide_empty' => false,
                'search'     => $request->get_param('search'),
                'offset'     => $request->get_param('offset'),
                'scope'      => $request->get_param('scope'),
                'post_type'  => $request->get_param('post_type')
            ]);

            // Additionally, to support legacy setup, iterate over the list of terms
            // and enrich them
            // TODO: Remove in January 2025
            foreach($result['list'] as $i => $item) {
                $result['list'][$i] = apply_filters(
                    'aam_rest_get_term_filter',
                    $item,
                    (object)['term_id' => $item['id'], 'taxonomy' => $item['taxonomy']],
                    $service->access_level,
                    $request
                );
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

            $result = $service->get_post($request->get_param('id'));
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
    public function delete_permissions(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->delete_post_permissions($request->get_param('id'));
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
            'subject'        => $this->_determine_subject($request),
            'error_handling' => 'exception'
        ]);
    }

}