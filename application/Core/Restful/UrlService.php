<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for the URL Access service
 *
 * @package AAM
 * @version 6.9.9
 */
class AAM_Core_Restful_UrlService
{

    use AAM_Core_Restful_ServiceTrait;

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 6.9.9
     */
    protected function __construct()
    {
        // Register API endpoint
        add_action('rest_api_init', function() {
            // Get the list of rules
            $this->_register_route('/url', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_rule_list'),
                'permission_callback' => array($this, 'check_permissions'),
                'args' => array()
            ));

            // Create a new rule
            $this->_register_route('/url', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'create_rule'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'url' => array(
                        'description' => __('URL or URI for the rule', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_url($value, $request);
                        }
                    ),
                    'type' => array(
                        'description' => __('Rule type', AAM_KEY),
                        'type'        => 'string',
                        'required'    => true,
                        'enum'        => array_values(
                            AAM_Framework_Service_Urls::RULE_TYPE_ALIAS
                        )
                    ),
                    'http_redirect_code' => array(
                        'description' => __('HTTP redirect code', AAM_KEY),
                        'type'        => 'number'
                    ),
                    'message' => array(
                        'description' => __('Custom access denied message', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_message($value, $request);
                        }
                    ),
                    'redirect_page_id' => array(
                        'description' => __('Existing page ID to redirect to', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_redirect_page_id($value, $request);
                        }
                    ),
                    'redirect_url' => array(
                        'description' => __('Valid URL to redirect to', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_redirect_url($value, $request);
                        }
                    ),
                    'callback' => array(
                        'description' => __('Custom callback function', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_callback($value, $request);
                        }
                    )
                )
            ));

