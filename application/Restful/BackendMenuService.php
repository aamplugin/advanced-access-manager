<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for the Backend Menu service
 *
 * @package AAM
 * @version 6.9.13
 */
class AAM_Restful_BackendMenuService
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
            $this->_register_route('/backend-menu', [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_menus' ],
                'permission_callback' => [ $this, 'check_permissions' ]
            ]);

            // Get a menu
            $this->_register_route('/backend-menu/(?P<slug>[A-Za-z0-9\/\+=]+)', [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_menu_item'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'slug' => array(
                        'description' => 'Base64 encoded backend menu unique slug',
                        'type'        => 'string',
                        'required'    => true
                    )
                )
            ]);

            // Update a menu's permission
            $this->_register_route('/backend-menu/(?P<slug>[A-Za-z0-9\/\+=]+)', [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_menu_permission'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'slug' => array(
                        'description' => 'Base64 encoded backend menu unique slug',
                        'type'        => 'string',
                        'required'    => true
                    ),
                    'effect' => array(
                        'description' => 'Either menu is restricted or not',
                        'type'        => 'string',
                        'default'     => 'deny',
                        'enum'        => [ 'allow', 'deny' ]
                    ),
                    'is_top_level' => [
                        'description' => 'Wether restricting whole branch or not',
                        'type'        => 'boolean',
                        'default'     => false
                    ]
                )
            ]);

            // Delete a menu's permission
            $this->_register_route('/backend-menu/(?P<slug>[A-Za-z0-9\/\+=]+)', [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_menu_permission'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'slug' => array(
                        'description' => 'Base64 encoded backend menu unique slug',
                        'type'        => 'string',
                        'required'    => true
                    )
                )
            ]);

            // Reset all backend menu permissions
            $this->_register_route('/backend-menu', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'reset_menu_permissions'),
                'permission_callback' => array($this, 'check_permissions')
            ));
        });
    }

    /**
     * Get the backend menu list
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
     * Get backend menu item
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.13
     */
    public function get_menu_item(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->get_item(base64_decode(
                $request->get_param('slug')
            ));
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Update backend menu permission
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.13
     */
    public function update_menu_permission(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->update_item_permission(
                base64_decode($request->get_param('slug')),
                $request->get_param('effect'),
                $request->get_param('is_top_level')
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
    public function delete_menu_permission(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->delete_item_permission(base64_decode(
                $request->get_param('slug')
            ));
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
    public function reset_menu_permissions(WP_REST_Request $request)
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
            && current_user_can('aam_manage_admin_menu');
    }

    /**
     * Get JWT framework service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_BackendMenu
     *
     * @access private
     * @version 6.9.13
     */
    private function _get_service($request)
    {
        return AAM_Framework_Manager::backend_menu([
            'access_level'   => $this->_determine_access_level($request),
            'error_handling' => 'exception'
        ]);
    }

}