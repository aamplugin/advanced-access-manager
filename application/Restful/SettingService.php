<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for the Settings service
 *
 * @package AAM
 * @version 6.9.34
 */
class AAM_Restful_SettingService
{

    use AAM_Restful_ServiceTrait;

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 6.9.34
     */
    protected function __construct()
    {
        // Register API endpoint
        add_action('rest_api_init', function() {
            // Get list of all AAM settings that are explicitly defined
            $this->_register_route('/settings', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_settings'),
                'permission_callback' => array($this, 'check_permissions')
            ));

            // Set settings in bulk
            $this->_register_route('/settings', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'set_settings'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'settings' => array(
                        'description' => 'Collection of settings',
                        'type'        => 'object',
                        'required'    => true
                    )
                )
            ));

            // Reset settings
            $this->_register_route('/settings', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'reset_settings'),
                'permission_callback' => array($this, 'check_permissions')
            ));
        });
    }

    /**
     * Get all defined settings for everyone or specific access level
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.34
     */
    public function get_settings(WP_REST_Request $request)
    {
        try {
            $result = $this->_get_service($request)->get_settings();
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Set settings
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.34
     */
    public function set_settings(WP_REST_Request $request)
    {
        try {
            $result = $this->_get_service($request)->set_settings(
                $request->get_param('settings')
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset all settings
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.34
     */
    public function reset_settings(WP_REST_Request $request)
    {
        try {
            $result = $this->_get_service($request)->reset();
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
     * @version 6.9.34
     */
    public function check_permissions()
    {
        return current_user_can('aam_manager')
            && current_user_can('aam_manage_settings');
    }

    /**
     * Get service
     *
     * @param WP_REST_Request $request
     *
     * @access private
     * @version 6.9.34
     */
    private function _get_service(WP_REST_Request $request)
    {
        return AAM_Framework_Manager::settings([
            'subject'        => $this->_determine_subject($request),
            'error_handling' => 'exception'
        ]);
    }

}