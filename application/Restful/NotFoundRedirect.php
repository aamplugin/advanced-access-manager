<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for the 404 Redirect service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Restful_NotFoundRedirect
{

    use AAM_Restful_ServiceTrait;

    /**
     * Necessary permissions to access endpoint
     *
     * @version 7.0.0
     */
    const PERMISSIONS = [
        'aam_manager',
        'aam_manage_404_redirect'
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
            // Get current redirect
            $this->_register_route('/redirect/not-found', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_redirect')
            ], self::PERMISSIONS);

            // Create a redirect rule
            $this->_register_route('/redirect/not-found', [
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'set_redirect'),
                'args'     => array(
                    'type' => array(
                        'description' => 'Redirect type',
                        'type'        => 'string',
                        'required'    => true,
                        'enum'        => AAM_Framework_Service_NotFoundRedirect::ALLOWED_REDIRECT_TYPES
                    ),
                    'redirect_page_id' => array(
                        'description' => 'Existing page ID to redirect to',
                        'type'        => 'number',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_redirect_page_id($value, $request);
                        }
                    ),
                    'redirect_url' => array(
                        'description' => 'Valid URL to redirect to',
                        'type'        => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_redirect_url($value, $request);
                        }
                    ),
                    'http_status_code' => array(
                        'description' => 'HTTP Status Code',
                        'type'        => 'number'
                    ),
                    'callback' => array(
                        'description' => 'Custom callback function',
                        'type'        => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_callback($value, $request);
                        }
                    )
                )
            ], self::PERMISSIONS);

            // Delete the redirect rule
            $this->_register_route('/redirect/not-found', [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'reset_redirect')
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
            $result  = $service->get_redirect();
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Set the 404 redirect
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
            $result  = $service->set_redirect($request->get_params());
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
            $result  = [ 'success' => $service->reset() ];
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
                'The url is not a valid URL',
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

        if ($rule_type === 'trigger_callback' && is_callable($value, true) === false) {
            $response = new WP_Error(
                'rest_invalid_param',
                'The callback is not valid PHP callback',
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
     * @return AAM_Framework_Service_NotFoundRedirect
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_service(WP_REST_Request $request)
    {
        return AAM::api()->not_found_redirect(
            $this->_determine_access_level($request),
            [ 'error_handling' => 'exception' ]
        );
    }

}