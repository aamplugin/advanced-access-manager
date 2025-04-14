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
class AAM_Restful_Metabox
{

    use AAM_Restful_ServiceTrait;

    /**
     * Necessary permissions to access endpoint
     *
     * @version 7.0.0
     */
    const PERMISSIONS = [
        'aam_manager',
        'aam_manage_metaboxes'
    ];

    /**
     * Constructor
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function __construct()
    {
        // Register API endpoint
        add_action('rest_api_init', function() {
            // Get the list of all metaboxes grouped by screen
            $this->_register_route('/metaboxes', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_items'),
                'args'     => array(
                    'screen_id' => array(
                        'description' => 'The screen ID when metabox are rendered',
                        'type'        => 'string',
                        'required'    => false
                    )
                )
            ], self::PERMISSIONS);

            // Get a metabox
            $this->_register_route('/metabox/(?P<slug>[\w]+)', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_item'),
                'args'     => array(
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
            ], self::PERMISSIONS);

            // Update a metabox's permission
            $this->_register_route('/metabox/(?P<slug>[\w]+)', [
                'methods'  => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_item_permission'),
                'args'     => array(
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
            ], self::PERMISSIONS);

            // Delete a metabox's permission
            $this->_register_route('/metabox/(?P<slug>[\w]+)', [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'reset_item_permission'),
                'args'     => array(
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
            ], self::PERMISSIONS);

            // Reset all or specific screen permissions
            $this->_register_route('/metaboxes', [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'reset_permissions'),
                'args' => array(
                    'screen_id' => array(
                        'description' => 'The screen ID when metaboxes are rendered',
                        'type'        => 'string',
                        'required'    => false
                    )
                )
            ], self::PERMISSIONS);
        });
    }

    /**
     * Get a list of metaboxes for given post type
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
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
     * @access public
     *
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
     * @access public
     *
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
                $service->deny($slug, $screen_id);
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
     * @access public
     *
     * @version 7.0.0
     */
    public function reset_item_permission(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = [ 'success' => $service->reset(
                $request->get_param('slug'),
                $request->get_param('screen_id')
            ) ];
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
     * @access public
     *
     * @version 7.0.0
     */
    public function reset_permissions(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = [ 'success' => $service->reset(
                null,
                $request->get_param('screen_id')
            ) ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get framework service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_Metaboxes
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_service($request)
    {
        return AAM::api()->metaboxes(
            $this->_determine_access_level($request),
            [ 'error_handling' => 'exception' ]
        );
    }

}