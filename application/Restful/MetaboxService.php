<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for the Metaboxes service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Restful_MetaboxService
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
        // Register API endpoint
        add_action('rest_api_init', function() {
            // Get the list of all metaboxes grouped by screen
            $this->_register_route('/metaboxes', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_list'),
                'permission_callback' => array($this, 'check_permissions'),
                'args' => array(
                    'screen_id' => array(
                        'description' => 'The screen ID when metabox are rendered',
                        'type'        => 'string',
                        'required'    => false
                    )
                )
            ));

            // Get a metabox
            $this->_register_route('/metabox/(?P<slug>[\w]+)', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_item'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'slug' => array(
                        'description' => 'Metabox slug',
                        'type'        => 'string',
                        'required'    => true
                    ),
                    'screen_id' => array(
                        'description' => 'The screen ID when metabox is rendered',
                        'type'        => 'string',
                        'required'    => false
                    )
                )
            ));

            // Update a metabox's permission
            $this->_register_route('/metabox/(?P<slug>[\w]+)', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_item_permission'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'slug' => array(
                        'description' => 'Metabox slug',
                        'type'        => 'string',
                        'required'    => true
                    ),
                    'screen_id' => array(
                        'description' => 'The screen ID when metabox is rendered',
                        'type'        => 'string',
                        'required'    => false
                    ),
                    'effect' => array(
                        'description' => 'Either metabox is restricted or not',
                        'type'        => 'string',
                        'default'     => 'deny',
                        'enum'        => [ 'allow', 'deny' ]
                    )
                )
            ));

            // Delete a metabox's permission
            $this->_register_route('/metabox/(?P<slug>[\w]+)', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_item_permission'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'slug' => array(
                        'description' => 'Metabox slug',
                        'type'        => 'string',
                        'required'    => true
                    ),
                    'screen_id' => array(
                        'description' => 'The screen ID when metabox is rendered',
                        'type'        => 'string',
                        'required'    => false
                    )
                )
            ));

            // Reset all or specific screen permissions
            $this->_register_route('/metaboxes', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'reset_permissions'),
                'permission_callback' => array($this, 'check_permissions'),
                'args' => array(
                    'screen_id' => array(
                        'description' => 'The screen ID when metaboxes are rendered',
                        'type'        => 'string',
                        'required'    => false
                    )
                )
            ));
        });
    }

    /**
     * Get a list of metaboxes for given post type
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function get_items(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->get_items($request->get_param('screen_id'));
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get a metabox by ID
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function get_item(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->item(
                $request->get_param('slug'),
                $request->get_param('screen_id')
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Update metabox permission
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function update_item_permission(WP_REST_Request $request)
    {
        try {
            $service   = $this->_get_service($request);
            $slug      = $request->get_param('slug');
            $screen_id = $request->get_param('screen_id');

            if ($request->get_param('effect') === 'allow') {
                $service->allow($slug, $screen_id);
            } else {
                $service->restrict($slug, $screen_id);
            }

            $result = $service->item($slug, $screen_id);
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Delete metabox permission
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function delete_item_permission(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->reset(
                $request->get_param('slug'),
                $request->get_param('screen_id')
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset all permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function reset_permissions(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->reset($request->get_param('screen_id'));
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Check if current user has access to the service
     *
     * @return bool
     *
     * @access public
     * @version 7.0.0
     */
    public function check_permissions()
    {
        return current_user_can('aam_manager')
            && current_user_can('aam_manage_metaboxes');
    }

    /**
     * Get framework service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_Metaboxes
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_service($request)
    {
        return AAM::api()->metaboxes([
            'access_level'   => $this->_determine_access_level($request),
            'error_handling' => 'exception'
        ]);
    }

}