            // Get a rule
            $this->_register_route('/url/(?<id>[\d]+)', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_rule'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => __('URL unique ID', AAM_KEY),
                        'type'        => 'number',
                        'required'    => true
                    )
                )
            ));

            // Update an existing rule
            $this->_register_route('/url/(?<id>[\d]+)', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_rule'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => __('URL unique ID', AAM_KEY),
                        'type'        => 'number',
                        'required'    => true
                    ),
                    'url' => array(
                        'description' => __('URL or URI for the rule', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_url($value, $request);
                        }
                    ),
                    'type' => array(
                        'description' => __('Rule type', AAM_KEY),
                        'type'        => 'string',
                        'required'    => true,
                        'enum'        => array_values(
                            AAM_Framework_Service_Urls::RULE_TYPE_ALIAS
                        )
                    ),
                    'http_redirect_code' => array(
                        'description' => __('HTTP redirect code', AAM_KEY),
                        'type'        => 'number'
                    ),
                    'message' => array(
                        'description' => __('Custom access denied message', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_message($value, $request);
                        }
                    ),
                    'redirect_page_id' => array(
                        'description' => __('Existing page ID to redirect to', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_redirect_page_id($value, $request);
                        }
                    ),
                    'redirect_url' => array(
                        'description' => __('Valid URL to redirect to', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_redirect_url($value, $request);
                        }
                    ),
                    'callback' => array(
                        'description' => __('Custom callback function', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_callback($value, $request);
                        }
                    )
                )
            ));

            // Delete a rule
            $this->_register_route('/url/(?<id>[\d]+)', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_rule'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => __('URL unique ID', AAM_KEY),
                        'type'        => 'number',
                        'required'    => true
                    )
                )
            ));

            // Reset all rules
            $this->_register_route('/url/reset', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'reset_rules'),
                'permission_callback' => array($this, 'check_permissions'),
                'args' => array()
            ));
        });
    }

    /**
     * Get list of all rules
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.9
     */
    public function get_rule_list(WP_REST_Request $request)
    {
        $service = AAM_Framework_Manager::urls(
            new AAM_Framework_Model_ServiceContext(array(
                'subject' => $this->_determine_subject($request)
            ))
        );

        return rest_ensure_response($service->get_rule_list());
    }

    /**
     * Create new rule
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.9
     */
    public function create_rule(WP_REST_Request $request)
    {
        $service = AAM_Framework_Manager::urls(
            new AAM_Framework_Model_ServiceContext(array(
                'subject' => $this->_determine_subject($request)
            ))
        );

        try {
            $result = $service->create_rule($request->get_params());
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get a rule
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.9
     */
    public function get_rule(WP_REST_Request $request)
    {
        $service = AAM_Framework_Manager::urls(array(
            'subject' => $this->_determine_subject($request)
        ));

        try {
            $result = $service->get_rule_by_id(
                intval($request->get_param('id'))
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Update a rule
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.9
     */
    public function update_rule(WP_REST_Request $request)
    {
        $service = AAM_Framework_Manager::urls(array(
            'subject' => $this->_determine_subject($request)
        ));

        try {
            $result = $service->update_rule(
                intval($request->get_param('id')),
                $request->get_params()
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Delete a rule
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.9
     */
    public function delete_rule(WP_REST_Request $request)
    {
        $service = AAM_Framework_Manager::urls(array(
            'subject' => $this->_determine_subject($request)
        ));

        try {
            $result = $service->delete_rule(intval($request->get_param('id')));
        } catch (UnderflowException $e) {
            $result = $this->_prepare_error_response($e, 'rest_not_found', 404);
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset all rules
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.9
     */
    public function reset_rules(WP_REST_Request $request)
    {
        $service = AAM_Framework_Manager::urls(array(
            'subject' => $this->_determine_subject($request)
        ));

        try {
            $result = $service->reset_rules();
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
     * @version 6.9.9
     */
    public function check_permissions()
    {
        return current_user_can('aam_manager')
                && (current_user_can('aam_manage_uri')
                || current_user_can('aam_manage_url_access'));
    }

    /**
     * Validate the 'url' param
     *
     * @param string $value
     *
     * @return boolean|WP_Error
     *
     * @access private
     * @version 6.9.9
     */
    private function _validate_url($value)
    {
        $response = true;
        $url      = wp_validate_redirect($value);

        if (empty($url)) {
            $response = new WP_Error(
                'rest_invalid_param',
                __('The url is not a valid URL', AAM_KEY),
                array('status'  => 400)
            );
        }

        return $response;
    }

    /**
     * Validate custom message
     *
     * @param string           $value
     * @param WP_REST_Request $request
     *
     * @return boolean|WP_Error
     *
     * @access private
     * @version 6.9.9
     */
    private function _validate_message($value, $request)
    {
        $response  = true;
        $rule_type = $request->get_param('type');
        $message   = esc_js(trim($value));

        if ($rule_type === 'custom_message' && strlen($message) === 0) {
            $response = new WP_Error(
                'rest_invalid_param',
                __('The custom_message cannot be empty or be unsafe', AAM_KEY),
                array('status'  => 400)
            );
        }

        return $response;
    }

    /**
     * Validate redirect page ID
     *
     * @param int             $value
     * @param WP_REST_Request $request
     *
     * @return boolean|WP_Error
     *
     * @access private
     * @version 6.9.9
     */
    private function _validate_redirect_page_id($value, $request)
    {
        $response  = true;
        $rule_type = $request->get_param('type');
        $page_id   = intval($value);

        if ($rule_type === 'page_redirect') {
            if ($page_id === 0 || get_post($page_id) === null) {
                $response = new WP_Error(
                    'rest_invalid_param',
                    __('The redirect_page_id refers to non-existing page', AAM_KEY),
                    array('status'  => 400)
                );
            }
        }

        return $response;
    }

    /**
     * Validate redirect URL
     *
     * @param string          $value
     * @param WP_REST_Request $request
     *
     * @return boolean|WP_Error
     *
     * @access private
     * @version 6.9.9
     */
    private function _validate_redirect_url($value, $request)
    {
        $response  = true;
        $rule_type = $request->get_param('type');
        $url       = wp_validate_redirect($value);

        if ($rule_type === 'url_redirect' && empty($url)) {
            $response = new WP_Error(
                'rest_invalid_param',
                __('The redirect_url is not valid URL', AAM_KEY),
                array('status'  => 400)
            );
        }

        return $response;
    }

    /**
     * Validate the callback value
     *
     * @param string          $value
     * @param WP_REST_Request $request
     *
     * @return boolean|WP_Error
     *
     * @access private
     * @version 6.9.9
     */
    private function _validate_callback($value, $request)
    {
        $response  = true;
        $rule_type = $request->get_param('type');

        if ($rule_type === 'trigger_callback' && is_callable($value, true) === false) {
            $response = new WP_Error(
                'rest_invalid_param',
                __('The callback is not valid PHP callback', AAM_KEY),
                array('status'  => 400)
            );
        }

        return $response;
    }

}