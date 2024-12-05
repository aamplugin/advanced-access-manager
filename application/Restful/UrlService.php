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
 * @version 7.0.0
 */
class AAM_Restful_UrlService
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
            // Get the list of define URL permissions
            $this->_register_route('/urls', [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_permissions' ],
                'permission_callback' => [ $this, 'check_permissions' ]
            ]);

            // Define new URL permission
            $this->_register_route('/urls', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'create_permission'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'url_schema' => array(
                        'description' => 'URL schema',
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
                                'description' => 'Redirect type',
                                'type'        => 'string',
                                'required'    => true,
                                'enum'        => apply_filters(
                                    'aam_url_access_allowed_rule_types_filter',
                                    AAM_Framework_Service_Urls::ALLOWED_REDIRECT_TYPES
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

            // Get a permission
            $this->_register_route('/url/(?P<id>[A-Za-z0-9\/\+=]+)', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_permission'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => 'Base64 encoded URL schema',
                        'type'        => 'string',
                        'required'    => true
                    ),
                )
            ));

            // Update an existing permission
            $this->_register_route('/url/(?P<id>[A-Za-z0-9\/\+=]+)', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_permission'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => 'Based64 encoded URL schema',
                        'type'        => 'string',
                        'required'    => true
                    ),
                    'url_schema' => array(
                        'description' => 'New URL schema',
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
                                'description' => 'Redirect type',
                                'type'        => 'string',
                                'required'    => true,
                                'enum'        => apply_filters(
                                    'aam_url_access_allowed_rule_types_filter',
                                    AAM_Framework_Service_Urls::ALLOWED_REDIRECT_TYPES
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

            // Delete a permission
            $this->_register_route('/url/(?P<id>[A-Za-z0-9\/\+=]+)', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_permission'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => 'Base64 encoded URL schema',
                        'type'        => 'string',
                        'required'    => true
                    ),
                )
            ));

            // Reset all permissions
            $this->_register_route('/urls', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'reset_permissions'),
                'permission_callback' => array($this, 'check_permissions')
            ));
        });
    }

    /**
     * Get list of all defined URL permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function get_permissions(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = [];

            foreach ($service->get_permissions() as $permission) {
                array_push($result, $this->_prepare_permission($permission));
            }
        } catch(Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Create new permission for given URL
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function create_permission(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);

            // Grab all the necessary permission attributes
            $url_schema = $request->get_param('url_schema');
            $effect     = strtolower($request->get_param('effect'));
            $redirect   = $request->get_param('redirect');

            // Persist the permission
            if ($effect === 'allow') {
                $service->allow($url_schema);
            } else {
                $service->restrict($url_schema, $redirect);
            }

            // Prepare the response
            $result = $this->_prepare_permission(
                $service->get_permission($url_schema)
            );
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
     * @version 7.0.0
     */
    public function get_permission(WP_REST_Request $request)
    {
        try {
            $service    = $this->_get_service($request);
            $url_schema = base64_decode($request->get_param('id'));

            // Prepare result
            $result = $this->_prepare_permission(
                $service->get_permission($url_schema)
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Update permissions for a specific URL
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function update_permission(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);

            // Get all the necessary attributes
            $original_url = base64_decode($request->get_param('id'));
            $new_url      = $request->get_param('url_schema');
            $redirect     = $request->get_param('redirect');
            $effect       = strtolower($request->get_param('effect'));

            // If we are updating URL, then first, let's delete the original rule
            if (!empty($new_url) && ($original_url !== $new_url)) {
                $service->reset($original_url);
            }

            // Now we are settings permission for the given URL
            $url_schema = !empty($new_url) ? $new_url : $original_url;

            if ($effect === 'allow') {
                $service->allow($url_schema);
            } else {
                $service->restrict($url_schema, $redirect);
            }

            // Prepare the result
            $result = $this->_prepare_permission(
                $service->get_permission($url_schema)
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Delete permission for a given URL
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function delete_permission(WP_REST_Request $request)
    {
        try {
            $service    = $this->_get_service($request);
            $url_schema = base64_decode($request->get_param('id'));

            // Reset the permission
            $service->reset($url_schema);

            // Prepare the result
            $result = [ 'success' => true ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset permissions to all URLs
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

            // Reset all permissions
            $service->reset();

            // Prepare the result
            $result = [ 'success' => true ];
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
            && current_user_can('aam_manage_url_access');
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
     * @version 7.0.0
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
     * @version 7.0.0
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
     * @version 7.0.0
     */
    private function _validate_redirect_url($value, $request)
    {
        $response  = true;
        $redirect  = $request->get_param('redirect');
        $rule_type = !empty($redirect['type']) ? $redirect['type'] : null;
        $url       = AAM::api()->misc->sanitize_url($value);

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
     * @version 7.0.0
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
     * Prepare the URL permission model
     *
     * This method prepares the URL model so it can be effectively used by RESTful
     * API
     *
     * @param array $permission
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_permission($permission)
    {
        return array_merge([
            'id' => base64_encode($permission['url_schema'])
        ], $permission);
    }

    /**
     * Get service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_Urls
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_service(WP_REST_Request $request)
    {
        return AAM::api()->urls([
            'access_level'   => $this->_determine_access_level($request),
            'error_handling' => 'exception'
        ]);
    }

}