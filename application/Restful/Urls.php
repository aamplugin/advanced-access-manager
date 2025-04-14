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
class AAM_Restful_Urls
{

    use AAM_Restful_ServiceTrait;

    /**
     * Necessary permissions to access endpoint
     *
     * @version 7.0.0
     */
    const PERMISSIONS = [
        'aam_manager',
        'aam_manage_url_access'
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
            // Get the list of define URL permissions
            $this->_register_route('/urls', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_permissions' ]
            ], self::PERMISSIONS);

            // Define new URL permission
            $this->_register_route('/urls', [
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_permission'),
                'args'     => array(
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
                                'required'    => true
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
            ], self::PERMISSIONS);

            // Get a permission
            $this->_register_route('/url/(?P<id>[A-Za-z0-9\/\+=]+)', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_permission'),
                'args'     => array(
                    'id' => array(
                        'description' => 'Base64 encoded URL schema',
                        'type'        => 'string',
                        'required'    => true
                    )
                )
            ], self::PERMISSIONS);

            // Update an existing permission
            $this->_register_route('/url/(?P<id>[A-Za-z0-9\/\+=]+)', [
                'methods'  => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_permission'),
                'args'     => array(
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
                                'type'        => 'string'
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
            ], self::PERMISSIONS);

            // Delete a permission
            $this->_register_route('/url/(?P<id>[A-Za-z0-9\/\+=]+)', [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_permission'),
                'args'     => array(
                    'id' => array(
                        'description' => 'Base64 encoded URL schema',
                        'type'        => 'string',
                        'required'    => true
                    )
                )
            ], self::PERMISSIONS);

            // Reset all permissions
            $this->_register_route('/urls', [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'reset_permissions')
            ], self::PERMISSIONS);
        });
    }

    /**
     * Get list of all defined URL permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function get_permissions(WP_REST_Request $request)
    {
        try {
            $result = $this->_get_permissions($request);
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
            $url_schema = AAM::api()->misc->sanitize_url(
                $request->get_param('url_schema')
            );

            $effect   = strtolower($request->get_param('effect'));
            $redirect = $request->get_param('redirect');

            // Persist the permission
            if ($effect === 'allow') {
                $service->allow($url_schema);
            } else {
                $service->deny($url_schema, $redirect);
            }

            // Prepare the response
            $result = $this->_prepare_output(
                $url_schema,
                $this->_find_permission_by_id(base64_encode($url_schema), $request),
                true
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
     * @access public
     *
     * @version 7.0.0
     */
    public function get_permission(WP_REST_Request $request)
    {
        try {
            // Prepare result
            $result = $this->_prepare_output(
                base64_decode($request->get_param('id')),
                $this->_find_permission_by_id($request->get_param('id'), $request),
                true
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
     * @access public
     *
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
            $url_schema = AAM::api()->misc->sanitize_url(
                !empty($new_url) ? $new_url : $original_url
            );

            if ($effect === 'allow') {
                $service->allow($url_schema);
            } else {
                $service->deny($url_schema, $redirect);
            }

            // Prepare the response
            $result = $this->_prepare_output(
                $url_schema,
                $this->_find_permission_by_id(base64_encode($url_schema), $request),
                true
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
     * Get complete list of defined permissions
     *
     * @param WP_REST_Request $request
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_permissions($request)
    {
        $result   = [];
        $access_level = $this->_determine_access_level($request);
        $resource     = $access_level->get_resource(
            AAM_Framework_Type_Resource::URL
        );

        foreach($resource->get_permissions() as $url => $permissions) {
            array_push($result, $this->_prepare_output(
                $url, $permissions['access'], $resource->is_customized($url)
            ));
        }

        return $result;
    }

    /**
     * Find permission by ID
     *
     * @param string          $id
     * @param WP_REST_Request $request
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _find_permission_by_id($id, $request)
    {
        $permissions = array_filter(
            $this->_get_permissions($request),
            function($p) use ($id) {
                return $p['id'] === $id;
            }
        );

        if (empty($permissions)) {
            throw new OutOfRangeException('Permission does not exist');
        }

        return array_shift($permissions);
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
                __('The custom_message cannot be empty or be unsafe', 'advanced-access-manager'),
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
     * @access private
     *
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
                    __('The redirect_page_id refers to non-existing page', 'advanced-access-manager'),
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
     * @access private
     *
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
                __('The redirect_url is not valid URL', 'advanced-access-manager'),
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
     * @access private
     *
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
                __('The callback is not valid PHP callback', 'advanced-access-manager'),
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
     * @param string $url_schema
     * @param array  $permission
     * @param bool   $is_customized
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_output($url_schema, $permission, $is_customized)
    {
       return array_merge([
            'id'            => base64_encode($url_schema),
            'url_schema'    => $url_schema,
            'is_customized' => $is_customized
        ], $permission);
    }

    /**
     * Get service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_Urls
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_service(WP_REST_Request $request)
    {
        return AAM::api()->urls(
            $this->_determine_access_level($request),
            [ 'error_handling' => 'exception' ]
        );
    }

}