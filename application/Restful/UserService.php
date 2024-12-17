<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for user management
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Restful_UserService
{

    use AAM_Restful_ServiceTrait;

    /**
     * The namespace for the collection of endpoints
     *
     * @version 7.0.0
     */
    const API_NAMESPACE = 'aam/v2';

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
            // Get the list of users
            $this->_register_route('/service/users', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_user_list'),
                'permission_callback' => [ $this, 'check_permissions' ],
                'args'                => array(
                    'fields' => array(
                        'description' => 'List of additional fields to return',
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_fields_input($value);
                        }
                    ),
                    'search' => array(
                        'description' => 'Search string',
                        'type'        => 'string'
                    ),
                    'offset' => array(
                        'description' => 'Pagination offset',
                        'type'        => 'number',
                        'default'     => 0
                    ),
                    'per_page' => array(
                        'description' => 'Pagination limit per page',
                        'type'        => 'number',
                        'default'     => 10
                    ),
                    'role'   => array(
                        'description' => 'Return users only for given role',
                        'type'        => 'string'
                    )
                )
            ));

            // Get a specific user
            $this->_register_route('/service/user/(?P<id>[\d]+)', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_user'),
                'permission_callback' => [ $this, 'check_permissions' ],
                'args' => array(
                    'id'   => array(
                        'description' => 'Unique user id',
                        'type'        => 'number',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_user_accessibility(
                                $value, $request->get_method()
                            );
                        }
                    ),
                    'fields' => array(
                        'description' => 'List of additional fields to return',
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_fields_input($value);
                        }
                    )
                )
            ));

            // Update existing user
            $this->_register_route('/service/user/(?P<id>[\d]+)', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_user'),
                'permission_callback' => [ $this, 'check_permissions' ],
                'args'                => array(
                    'id'   => array(
                        'description' => 'Unique user id',
                        'type'        => 'number',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_user_accessibility(
                                $value, $request->get_method()
                            );
                        }
                    ),
                    'status' => array(
                        'description' => 'User status',
                        'type'        => 'string',
                        'enum'        => [
                            'active',
                            'inactive'
                        ]
                    ),
                    'expiration' => array(
                        'description' => 'User access expiration date-time & trigger',
                        'type'        => 'object',
                        'properties'  => [
                            'expires_at' => [
                                'type'     => 'string',
                                'format'   => 'date-time',
                                'required' => true
                            ],
                            'trigger'    => [
                                'type'     => ['string', 'object'],
                                'required' => true,
                                'default'  => 'logout',
                                'properties' => [
                                    'type' => [
                                        'type'     => 'string',
                                        'required' => true,
                                        'enum'     => AAM_Framework_Proxy_User::ALLOWED_EXPIRATION_TRIGGERS
                                    ],
                                    'role' => [
                                        'type'              => 'string',
                                        'validate_callback' => function ($value) {
                                            return $this->_validate_role_accessibility(
                                                $value
                                            );
                                        }
                                    ]
                                ]
                            ]
                        ]
                    ),
                    'add_capabilities' => array(
                        'description' => 'List of capabilities to assign',
                        'type'        => 'array',
                        'items'       => array(
                            'type'    => 'string'
                        )
                    ),
                    'deprive_capabilities' => array(
                        'description' => 'List of capabilities to deprive',
                        'type'        => 'array',
                        'items'       => [
                            'type'    => 'string'
                        ]
                    ),
                    'remove_capabilities' => array(
                        'description' => 'List of capabilities to remove',
                        'type'        => 'array',
                        'items'       => array(
                            'type'    => 'string'
                        )
                    ),
                    'fields' => array(
                        'description' => 'List of additional fields to return',
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_fields_input($value);
                        }
                    )
                )
            ));

            // Reset existing user settings
            $this->_register_route('/service/user/(?P<id>[\d]+)', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'reset_user'),
                'permission_callback' => [ $this, 'check_permissions' ],
                'args'                => array(
                    'id'   => array(
                        'description' => 'Unique user id',
                        'type'        => 'number',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_user_accessibility(
                                $value, $request->get_method()
                            );
                        }
                    ),
                    'fields' => array(
                        'description' => 'List of additional fields to return',
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_fields_input($value);
                        }
                    )
                )
            ));
        });
    }

    /**
     * Get a paginated list of users
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function get_user_list(WP_REST_Request $request)
    {
        try {
            // Prepare the list of filters
            $filters = [
                'number'   => $request->get_param('per_page'),
                'search'   => $request->get_param('search'),
                'offset'   => $request->get_param('offset')
            ];

            $role_filter = $request->get_param('role');

            if (!empty($role_filter)) {
                $filters['role__in'] = $role_filter;
            }

            // Modify the search, if not empty
            if (!empty($filters['search'])) {
                $filters['search'] .= '*';
            }

            // Iterate over the list of all users and enrich it with additional
            // attributes
            $user_data = AAM::api()->users->list($filters, 'full');
            $fields    = $this->_determine_additional_fields($request);
            $result    = [ 'list' => [], 'summary' => $user_data['summary'] ];

            foreach($user_data['list'] as $user) {
                array_push($result['list'], $this->_prepare_output($user, $fields));
            }
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get a user
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function get_user(WP_REST_Request $request)
    {
        try {
            $result = $this->_prepare_output(
                AAM::api()->users->user($request->get_param('id')),
                $this->_determine_additional_fields($request)
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Update user
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function update_user(WP_REST_Request $request)
    {
        try {
            $expiration   = $request->get_param('expiration');
            $status       = $request->get_param('status');
            $add_caps     = $request->get_param('add_capabilities');
            $remove_caps  = $request->get_param('remove_capabilities');
            $deprive_caps = $request->get_param('deprive_capabilities');
            $data         = [];

            if (!empty($expiration)) {
                $data['expiration'] = $expiration;
            }

            if (!empty($status)) {
                $data['status'] = $status;
            }

            if (!empty($add_caps)) {
                $data['add_caps'] = $add_caps;
            }

            if (!empty($remove_caps)) {
                $data['remove_caps'] = $remove_caps;
            }

            if (!empty($deprive_caps)) {
                $data['deprive_caps'] = $deprive_caps;
            }

            $user = AAM::api()->users->user($request->get_param('id'));

            // Update user data
            $user->update($data);

            $result = $this->_prepare_output(
                $user, $this->_determine_additional_fields($request)
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset user
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function reset_user(WP_REST_Request $request)
    {
        try {
            $user = AAM::api()->users->user($request->get_param('id'));

            // Reset user
            $user->reset();

            $result = $this->_prepare_output(
                $user, $this->_determine_additional_fields($request)
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Check if current user has access to the service
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function check_permissions()
    {
        return current_user_can('aam_manager')
            && current_user_can('aam_manage_users');
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
                    sprintf(
                        __('Invalid fields: %s'),
                        implode(', ', $invalid_fields)
                    ),
                    array('status'  => 400)
                );
            }
        }

        return $response;
    }

    /**
     * Prepare user data
     *
     * @param AAM_Framework_AccessLevel_User $user
     * @param array                          $fields
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_output($user, $fields = [])
    {
        $response = [
            'id'                    => $user->ID,
            'user_login'            => $user->user_login,
            'display_name'          => $user->display_name,
            'user_level'            => intval($user->user_level),
            'roles'                 => $this->_prepare_user_roles($user->roles),
            'assigned_capabilities' => $user->caps,
            'all_capabilities'      => $user->allcaps,
            'status'                => $user->status
        ];

        $expires_at = $user->expires_at;

        if (!empty($expires_at)) {
            $response['expiration'] = [
                'expires_at'           => $expires_at->format(DateTime::RFC3339),
                'expires_at_timestamp' => $expires_at->getTimestamp(),
                'trigger'              => $user->expiration_trigger
            ];
        }

        // Addition list of actions that current user can perform upon given user
        $response['permissions'] = [];

        if (current_user_can('edit_user', $response['id'])) {
            array_push($response['permissions'], 'allow_manage', 'allow_edit');

            if(current_user_can('aam_toggle_users')) {
                array_push(
                    $response['permissions'],
                    $response['status'] === 'inactive' ? 'allow_unlock' : 'allow_lock'
                );
            }
        }

        $response = apply_filters('aam_prepare_user_item_filter', $response);

        // Finally, return only fields that were requested
        foreach($fields as $field) {
            if (!isset($response[$field]) && isset($item[$field])) {
                $response[$field] = $item[$field];
            }
        }

        return $response;
    }

    /**
     * Prepare list of roles
     *
     * @param array $roles
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_user_roles($roles)
    {
        $response = [];

        $names = wp_roles()->get_names();

        if (is_array($roles)) {
            foreach ($roles as $role) {
                if (array_key_exists($role, $names)) {
                    $response[] = translate_user_role($names[$role]);
                }
            }
        }

        return $response;
    }

    /**
     * Determine list of additional fields to return
     *
     * @param WP_REST_Request $request
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _determine_additional_fields(WP_REST_Request $request)
    {
        $fields = $request->get_param('fields');

        if (!empty($fields) && is_string($fields)) {
            $fields = explode(',', $fields);
        } else {
            $fields = array();
        }

        return $fields;
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
     * @access private
     *
     * @version 7.0.0
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

}