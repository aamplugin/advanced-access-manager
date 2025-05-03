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
 * @version 7.0.0
 */
class AAM_Restful_Settings
{

    use AAM_Restful_ServiceTrait;

    /**
     * Necessary permissions to access endpoint
     *
     * @version 7.0.0
     */
    const PERMISSIONS = [
        'aam_manager',
        'aam_manage_settings'
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
            // Get list of all AAM settings that are explicitly defined
            $this->_register_route('/settings', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_settings')
            ], self::PERMISSIONS);

            // Set settings in bulk
            $this->_register_route('/settings', [
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'set_settings')
            ], self::PERMISSIONS);

            // Reset settings
            $this->_register_route('/settings', [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'reset_settings')
            ], self::PERMISSIONS);
        });
    }

    /**
     * Get all defined settings for everyone or specific access level
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function get_settings(WP_REST_Request $request)
    {
        try {
            if ($this->_has_access_level($request)) {
                $result = $this->_get_service($request)->get_settings();
            } else {
                $result = AAM::api()->db->read(
                    AAM_Framework_Service_Settings::DB_OPTION, []
                );
            }
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
     * @access public
     *
     * @version 7.0.0
     */
    public function set_settings(WP_REST_Request $request)
    {
        try {
            if ($this->_has_access_level($request)) {
                $result = $this->_get_service($request)->set_settings(
                    $request->get_json_params()
                );
            } else {
                $result = AAM::api()->db->write(
                    AAM_Framework_Service_Settings::DB_OPTION,
                    $request->get_json_params()
                );
            }

            $result = [ 'success' => $result ];
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
     * @access public
     *
     * @version 7.0.0
     */
    public function reset_settings(WP_REST_Request $request)
    {
        try {
            if ($this->_has_access_level($request)) {
                $result = $this->_get_service($request)->reset();
            } else {
                $result = AAM::api()->db->delete(
                    AAM_Framework_Service_Settings::DB_OPTION
                );
            }

            $result = [ 'success' => $result ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get service
     *
     * @param WP_REST_Request $request
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_service(WP_REST_Request $request)
    {
        return AAM::api()->settings(
            $this->_determine_access_level($request),
            [ 'error_handling' => 'exception' ]
        );
    }

    /**
     * Determine if access level is provided
     *
     * @param WP_REST_Request $request
     *
     * @return bool
     * @access private
     *
     * @version 7.0.0
     */
    private function _has_access_level($request)
    {
        $access_level = $this->_determine_access_level($request);

        return !is_null($access_level);
    }

}