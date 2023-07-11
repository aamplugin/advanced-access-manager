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
class AAM_Core_Restful_AdminToolbarService
{

    use AAM_Core_Restful_ServiceTrait;

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
                'permission_callback' => array($this, 'check_permissions'),
                'args' => array()
            ));

            // Get a menu
            $this->_register_route('/admin-toolbar/(?<id>[\d]+)', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_menu'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => __('Admin toolbar menu unique ID', AAM_KEY),
                        'type'        => 'number',
                        'required'    => true
                    )
                )
            ));

            // Update a menu's permission
            $this->_register_route('/admin-toolbar/(?<id>[\d]+)', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_item_permission'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => __('Admin toolbar menu unique ID', AAM_KEY),
                        'type'        => 'number',
                        'required'    => true
                    ),
                    'is_hidden' => array(
                        'description' => __('Either menu is hidden or not', AAM_KEY),
                        'type'        => 'boolean'
                    )
                )
            ));

            // Delete a menu's permission
            $this->_register_route('/admin-toolbar/(?<id>[\d]+)', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_item_permission'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => __('Admin toolbar menu unique ID', AAM_KEY),
                        'type'        => 'number',
                        'required'    => true
                    )
                )
            ));

            // Reset all backend menu permissions
            $this->_register_route('/admin-toolbar/reset', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'reset_permissions'),
                'permission_callback' => array($this, 'check_permissions'),
                'args' => array()
            ));

            // Register additional endpoints with add-ons
            $more = apply_filters('aam_admin_toolbar_api_filter', array(), array(
                'methods'             => WP_REST_Server::EDITABLE,
                'permission_callback' => array($this, 'check_permissions'),
                'args' => array()
            ));

            if (is_array($more)) {
                foreach($more as $endpoint => $params) {
                    // Wrap the callback function to include the current subject
                    $params['callback'] = function(WP_REST_Request $request) use ($params) {
                        return call_user_func_array($params['callback'], array(
                            $request,
                            $this->_determine_subject($request)
                        ));
                    };

                    $this->_register_route($endpoint, $params);
                }
            }
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
        $service = $this->_get_service($request);

        return rest_ensure_response($service->get_item_list());
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
        $service = $this->_get_service($request);

        try {
            $result = $service->get_item_by_id(
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
        $service = $this->_get_service($request);

        try {
            $result = $service->update_item_permission(
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
        $service = $this->_get_service($request);

        try {
            $result = $service->delete_item_permission(
                intval($request->get_param('id'))
            );
        } catch (UnderflowException $e) {
            $result = $this->_prepare_error_response($e, 'rest_not_found', 404);
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
        $service = $this->_get_service($request);

        try {
            $result = $service->reset_permissions();
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
     * Get JWT framework service
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
        return AAM_Framework_Manager::admin_toolbar(
            new AAM_Framework_Model_ServiceContext(array(
                'subject' => $this->_determine_subject($request)
            ))
        );
    }

}