<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for support service
 *
 * @since 6.9.36 https://github.com/aamplugin/advanced-access-manager/issues/410
 * @since 6.9.15 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.36
 */
class AAM_Restful_SupportService
{

    use AAM_Restful_ServiceTrait;

    /**
     * The namespace for the collection of endpoints
     */
    const API_NAMESPACE = 'aam/v2';

    /**
     * Single instance of itself
     *
     * @var AAM_Restful_SupportService
     *
     * @access private
     * @static
     */
    private static $_instance = null;

    /**
     * Constructor
     *
     * @return void
     *
     * @since 6.9.36 https://github.com/aamplugin/advanced-access-manager/issues/410
     * @since 6.9.15 Initial implementation of the method
     *
     * @access protected
     * @version 6.9.36
     */
    protected function __construct()
    {
        // Register API endpoint
        add_action('rest_api_init', function() {
            // Create new support message
            $this->_register_route('/support', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'create_support_request'),
                'permission_callback' => function () {
                    return current_user_can('aam_manager')
                        && current_user_can('aam_create_support_request');
                },
                'args' => array(
                    'fullname' => array(
                        'description' => 'Customer name',
                        'type'        => 'string',
                        'default'     => 'No Name',
                        'required'    => false
                    ),
                    'email' => array(
                        'description' => 'Customer email',
                        'type'        => 'string',
                        'default'     => 'notprovided@domain.xyz',
                        'validate_callback' => function($value) {
                            return $this->_validate_email($value);
                        }
                    ),
                    'message' => array(
                        'description' => 'Support message',
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_message($value);
                        }
                    )
                )
            ));
        });
    }

    /**
     * Create new support message
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @version 6.9.15
     */
    public function create_support_request(WP_REST_Request $request)
    {
        $response = array();

        $fullname = $request->get_param('fullname');
        $email    = filter_var($request->get_param('email'), FILTER_VALIDATE_EMAIL);
        $message  = $request->get_param('message');

        try {
            $result = wp_remote_post(
                AAM_Core_API::getAPIEndpoint() . '/contact',
                array(
                    'headers' => array(
                        'Content-Type: application/json'
                    ),
                    'body' => array(
                        'nickname' => $fullname,
                        'email'    => $email,
                        'message'  => $message
                    )
                )
            );

            if (!is_wp_error($result)) {
                $response = array('status' => 'OK');
            } else {
                throw new Exception($result->get_error_message());
            }

        } catch (Exception $ex) {
            $response = $this->_prepare_error_response($ex);
        }

        return rest_ensure_response($response);
    }

    /**
     * Register new RESTful route
     *
     * The method also applies the `aam_rest_route_args_filter` filter that allows
     * other processes to change the router definition
     *
     * @param string $route
     * @param array  $args
     *
     * @return void
     *
     * @access private
     * @version 6.9.15
     */
    private function _register_route($route, $args)
    {
        register_rest_route(
            self::API_NAMESPACE,
            $route,
            apply_filters(
                'aam_rest_route_args_filter', $args, $route, self::API_NAMESPACE
            )
        );
    }

    /**
     * Validate the input field "message"
     *
     * @param string|null $value Input value
     *
     * @return bool|WP_Error
     *
     * @access private
     * @version 6.9.15
     */
    private function _validate_message($value)
    {
        $response = true;
        $message  = trim($value);

        if (strlen($message) === 0) {
            $response = new WP_Error(
                'rest_invalid_param',
                'The message field is required and cannot be empty',
                array('status'  => 400)
            );
        }

        return $response;
    }

    /**
     * Validate the input field "email"
     *
     * @param string|null $value Input value
     *
     * @return bool|WP_Error
     *
     * @access private
     * @version 6.9.15
     */
    private function _validate_email($value)
    {
        $response = true;
        $email    = filter_var($value, FILTER_VALIDATE_EMAIL);

        if (!$email) {
            $response = new WP_Error(
                'rest_invalid_param',
                'The valid email address is required.',
                array('status'  => 400)
            );
        }

        return $response;
    }

}