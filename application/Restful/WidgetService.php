<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for the Widgets service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Restful_WidgetService
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
            // Get the list of all widgets grouped by screen
            $this->_register_route('/widgets', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_list'),
                'permission_callback' => array($this, 'check_permissions'),
                'args' => array(
                    'screen_id' => array(
                        'description' => 'Screen ID',
                        'type'        => 'string',
                        'enum'        => [ 'dashboard', 'frontend' ]
                    )
                )
            ));

            // Get a widget
            $this->_register_route('/widget/(?P<slug>[\w]+)', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_item'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'slug' => array(
                        'description' => 'Widget unique slug',
                        'type'        => 'string',
                        'required'    => true
                    )
                )
            ));

            // Update a widget's permission
            $this->_register_route('/widget/(?P<slug>[\w]+)', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_item_permission'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'slug' => array(
                        'description' => 'Widget unique slug',
                        'type'        => 'string',
                        'required'    => true
                    ),
                    'effect' => array(
                        'description' => 'Either metabox is restricted or not',
                        'type'        => 'string',
                        'default'     => 'deny',
                        'enum'        => [ 'allow', 'deny' ]
                    )
                )
            ));

            // Delete a widget's permission
            $this->_register_route('/widget/(?P<slug>[\w]+)', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_item_permission'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'slug' => array(
                        'description' => 'Widget unique slug',
                        'type'        => 'string',
                        'required'    => true
                    )
                )
            ));

            // Reset all or specific screen permissions
            $this->_register_route('/widgets', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'reset_permissions'),
                'permission_callback' => array($this, 'check_permissions'),
                'args' => array(
                    'screen_id' => array(
                        'description' => 'Screen ID',
                        'type'        => 'string',
                        'enum'        => [ 'dashboard', 'frontend' ]
                    )
                )
            ));
        });
    }

    /**
     * Get a list of widgets grouped by screen
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
     * Get a widget by ID
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
            $result  = $service->item($request->get_param('slug'));
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Update widget permission
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
            $service = $this->_get_service($request);
            $slug    = $request->get_param('slug');

            if ($request->get_param('effect') === 'allow') {
                $service->allow($slug);
            } else {
                $service->deny($slug);
            }

            $result = $service->item($slug);
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Delete widget permission
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
            $result  = $service->reset($request->get_param('slug'));
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
            && current_user_can('aam_manage_widgets');
    }

    /**
     * Get framework service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_Widgets
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_service($request)
    {
        return AAM::api()->widgets([
            'access_level'   => $this->_determine_access_level($request),
            'error_handling' => 'exception'
        ]);
    }

}