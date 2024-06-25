<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for the Metaboxes & Widgets service
 *
 * @package AAM
 * @version 6.9.13
 */
class AAM_Restful_ComponentService
{

    use AAM_Restful_ServiceTrait;

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 6.9.13
     */
    protected function __construct()
    {
        // Register API endpoint
        add_action('rest_api_init', function() {
            // Get the list of all metaboxes & widgets grouped by screen
            $this->_register_route('/components', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_components'),
                'permission_callback' => array($this, 'check_permissions'),
                'args' => array(
                    'screen_id' => array(
                        'description' => 'WordPress screen ID',
                        'type'        => 'string'
                    )
                )
            ));

            // Get a metabox or widget
            $this->_register_route('/component/(?P<id>[\d]+)', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_component'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => 'Metabox or widget unique ID',
                        'type'        => 'number',
                        'required'    => true
                    )
                )
            ));

            // Update a component's permission
            $this->_register_route('/component/(?P<id>[\d]+)', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_component_permission'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => 'Component unique ID',
                        'type'        => 'number',
                        'required'    => true
                    ),
                    'is_hidden' => array(
                        'description' => 'Either component is hidden or not',
                        'type'        => 'boolean',
                        'default'     => true
                    )
                )
            ));

            // Delete a component's permission
            $this->_register_route('/component/(?P<id>[\d]+)', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_component_permission'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => 'Component unique ID',
                        'type'        => 'number',
                        'required'    => true
                    )
                )
            ));

            // Reset all or specific screen permissions
            $this->_register_route('/components', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'reset_permissions'),
                'permission_callback' => array($this, 'check_permissions'),
                'args' => array(
                    'screen_id' => array(
                        'description' => 'WordPress screen ID',
                        'type'        => 'string'
                    )
                )
            ));
        });
    }

    /**
     * Get a list of components grouped by screen
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.13
     */
    public function get_components(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->get_item_list($request->get_param('screen_id'));
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get a component by ID
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.13
     */
    public function get_component(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->get_item_by_id(
                intval($request->get_param('id'))
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Update component permission
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.13
     */
    public function update_component_permission(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->update_component_permission(
                intval($request->get_param('id')),
                $request->get_param('is_hidden')
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Delete component permission
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.13
     */
    public function delete_component_permission(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->delete_component_permission(
                intval($request->get_param('id'))
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
     * @version 6.9.13
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
     * @version 6.9.13
     */
    public function check_permissions()
    {
        return current_user_can('aam_manager')
            && current_user_can('aam_manage_metaboxes');
    }

    /**
     * Get JWT framework service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_Components
     *
     * @access private
     * @version 6.9.13
     */
    private function _get_service($request)
    {
        return AAM_Framework_Manager::components([
            'subject'        => $this->_determine_subject($request),
            'error_handling' => 'exception'
        ]);
    }

}