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
 * @version 7.0.0
 */
class AAM_Restful_AdminToolbar
{

    use AAM_Restful_ServiceTrait;

    /**
     * Necessary permissions to access endpoint
     *
     * @version 7.0.0
     */
    const PERMISSIONS = [
        'aam_manager',
        'aam_manage_admin_toolbar'
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
            // Get the list of backend menus
            $this->_register_route('/admin-toolbar', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_items' ],
            ], self::PERMISSIONS);

            // Get a menu
            $this->_register_route('/admin-toolbar/(?P<slug>[\w\-]+)', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_item'),
                'args'     => array(
                    'slug' => array(
                        'description' => 'Toolbar item unique ID',
                        'type'        => 'string',
                        'required'    => true
                    )
                )
            ], self::PERMISSIONS);

            // Set or update a menu's permission
            $this->_register_route('/admin-toolbar/(?P<slug>[\w\-]+)', [
                'methods'  => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_item_permission'),
                'args'     => array(
                    'slug' => array(
                        'description' => 'Toolbar item unique ID',
                        'type'        => 'string',
                        'required'    => true
                    ),
                    'effect' => array(
                        'description' => 'Either menu is restricted or not',
                        'type'        => 'string',
                        'default'     => 'deny',
                        'enum'        => [ 'allow', 'deny' ]
                    )
                )
            ], self::PERMISSIONS);

            // Delete a menu's permission
            $this->_register_route('/admin-toolbar/(?P<slug>[\w\-]+)', [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_item_permission'),
                'args'     => array(
                    'slug' => array(
                        'description' => 'Toolbar item unique ID',
                        'type'        => 'string',
                        'required'    => true
                    )
                )
            ], self::PERMISSIONS);

            // Reset all admin toolbar permissions
            $this->_register_route('/admin-toolbar', [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => [ $this, 'reset_permissions' ],
            ], self::PERMISSIONS);
        });
    }

    /**
     * Get the admin toolbar menu list
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
            $result  = $service->get_items();
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
     * @access public
     *
     * @version 7.0.0
     */
    public function get_item(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->get_item($request->get_param('slug'));
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
     * @access public
     *
     * @version 7.0.0
     */
    public function update_item_permission(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $item_id = $request->get_param('slug');

            if ($request->get_param('effect') === 'allow') {
                $result = $service->allow($item_id);
            } else {
                $result = $service->deny($item_id);
            }

            $result = $service->get_item($item_id);
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
     * @access public
     *
     * @version 7.0.0
     */
    public function delete_item_permission(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = [
                'success' => $service->reset($request->get_param('slug'))
            ];
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
            $result  = [
                'success' => $this->_get_service($request)->reset()
            ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get Admin Toolbar framework service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_AdminToolbar
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_service($request)
    {
        return AAM::api()->admin_toolbar(
            $this->_determine_access_level($request),
            [ 'error_handling' => 'exception' ]
        );
    }

}