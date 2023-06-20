<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for the Logout Redirect service
 *
 * @package AAM
 * @version 6.9.12
 */
class AAM_Core_Restful_LogoutRedirectService
{

    use AAM_Core_Restful_ServiceTrait;

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 6.9.12
     */
    protected function __construct()
    {
        // Register API endpoint
        add_action('rest_api_init', function() {
            // Get current redirect
            $this->_register_route('/redirect/logout', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_redirect'),
                'permission_callback' => array($this, 'check_permissions'),
                'args' => array()
            ));

            // Create a redirect rule
            $this->_register_route('/redirect/logout', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'set_redirect'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'type' => array(
                        'description' => __('Rule type', AAM_KEY),
                        'type'        => 'string',
                        'required'    => true,
                        'enum'        => array_values(
                            AAM_Framework_Service_RedirectAbstract::REDIRECT_TYPE_ALIAS
                        )
                    ),
                    'redirect_page_id' => array(
                        'description' => __('Existing page ID to redirect to', AAM_KEY),
                        'type'        => 'number',
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

            // Delete the redirect rule
            $this->_register_route('/redirect/logout', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'reset_redirect'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array()
            ));
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
     * @version 6.9.12
     */
    public function get_redirect(WP_REST_Request $request)
    {
        $service = AAM_Framework_Manager::logout_redirect(
            new AAM_Framework_Model_ServiceContext(array(
                'subject' => $this->_determine_subject($request)
            ))
        );

        return rest_ensure_response($service->get_redirect());
    }

    /**
     * Set the logout redirect
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.12
     */
    public function set_redirect(WP_REST_Request $request)
    {
        $service = AAM_Framework_Manager::logout_redirect(
            new AAM_Framework_Model_ServiceContext(array(
                'subject' => $this->_determine_subject($request)
            ))
        );

        try {
            $result = $service->set_redirect($request->get_params());
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
     * @version 6.9.12
     */
    public function reset_redirect(WP_REST_Request $request)
    {
        $response = array('success' => true);

        $service = AAM_Framework_Manager::logout_redirect(array(
            'subject' => $this->_determine_subject($request)
        ));

        try {
            $service->reset_redirect();
        } catch (Exception $e) {
            $response = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($response);
    }

    /**
     * Check if current user has access to the service
     *
     * @return bool
     *
     * @access public
     * @version 6.9.12
     */
    public function check_permissions()
    {
        return current_user_can('aam_manager')
            && current_user_can('aam_manage_logout_redirect');
    }

    /**
     * Validate the 'url' param
     *
     * @param string $value
     *
     * @return boolean|WP_Error
     *
     * @access private
     * @version 6.9.12
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
     * Validate redirect page ID
     *
     * @param int             $value
     * @param WP_REST_Request $request
     *
     * @return boolean|WP_Error
     *
     * @access private
     * @version 6.9.12
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
     * @version 6.9.12
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
     * @version 6.9.12
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