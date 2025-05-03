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
class AAM_Restful_Configs
{

    use AAM_Restful_ServiceTrait;

    /**
     * Necessary permissions to access endpoint
     *
     * @version 7.0.0
     */
    const PERMISSIONS = [
        'aam_manager',
        'aam_manage_configs'
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
            // Get list of all AAM configurations explicitly defined
            $this->_register_route('/configs', array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_configurations'),
            ), self::PERMISSIONS, false);

            // Get a single configuration
            $this->_register_route('/config/(?P<key>[\w\.]+)', array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_configuration'),
                'args'     => array(
                    'key' => array(
                        'description' => 'Configuration key',
                        'type'        => 'string',
                        'required'    => true
                    )
                )
            ), self::PERMISSIONS, false);

            // Get ConfigPress
            $this->_register_route('/configpress', array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_configpress')
            ), self::PERMISSIONS, false);

            // Set config
            $this->_register_route('/config/(?P<key>[\w\.]+)', array(
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'set_configuration'),
                'args'     => array(
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
            ), self::PERMISSIONS, false);

            // Set ConfigPress
            $this->_register_route('/configpress', array(
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'set_configpress'),
                'args'     => array(
                    'ini' => array(
                        'description' => 'ConfigPress INI',
                        'type'        => 'string',
                        'required'    => true
                    )
                )
            ), self::PERMISSIONS, false);

            // Reset AAM configurations
            $this->_register_route('/configs', array(
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'reset_configurations')
            ), self::PERMISSIONS, false);

            // Reset AAM ConfigPress
            $this->_register_route('/configpress', array(
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'reset_configpress')
            ), self::PERMISSIONS, false);
        });
    }

    /**
     * Get all defined configurations
     *
     * @return WP_REST_Response
     * @access public
     *
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
     * @access public
     *
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
     * @access public
     *
     * @version 7.0.0
     */
    public function get_configpress()
    {
        try {
            // Read stored ConfigPress from DB
            $raw = AAM::api()->db->read(AAM_Service_Core::DB_OPTION);

            if (!empty($raw)) {
                $parsed = parse_ini_string($raw, true, INI_SCANNER_TYPED);
                $parsed = !empty($parsed['aam']) ? $parsed['aam'] : [];
            } else {
                $parsed = [];
            }

            $result = [
                'ini'    => empty($raw) ? '[aam]' : $raw,
                'parsed' => $parsed
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
     * @access public
     *
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
     * @access public
     *
     * @version 7.0.0
     */
    public function set_configpress(WP_REST_Request $request)
    {
        try {
            $raw = $request->get_param('ini');

            if (!empty($raw)) {
                $parsed = parse_ini_string($raw, true, INI_SCANNER_TYPED);
            } else {
                $parsed = [];
            }

            if (empty($parsed)) {
                throw new InvalidArgumentException('The ConfigPress value is empty');
            } else {
                $result = AAM::api()->db->write(
                    AAM_Service_Core::DB_OPTION, $raw
                );
            }

            if ($result) {
                $result = [
                    'ini'    => $raw,
                    'parsed' => $parsed
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
     * @access public
     *
     * @version 7.0.0
     */
    public function reset_configurations()
    {
        try {
            $result = [
                'success' => AAM::api()->config->reset()
            ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset all configurations
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function reset_configpress()
    {
        try {
            $result = [
                'success' => AAM::api()->db->delete(
                    AAM_Service_Core::DB_OPTION
                )
            ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

}