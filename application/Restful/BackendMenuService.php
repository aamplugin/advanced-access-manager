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
 * @version 7.0.0
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
     * @version 7.0.0
     */
    protected function __construct()
    {
        // Register API endpoint
        add_action('rest_api_init', function() {
            // Get the list of backend menus
            $this->_register_route('/backend-menu', [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_menu_items' ],
                'permission_callback' => [ $this, 'check_permissions' ]
            ]);

            // Get a menu
            $this->_register_route('/backend-menu/(?P<id>[A-Za-z0-9\/\+=]+)', [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_menu_item'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => 'Base64 encoded backend menu unique slug',
                        'type'        => 'string',
                        'required'    => true
                    )
                )
            ]);

            // Update a menu's permission
            $this->_register_route('/backend-menu/(?P<id>[A-Za-z0-9\/\+=]+)', [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_menu_item_permission'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
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
            $this->_register_route('/backend-menu/(?P<id>[A-Za-z0-9\/\+=]+)', [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_menu_item_permission'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => 'Base64 encoded backend menu unique slug',
                        'type'        => 'string',
                        'required'    => true
                    )
                )
            ]);

            // Reset all backend menu permissions
            $this->_register_route('/backend-menu', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'reset_permissions'),
                'permission_callback' => array($this, 'check_permissions')
            ));
        });
    }

    /**
     * Get the entire backend menu
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function get_menu_items(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = [];

            foreach($service->get_menu() as $item) {
                array_push($result, $this->_prepare_menu_item($item));
            }
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
     * @version 7.0.0
     */
    public function get_menu_item(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $this->_prepare_menu_item($service->get_menu_item(
                base64_decode($request->get_param('id'))
            ));
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Update backend menu item permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function update_menu_item_permission(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->update_menu_item_permission(
                base64_decode($request->get_param('id')),
                $request->get_param('effect'),
                $request->get_param('is_top_level')
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Delete menu item permission
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function delete_menu_item_permission(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->delete_menu_item_permission(
                base64_decode($request->get_param('id')),
                $request->get_param('is_top_level')
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset all backend menu permissions
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
     * @version 7.0.0
     */
    public function check_permissions()
    {
        return current_user_can('aam_manager')
            && current_user_can('aam_manage_backend_menu');
    }

    /**
     * Prepare menu item
     *
     * @param array $menu
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_menu_item($menu)
    {
        // Adding ID so we can effectively communicate through RESTful API
        $result = array_merge([
            'id' => base64_encode($menu['slug']),
        ], $menu);

        if (!empty($result['children'])) {
            foreach($result['children'] as $i => $child) {
                $result['children'][$i] = $this->_prepare_menu_item($child);
            }
        }

        return $result;
    }

    /**
     * Get JWT framework service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_BackendMenu
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_service($request)
    {
        return AAM_Framework_Manager::backend_menu([
            'access_level'   => $this->_determine_access_level($request),
            'error_handling' => 'exception'
        ]);
    }

}