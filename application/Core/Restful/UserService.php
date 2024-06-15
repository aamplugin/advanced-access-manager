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
 * @version 6.9.32
 */
class AAM_Core_Restful_UserService
{

    use AAM_Core_Restful_ServiceTrait;

    /**
     * The namespace for the collection of endpoints
     *
     * @version 6.9.32
     */
    const API_NAMESPACE = 'aam/v2';

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 6.9.32
     */
    protected function __construct()
    {
        // Register API endpoint
        add_action('rest_api_init', function() {
            // Get the list of users
            $this->_register_route('/users', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_user_list'),
                'permission_callback' => function () {
                    return current_user_can('aam_manager')
                        && current_user_can('aam_manage_users');
                },
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
            $this->_register_route('/user/(?<id>[\d]+)', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_user'),
                'permission_callback' => function () {
                    return current_user_can('aam_manager')
                        && current_user_can('aam_manage_users');
                },
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
            $this->_register_route('/user/(?<id>[\d]+)', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_user'),
                'permission_callback' => function () {
                    return current_user_can('aam_manager')
                        && current_user_can('aam_edit_users');
                },
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
            $this->_register_route('/user/(?<id>[\d]+)/settings', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'reset_user'),
                'permission_callback' => function () {
                    return current_user_can('aam_manager')
                        && current_user_can('aam_edit_users');
                },
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
     * @version 6.9.32
     */
    public function get_user_list(WP_REST_Request $request)
    {
        $service = AAM_Framework_Manager::users(
            new AAM_Framework_Model_ServiceContext()
        );

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
        $response = $service->get_users($filters);
        $fields   = $this->_determine_additional_fields($request);

        foreach($response['list'] as &$user) {
            $user = $this->_prepare_user_item($user, $fields);
        }

        return rest_ensure_response($response);
    }

    /**
     * Get a user
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @version 6.9.32
     */
    public function get_user(WP_REST_Request $request)
    {
        $service = AAM_Framework_Manager::users(
            new AAM_Framework_Model_ServiceContext()
        );

        try {
            $response = $this->_prepare_user_item(
                $service->get_user($request->get_param('id'), false),
                $this->_determine_additional_fields($request)
            );
        } catch (DomainException $e) {
            $response = new WP_Error(
                'rest_not_found',
                $e->getMessage(),
                array('status'  => 404)
            );
        } catch (Exception $e) {
            $response = new WP_Error(
                'rest_invalid_param',
                $e->getMessage(),
                array('status'  => 400)
            );
        }

        return rest_ensure_response($response);
    }

    /**
     * Update user
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @version 6.9.32
     */
    public function update_user(WP_REST_Request $request)
    {
        $service = AAM_Framework_Manager::users(
            new AAM_Framework_Model_ServiceContext()
        );

        $expiration = $request->get_param('expiration');
        $status     = $request->get_param('status');

        $data = [];

        if (!empty($expiration)) {
            $data['expires_at'] = $expiration['expires_at'];
            $data['trigger']    = $expiration['trigger'];
        }

        if (!empty($status)) {
            $data['status'] = $status;
        }

        try {
            $response = $this->_prepare_user_item(
                $service->update_user($request->get_param('id'), $data, false),
                $this->_determine_additional_fields($request)
            );
        } catch (DomainException $e) {
            $response = new WP_Error(
                'rest_not_found',
                $e->getMessage(),
                array('status'  => 404)
            );
        } catch (Exception $e) {
            $response = new WP_Error(
                'rest_invalid_param',
                $e->getMessage(),
                array('status'  => 400)
            );
        }

        return rest_ensure_response($response);
    }

    /**
     * Reset user
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @version 6.9.32
     */
    public function reset_user(WP_REST_Request $request)
    {
        $service = AAM_Framework_Manager::users(
            new AAM_Framework_Model_ServiceContext()
        );

        try {
            $response = $this->_prepare_user_item(
                $service->reset_user($request->get_param('id'), false),
                $this->_determine_additional_fields($request)
            );
        } catch (DomainException $e) {
            $response = new WP_Error(
                'rest_not_found',
                $e->getMessage(),
                array('status'  => 404)
            );
        } catch (Exception $e) {
            $response = new WP_Error(
                'rest_invalid_param',
                $e->getMessage(),
                array('status'  => 400)
            );
        }

        return rest_ensure_response($response);
    }

    /**
     * Validate the input field "fields"
     *
     * @param string|null $value Input value
     *
     * @return bool|WP_Error
     *
     * @access private
     * @version 6.9.32
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
     * Prepare user item
     *
     * @param array $item
     * @param array $fields
     *
     * @return array
     *
     * @access private
     * @version 6.9.32
     */
    private function _prepare_user_item($item, $fields = [])
    {
        // Addition list of actions that current user can perform upon given user
        $item['permissions'] = [];

        if (current_user_can('edit_user', $item['id'])) {
            array_push($item['permissions'], 'allow_manage', 'allow_edit');
        }

        $item = apply_filters('aam_prepare_user_item_filter', $item);

        // Finally, return only fields that were requested
        $response = apply_filters('aam_user_rest_field_filter', [
            'id' => $item['id']
        ], $item, $fields);

        foreach($fields as $field) {
            if (!isset($response[$field]) && isset($item[$field])) {
                $response[$field] = $item[$field];
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
     *
     * @access private
     * @version 6.9.32
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
     *
     * @access private
     * @version 6.9.32
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