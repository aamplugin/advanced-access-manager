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
 * @since 6.9.37 https://github.com/aamplugin/advanced-access-manager/issues/413
 * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
 * @since 6.9.21 https://github.com/aamplugin/advanced-access-manager/issues/339
 * @since 6.9.9  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.37
 */
class AAM_Restful_UrlService
{

    use AAM_Restful_ServiceTrait;

    /**
     * Constructor
     *
     * @return void
     *
     * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/360
     * @since 6.9.21 https://github.com/aamplugin/advanced-access-manager/issues/339
     * @since 6.9.9  Initial implementation of the method
     *
     * @access protected
     * @version 6.9.26
     */
    protected function __construct()
    {
        // Register API endpoint
        add_action('rest_api_init', function() {
            // Get the list of rules
            $this->_register_route('/urls', [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_rule_list' ],
                'permission_callback' => [ $this, 'check_permissions' ]
            ]);

            // Create a new rule
            $this->_register_route('/urls', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'create_rule'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'url' => array(
                        'description' => 'URL or URI for the rule',
                        'type'        => 'string',
                        'required'    => true
                    ),
                    'effect' => array(
                        'description' => 'Wether allow or deny access to URL',
                        'type'        => 'string',
                        'required'    => true,
                        'enum'        => [ 'allow', 'deny' ]
                    ),
                    'redirect' => [
                        'type'     => 'object',
                        'required' => false,
                        'properties' => [
                            'type' => [
                                'description' => 'Rule type',
                                'type'        => 'string',
                                'required'    => true,
                                'enum'        => apply_filters(
                                    'aam_url_access_allowed_rule_types_filter',
                                    AAM_Framework_Service_Urls::ALLOWED_RULE_TYPES
                                )
                            ],
                            'message' => [
                                'description' => 'Custom access denied message',
                                'type'        => 'string',
                                'validate_callback' => function ($value, $request) {
                                    return $this->_validate_message(
                                        $value, $request
                                    );
                                }
                            ],
                            'redirect_page_id' => [
                                'description' => 'Existing page ID to redirect to',
                                'type'        => 'number',
                                'validate_callback' => function ($value, $request) {
                                    return $this->_validate_redirect_page_id(
                                        $value, $request
                                    );
                                }
                            ],
                            'redirect_url' => [
                                'description' => 'Valid URL to redirect to',
                                'type'        => 'string',
                                'validate_callback' => function ($value, $request) {
                                    return $this->_validate_redirect_url(
                                        $value, $request
                                    );
                                }
                            ],
                            'callback' => array(
                                'description' => 'Custom callback function',
                                'type'        => 'string',
                                'validate_callback' => function ($value, $request) {
                                    return $this->_validate_callback(
                                        $value, $request
                                    );
                                }
                            ),
                            'http_status_code' => [
                                'description' => 'HTTP Status Code',
                                'type'        => 'number'
                            ]
                        ]
                    ]
                )
            ));

            // Get a rule
            $this->_register_route('/url', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_rule'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'url' => array(
                        'description' => 'URL or URI for the rule',
                        'type'        => 'string',
                        'required'    => true
                    ),
                )
            ));

            // Update an existing rule
            $this->_register_route('/url', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_rule'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'url' => array(
                        'description' => 'URL or URI for the rule',
                        'type'        => 'string',
                        'required'    => true
                    ),
                    'new_url' => array(
                        'description' => 'Updated rule URL',
                        'type'        => 'string',
                        'required'    => false
                    ),
                    'effect' => array(
                        'description' => 'Wether allow or deny access to URL',
                        'type'        => 'string',
                        'required'    => true,
                        'enum'        => [ 'allow', 'deny' ]
                    ),
                    'redirect' => [
                        'type'     => 'object',
                        'required' => false,
                        'properties' => [
                            'type' => [
                                'description' => 'Rule type',
                                'type'        => 'string',
                                'required'    => true,
                                'enum'        => apply_filters(
                                    'aam_url_access_allowed_rule_types_filter',
                                    AAM_Framework_Service_Urls::ALLOWED_RULE_TYPES
                                )
                            ],
                            'message' => [
                                'description' => 'Custom access denied message',
                                'type'        => 'string',
                                'validate_callback' => function ($value, $request) {
                                    return $this->_validate_message(
                                        $value, $request
                                    );
                                }
                            ],
                            'redirect_page_id' => [
                                'description' => 'Existing page ID to redirect to',
                                'type'        => 'number',
                                'validate_callback' => function ($value, $request) {
                                    return $this->_validate_redirect_page_id(
                                        $value, $request
                                    );
                                }
                            ],
                            'redirect_url' => [
                                'description' => 'Valid URL to redirect to',
                                'type'        => 'string',
                                'validate_callback' => function ($value, $request) {
                                    return $this->_validate_redirect_url(
                                        $value, $request
                                    );
                                }
                            ],
                            'callback' => array(
                                'description' => 'Custom callback function',
                                'type'        => 'string',
                                'validate_callback' => function ($value, $request) {
                                    return $this->_validate_callback(
                                        $value, $request
                                    );
                                }
                            ),
                            'http_status_code' => [
                                'description' => 'HTTP Status Code',
                                'type'        => 'number'
                            ]
                        ]
                    ]
                )
            ));

            // Delete a rule
            $this->_register_route('/url', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_rule'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'url' => array(
                        'description' => 'URL or URI for the rule',
                        'type'        => 'string',
                        'required'    => true
                    ),
                )
            ));

            // Reset all rules
            $this->_register_route('/urls', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'reset_rules'),
                'permission_callback' => array($this, 'check_permissions')
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
        try {
            $service = $this->_get_service($request);
            $result  = $service->get_rule_list();
        } catch(Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
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
        try {
            $service = $this->_get_service($request);
            $result  = $service->create_rule($request->get_params());
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
        try {
            $service = $this->_get_service($request);
            $result  = $service->get_rule(urldecode($request->get_param('url')));
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
        try {
            $service       = $this->_get_service($request);
            $incoming_data = $request->get_params();

            // Swapping the "new_url" prop with "url"
            $incoming_data['url'] = $incoming_data['new_url'];
            $incoming_data        = array_filter($incoming_data, function($key) {
                return $key !== 'new_url';
            }, ARRAY_FILTER_USE_KEY);

            $result = $service->update_rule(
                urldecode($request->get_param('url')), $incoming_data
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
        try {
            $service = $this->_get_service($request);
            $result  = [
                'success' => $service->delete_rule(
                    urldecode($request->get_param('url'))
                )
            ];
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
     * @version 6.9.9
     */
    public function check_permissions()
    {
        return current_user_can('aam_manager')
            && (current_user_can('aam_manage_uri')
            || current_user_can('aam_manage_url_access'));
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
        $redirect  = $request->get_param('redirect');
        $rule_type = !empty($redirect['type']) ? $redirect['type'] : null;
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
        $redirect  = $request->get_param('redirect');
        $rule_type = !empty($redirect['type']) ? $redirect['type'] : null;
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
        $redirect  = $request->get_param('redirect');
        $rule_type = !empty($redirect['type']) ? $redirect['type'] : null;
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
        $redirect  = $request->get_param('redirect');
        $rule_type = !empty($redirect['type']) ? $redirect['type'] : null;

        if ($rule_type === 'trigger_callback'
            && is_callable($value, true) === false
        ) {
            $response = new WP_Error(
                'rest_invalid_param',
                __('The callback is not valid PHP callback', AAM_KEY),
                array('status'  => 400)
            );
        }

        return $response;
    }

    /**
     * Get service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_Urls
     *
     * @access private
     * @version 6.9.33
     */
    private function _get_service(WP_REST_Request $request)
    {
        return AAM_Framework_Manager::urls([
            'access_level'   => $this->_determine_access_level($request),
            'error_handling' => 'exception'
        ]);
    }

}