<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for the Access Denied Redirect service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Restful_AccessDeniedRedirect
{

    use AAM_Restful_ServiceTrait;

    /**
     * Necessary permissions to access endpoint
     *
     * @version 7.0.0
     */
    const PERMISSIONS = [
        'aam_manager',
        'aam_manage_access_denied_redirect'
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
            // Get current access denied redirect rule
            $this->_register_route('/redirect/access-denied', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_redirect' ],
                'args'     => [
                    'area' => [
                        'description' => 'Access area (frontend, backend or api)',
                        'type'        => 'string',
                        'required'    => false
                    ]
                ]
            ], self::PERMISSIONS);

            // Create a redirect rule
            $this->_register_route('/redirect/access-denied', [
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => [ $this, 'set_redirect' ],
                'args'     => [
                    'area' => [
                        'description' => 'Access area (frontend,  backend or api)',
                        'type'        => 'string',
                        'required'    => true
                    ],
                    'type' => [
                        'description' => 'Redirect type',
                        'type'        => 'string',
                        'required'    => true
                    ],
                    'http_status_code' => [
                        'description'       => 'HTTP Status Code',
                        'type'              => 'number',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_http_status_code(
                                $value, $request
                            );
                        }
                    ],
                    'redirect_page_id' => [
                        'description'       => 'Existing page ID to redirect to',
                        'type'              => 'number',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_redirect_page_id(
                                $value, $request
                            );
                        }
                    ],
                    'redirect_page_slug' => [
                        'description'       => 'Existing page slug to redirect to',
                        'type'              => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_redirect_page_slug(
                                $value, $request
                            );
                        }
                    ],
                    'redirect_url' => [
                        'description'       => 'Valid URL to redirect to',
                        'type'              => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_redirect_url($value, $request);
                        }
                    ],
                    'callback' => [
                        'description'       => 'Custom callback function',
                        'type'              => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_callback($value, $request);
                        }
                    ],
                    'message' => [
                        'description' => 'Custom access denied message',
                        'type'        => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_message($value, $request);
                        }
                    ]
                ]
            ], self::PERMISSIONS);

            // Delete/reset access denied redirect rule for a specific area
            // or all at once
            $this->_register_route('/redirect/access-denied', [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => [ $this, 'reset_redirect' ],
                'args'     => [
                    'area' => [
                        'description' => 'Access area (frontend, backend or api)',
                        'type'        => 'string',
                        'required'    => false
                    ]
                ]
            ], self::PERMISSIONS);
        });
    }

    /**
     * Get current redirect rule
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function get_redirect(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->get_redirect($request->get_param('area'));
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Set the access denied redirect
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function set_redirect(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $area    = $request->get_param('area');
            $data    = $request->get_params();

            // Preparing the proper array of properties to store
            unset($data['area']);

            // Finally set the redirect
            $service->set_redirect($area, $data);

            $result = $service->get_redirect($area);
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset the redirect rule
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function reset_redirect(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = [
                'success' => $service->reset($request->get_param('area'))
            ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Validate the 'url' param
     *
     * @param string $value
     *
     * @return boolean|WP_Error
     * @access private
     *
     * @version 7.0.0
     */
    private function _validate_url($value)
    {
        $response = true;
        $url      = AAM::api()->misc->sanitize_url($value);

        if (empty($url)) {
            $response = new WP_Error(
                'rest_invalid_param',
                'The provided url is not a valid URL',
                [ 'status'  => 400 ]
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
        $rule_type = $request->get_param('type');
        $page_id   = intval($value);

        if ($rule_type === 'page_redirect') {
            if ($page_id === 0 || get_post($page_id) === null) {
                $response = new WP_Error(
                    'rest_invalid_param',
                    'The redirect_page_id refers to non-existing page',
                    array('status'  => 400)
                );
            }
        }

        return $response;
    }

    /**
     * Validate redirect page slug
     *
     * @param string          $value
     * @param WP_REST_Request $request
     *
     * @return boolean|WP_Error
     * @access private
     *
     * @version 7.0.0
     */
    private function _validate_redirect_page_slug($value, $request)
    {
        $response  = true;
        $rule_type = $request->get_param('type');

        if ($rule_type === 'page_redirect') {
            if (get_page_by_path($value) === null) {
                $response = new WP_Error(
                    'rest_invalid_param',
                    'The redirect_page_slug refers to non-existing page',
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
        $rule_type = $request->get_param('type');
        $url       = AAM::api()->misc->sanitize_url($value);

        if ($rule_type === 'url_redirect' && empty($url)) {
            $response = new WP_Error(
                'rest_invalid_param',
                'The redirect_url is not valid URL',
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
        $rule_type = $request->get_param('type');

        if ($rule_type === 'trigger_callback'
            && is_callable($value, true) === false
        ) {
            $response = new WP_Error(
                'rest_invalid_param',
                'The callback is not valid PHP callback',
                [ 'status'  => 400 ]
            );
        }

        return $response;
    }

    /**
     * Validate the custom message value
     *
     * @param string          $value
     * @param WP_REST_Request $request
     *
     * @return boolean|WP_Error
     * @access private
     *
     * @version 7.0.0
     */
    private function _validate_message($value, $request)
    {
        $response  = true;
        $rule_type = $request->get_param('type');

        if ($rule_type === 'custom_message' && empty($value)) {
            $response = new WP_Error(
                'rest_invalid_param',
                'The custom message cannot be empty',
                [ 'status'  => 400 ]
            );
        }

        return $response;
    }

    /**
     * Validate HTTP status code
     *
     * @param int             $value
     * @param WP_REST_Request $request
     *
     * @return boolean|WP_Error
     * @access private
     *
     * @version 7.0.0
     */
    private function _validate_http_status_code($value, $request)
    {
        $type  = $request->get_param('type');
        $code  = intval($value);
        $valid = false;

        if (in_array($type, ['default', 'custom_message'], true)) {
            $valid = ($code >= 400 && $code <= 499) || ($code >= 500 && $code <= 599);
        } elseif (in_array($type, ['page_redirect', 'url_redirect'], true)) {
            $valid = $code >= 300 && $code <= 399;
        } elseif ($type === 'trigger_callback') {
            $valid = $code >= 300 && $code <= 599;
        }

        if ($valid === false) {
            $response = new WP_Error(
                'rest_invalid_param',
                'The http_status_code is invalid for given redirect type',
                [ 'status'  => 400 ]
            );
        } else {
            $response = true;
        }

        return $response;
    }

    /**
     * Get Access Denied Redirect framework service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_AccessDeniedRedirect
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_service($request)
    {
        return AAM::api()->access_denied_redirect(
            $this->_determine_access_level($request),
            [ 'error_handling' => 'exception' ]
        );
    }

}