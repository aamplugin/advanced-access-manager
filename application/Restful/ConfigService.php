<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for the Configurations service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Restful_ConfigService
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
            // Get list of all AAM configurations explicitly defined
            $this->_register_route('/configs', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_configurations'),
                'permission_callback' => array($this, 'check_permissions')
            ), false);

            // Get a single configuration
            $this->_register_route('/config', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_configuration'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'key' => array(
                        'description' => 'Configuration key',
                        'type'        => 'string',
                        'required'    => true
                    )
                )
            ), false);

            // Get ConfigPress
            $this->_register_route('/configpress', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_configpress'),
                'permission_callback' => array($this, 'check_permissions')
            ), false);

            // Set config
            $this->_register_route('/configs', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'set_configuration'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'key' => array(
                        'description' => 'Configuration key',
                        'type'        => 'string',
                        'required'    => true
                    ),
                    'value' => array(
                        'description' => 'Configuration value',
                        'type'        => [
                            'string',
                            'number',
                            'boolean'
                        ],
                        'required'    => true
                    )
                )
            ), false);

            // Set ConfigPress
            $this->_register_route('/configpress', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'set_configpress'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'ini' => array(
                        'description' => 'ConfigPress INI',
                        'type'        => 'string',
                        'required'    => true
                    )
                )
            ), false);

            // Reset AAM configurations
            $this->_register_route('/configs', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'reset_configurations'),
                'permission_callback' => array($this, 'check_permissions')
            ), false);
        });
    }

    /**
     * Get all defined configurations
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function get_configurations()
    {
        try {
            $result = AAM::api()->config->get();
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get a single configuration
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function get_configuration(WP_REST_Request $request)
    {
        try {
            $key    = $request->get_param('key');
            $result = [
                $key => AAM::api()->config->get($key)
            ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get ConfigPress
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.34
     */
    public function get_configpress()
    {
        try {
            // TODO: Finish ConfigPress
            $result = [
                'ini' => $this->_get_service()->get_configpress()
            ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Set configuration
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function set_configuration(WP_REST_Request $request)
    {
        try {
            $key   = $request->get_param('key');
            $value = $request->get_param('value');

            // Normalize the configuration value
            if (in_array($value, [ 'true', 'false' ], true)) {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } elseif (is_numeric($value)) {
                $value = intval($value);
            }

            $status = AAM::api()->config->set($key, $value);

            if ($status) {
                $result = [
                    $key => AAM::api()->config->get($key)
                ];
            }
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Set ConfigPress
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.34
     */
    public function set_configpress(WP_REST_Request $request)
    {
        try {
            $ini = $request->get_param('ini');

            // TODO: Finish ConfigPress
            $status = $this->_get_service()->set_configpress($ini);

            if ($status) {
                $result = [
                    'ini' => $this->_get_service()->get_configpress()
                ];
            }
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset all configurations
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function reset_configurations()
    {
        try {
            $result = AAM::api()->config->reset();
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
            && current_user_can('aam_manage_configs');
    }

}