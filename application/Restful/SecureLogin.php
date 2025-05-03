<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for the Secure Login service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Restful_SecureLogin
{

    use AAM_Restful_ServiceTrait;

    /**
     * Necessary permissions to access endpoint
     *
     * @version 7.0.0
     */
    const PERMISSIONS = [
        'aam_manager',
        'aam_manage_admin_toolbar'
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
            // Create a redirect rule
            $this->_register_route('/authenticate', [
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'authenticate'),
                'args'     => array(
                        'username' => array(
                        'description' => 'Valid username',
                        'type'        => 'string',
                        'required'    => true
                    ),
                    'password' => array(
                        'description' => 'Valid password',
                        'type'        => 'string',
                        'required'    => true
                    ),
                    'remember' => array(
                        'description' => 'Prolong the user session.',
                        'type'        => 'boolean',
                        'default'     => false
                    ),
                    'return_auth_cookies' => array(
                        'description' => 'Return auth cookies.',
                        'type'        => 'boolean',
                        'default'     => false
                    ),
                    'fields' => array(
                        'description' => 'List of additional fields to return',
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_fields_input($value);
                        }
                    )
                )
            ], function() { return !is_user_logged_in(); }, false);
        });
    }

    /**
     * Authenticate user
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function authenticate(WP_REST_Request $request)
    {
        try {
            // No need to generate Auth cookies, unless explicitly stated so
            if ($request->get_param('return_auth_cookies') !== true) {
                add_filter('send_auth_cookies', '__return_false');
            }

            $user = wp_signon(array(
                'user_login'    => $request->get_param('username'),
                'user_password' => $request->get_param('password'),
                'remember'      => $request->get_param('remember')
            ));

            if (!is_wp_error($user)) {
                $result = $this->_prepare_user_data($user, $request);
            } else {
                throw new DomainException($user->get_error_message());
            }
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Prepare user data that is returned
     *
     * @param WP_User         $user
     * @param WP_REST_Request $request
     *
     * @return array
     * @access protected
     *
     * @version 7.0.0
     */
    private function _prepare_user_data($user, $request)
    {
        $response = [];
        $fields   = $request->get_param('fields');
        $props    = array_unique(array_merge(
            [ 'ID' ], is_string($fields) ? explode(',', $fields) : []
        ));

        foreach($props as $prop) {
            if (isset($user->{$prop})) {
                $response[$prop] = $user->{$prop};
            }
        }

        return apply_filters(
            'aam_rest_authenticated_user_data_filter', $response, $request, $user
        );
    }

    /**
     * Validate the input field "fields"
     *
     * @param string|null $value Input value
     *
     * @return bool|WP_Error
     * @access private
     *
     * @version 7.0.0
     */
    private function _validate_fields_input($value)
    {
        $response = true;

        if (is_string($value) && strlen($value) > 0) {
            $invalid_fields = [];

            foreach(explode(',', $value) as $field) {
                if (strlen(sanitize_key($field)) !== strlen($field)) {
                    $invalid_fields[] = $field;
                }
            }

            if (count($invalid_fields) > 0) {
                $response = new WP_Error(
                    'rest_invalid_param',
                    sprintf('Invalid fields: %s', implode(', ', $invalid_fields)),
                    array('status'  => 400)
                );
            }
        }

        return $response;
    }

}