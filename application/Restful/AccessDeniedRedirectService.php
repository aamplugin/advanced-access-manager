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
 * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/359
 * @since 6.9.14 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.26
 */
class AAM_Restful_AccessDeniedRedirectService
{

    use AAM_Restful_ServiceTrait;

    /**
     * Constructor
     *
     * @return void
     *
     * @since 6.9.26 https://github.com/aamplugin/advanced-access-manager/issues/359
     * @since 6.9.14 Initial implementation of the method
     *
     * @access protected
     * @version 6.9.26
     */
    protected function __construct()
    {
        // Register API endpoint
        add_action('rest_api_init', function() {
            // Get current access denied redirect rules
            $this->_register_route('/redirect/access-denied', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_redirect' ],
                'permission_callback' => [ $this, 'check_permissions' ],
                'args'                => [
                    'area' => [
                        'description' => 'Access area (frontend or backend)',
                        'type'        => 'string',
                        'required'    => false,
                        'enum'        => AAM_Framework_Service_AccessDeniedRedirect::ALLOWED_REDIRECT_AREAS
                    ]
                ]
            ));

            // Create a redirect rule
            $this->_register_route('/redirect/access-denied', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'set_redirect' ],
                'permission_callback' => [ $this, 'check_permissions' ],
                'args'                => [
                    'area' => [
                        'description' => 'Access area (frontend or backend)',
                        'type'        => 'string',
                        'required'    => true,
                        'enum'        => AAM_Framework_Service_AccessDeniedRedirect::ALLOWED_REDIRECT_AREAS
                    ],
                    'type' => [
                        'description' => 'Rule type',
                        'type'        => 'string',
                        'required'    => true,
                        'enum'        => array_values(
                            AAM_Framework_Service_AccessDeniedRedirect::REDIRECT_TYPE_ALIAS
                        )
                    ],
                    'http_status_code' => [
                        'description'       => 'HTTP Status Code',
                        'type'              => 'number',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_redirect_status_code($value, $request);
                        }
                    ],
                    'redirect_page_id' => [
                        'description'       => 'Existing page ID to redirect to',
                        'type'              => 'number',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_redirect_page_id($value, $request);
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
                    'custom_message' => [
                        'description' => 'Custom access denied message',
                        'type'        => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_message($value, $request);
                        }
                    ]
                ]
            ));

            // Delete/reset access denied redirect rule for a specific area
            // or all at once
            $this->_register_route('/redirect/access-denied', [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [ $this, 'reset_redirect' ],
                'permission_callback' => [ $this, 'check_permissions' ],
                'args'                => [
                    'area' => [
                        'description' => 'Access area (frontend or backend)',
                        'type'        => 'string',
                        'required'    => false,
                        'enum'        => AAM_Framework_Service_AccessDeniedRedirect::ALLOWED_REDIRECT_AREAS
                    ],
                ]
            ]);
        });
    }

    /**
     * Get current redirect rule
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.14
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
     *
     * @access public
     * @version 6.9.14
     */
    public function set_redirect(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);

            $service->set_redirect($request->get_params());

            $result = $service->get_redirect($request->get_param('area'));
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
     *
     * @access public
     * @version 6.9.14
     */
    public function reset_redirect(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->reset($request->get_param('area'));
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
     * @version 6.9.14
     */
    public function check_permissions()
    {
        return current_user_can('aam_manager') &&
            (current_user_can('aam_manage_access_denied_redirect')
            || current_user_can('aam_manage_403_redirect'));
    }

    /**
     * Validate the 'url' param
     *
     * @param string $value
     *
     * @return boolean|WP_Error
     *
     * @access private
     * @version 6.9.14
     */
    private function _validate_url($value)
    {
        $response = true;
        $url      = wp_validate_redirect($value);

        if (empty($url)) {
            $response = new WP_Error(
                'rest_invalid_param',
                'The provided url is not a valid URL',
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
     * @version 6.9.14
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
     * Validate HTTP status code
     *
     * @param string          $value
     * @param WP_REST_Request $request
     *
     * @return boolean|WP_Error
     *
     * @access private
     * @version 6.9.26
     */
    private function _validate_redirect_status_code($value, $request)
    {
        $response    = true;
        $rule_type   = $request->get_param('type');
        $status_code = intval($value);

        $allowed = AAM_Framework_Service_AccessDeniedRedirect::HTTP_STATUS_CODES[$rule_type];

        if (is_null($allowed) && !empty($status_code)) {
            $response = new WP_Error(
                'rest_invalid_param',
                "Redirect type {$rule_type} does not accept any status codes",
                [ 'status'  => 400 ]
            );
        } elseif (is_array($allowed)) {
            $list = array();

            foreach($allowed as $range) {
                $list = array_merge(
                    $list,
                    range(
                        str_replace('xx', '00', $range),
                        str_replace('xx', '99', $range)
                    )
                );
            }

            if (!in_array($status_code, $list, true)) {
                $allowed = implode(', ', $allowed);

                $response = new WP_Error(
                    'rest_invalid_param',
                    "For redirect type {$rule_type} allowed status codes are {$allowed}",
                    [ 'status'  => 400 ]
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
     * @version 6.9.14
     */
    private function _validate_redirect_url($value, $request)
    {
        $response  = true;
        $rule_type = $request->get_param('type');
        $url       = wp_validate_redirect($value);

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
     *
     * @access private
     * @version 6.9.14
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
     *
     * @access private
     * @version 6.9.14
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
     * Get Access Denied Redirect framework service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_AccessDeniedRedirect
     *
     * @access private
     * @version 6.9.33
     */
    private function _get_service($request)
    {
        return AAM_Framework_Manager::access_denied_redirect([
            'subject'        => $this->_determine_subject($request),
            'error_handling' => 'exception'
        ]);
    }

}