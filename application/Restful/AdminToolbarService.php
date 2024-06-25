<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for the Admin Toolbar service
 *
 * @package AAM
 * @version 6.9.13
 */
class AAM_Restful_AdminToolbarService
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
            // Get the list of backend menus
            $this->_register_route('/admin-toolbar', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_menus'),
                'permission_callback' => array($this, 'check_permissions')
            ));

            // Get a menu
            $this->_register_route('/admin-toolbar/(?P<id>[\d]+)', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_menu'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => 'Admin toolbar menu unique ID',
                        'type'        => 'number',
                        'required'    => true
                    )
                )
            ));

            // Set or update a menu's permission
            $this->_register_route('/admin-toolbar/(?P<id>[\d]+)', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_item_permission'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => 'Admin toolbar menu unique ID',
                        'type'        => 'number',
                        'required'    => true
                    ),
                    'is_hidden' => array(
                        'description' => 'Either menu is hidden or not',
                        'type'        => 'boolean',
                        'default'     => true
                    )
                )
            ));

            // Delete a menu's permission
            $this->_register_route('/admin-toolbar/(?P<id>[\d]+)', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_item_permission'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => 'Admin toolbar menu unique ID',
                        'type'        => 'number',
                        'required'    => true
                    )
                )
            ));

            // Reset all admin toolbar permissions
            $this->_register_route('/admin-toolbar', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'reset_permissions'),
                'permission_callback' => array($this, 'check_permissions')
            ));
        });
    }

    /**
     * Get the admin toolbar menu list
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.13
     */
    public function get_menus(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->get_item_list();
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get admin toolbar menu item
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.13
     */
    public function get_menu(WP_REST_Request $request)
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
     * Update admin toolbar menu permission
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.13
     */
    public function update_item_permission(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->update_item_permission(
                intval($request->get_param('id')),
                $request->get_param('is_hidden')
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Delete menu permission
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.13
     */
    public function delete_item_permission(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->delete_item_permission(
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
            $result  = $service->reset();
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
            && current_user_can('aam_manage_toolbar');
    }

    /**
     * Get Admin Toolbar framework service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_AdminToolbar
     *
     * @access private
     * @version 6.9.13
     */
    private function _get_service($request)
    {
        return AAM_Framework_Manager::admin_toolbar([
            'subject'        => $this->_determine_subject($request),
            'error_handling' => 'exception'
        ]);
    }

}