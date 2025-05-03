<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for the Users & Roles (aka Identity) Governance service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Restful_Identity
{

    use AAM_Restful_ServiceTrait;

    /**
     * Necessary permissions to access endpoint
     *
     * @version 7.0.0
     */
    const PERMISSIONS = [
        'aam_manager',
        'aam_manage_identities'
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
            // Define some common properties
            $effect = [
                'type'    => 'string',
                'default' => 'deny',
                'enum'    => [ 'allow', 'deny' ]
            ];

            $permission = [
                'type'     => 'string',
                'required' => true
            ];

            $permissions = [
                'description' => 'Collection of permissions',
                'type'        => 'array',
                'required'    => true,
                'items'       => [
                    'type' => 'object',
                    'properties' => [
                        'permission' => $permission,
                        'effect'     => $effect
                    ]
                ]
            ];

            $role_slug = [
                'description' => 'Role slug',
                'type'        => 'string',
                'required'    => true
            ];

            $user_id = [
                'description' => 'User id',
                'type'        => [ 'number', 'string' ],
                'required'    => true
            ];

            // Get the list of roles with permissions
            $this->_register_route('/identity/roles', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_roles' ]
            ], self::PERMISSIONS);

            // Get a specific role with permissions
            $this->_register_route('/identity/role/(?P<slug>.+)', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_role' ],
                'args'     => [
                    'slug' => $role_slug
                ]
            ], self::PERMISSIONS);

            // Set role identity permissions
            $this->_register_route('/identity/role/(?P<slug>[^/]+)', [
                'methods'  => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'set_role_permissions'),
                'args'     => [
                    'slug'        => $role_slug,
                    'permissions' => $permissions
                ]
            ], self::PERMISSIONS);

            // Set a single permission for a role identity
            $this->_register_route('/identity/role/(?P<slug>.+)/(?P<permission>[\w\-]+)', [
                'methods'  => WP_REST_Server::EDITABLE,
                'callback' => [ $this, 'set_role_permission' ],
                'args'     => [
                    'slug'       => $role_slug,
                    'permission' => $permission,
                    'effect'     => $effect
                ]
            ], self::PERMISSIONS);

            // Reset permissions for all roles
            $this->_register_route('/identity/roles', [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => [ $this, 'reset_roles_permissions' ]
            ], self::PERMISSIONS);

            // Reset specific role permissions
            $this->_register_route('/identity/role/(?P<slug>.+)', [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => [ $this, 'reset_role_permissions' ],
                'args'     => [
                    'slug' => $role_slug
                ]
            ], self::PERMISSIONS);

            // Get the list of user identities
            $this->_register_route('/identity/users', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_users' ],
                'args'     => [
                    'search' => [
                        'description' => 'Search string',
                        'type'        => 'string'
                    ],
                    'offset' => [
                        'description' => 'Pagination offset',
                        'type'        => 'number',
                        'default'     => 0
                    ],
                    'per_page' => [
                        'description' => 'Pagination limit per page',
                        'type'        => 'number',
                        'default'     => 10
                    ],
                    'role'   => [
                        'description' => 'Return users only for given role',
                        'type'        => 'string'
                    ]
                ]
            ], self::PERMISSIONS);

            // Get a specific user with permissions
            $this->_register_route('/identity/user/(?P<id>[\d]+)', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_user' ],
                'args'     => [
                    'id' => $user_id
                ]
            ], self::PERMISSIONS);

            // Set user identity permissions
            $this->_register_route('/identity/user/(?P<id>[\d]+)', [
                'methods'  => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'set_user_permissions'),
                'args'     => [
                    'id'          => $user_id,
                    'permissions' => $permissions
                ]
            ], self::PERMISSIONS);

            // Set a single permission for a user identity
            $this->_register_route('/identity/user/(?P<id>[\d]+)/(?P<permission>[\w\-]+)', [
                'methods'  => WP_REST_Server::EDITABLE,
                'callback' => [ $this, 'set_user_permission' ],
                'args'     => [
                    'id'         => $user_id,
                    'permission' => $permission,
                    'effect'     => $effect
                ]
            ], self::PERMISSIONS);

            // Reset permissions for all users
            $this->_register_route('/identity/users', [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => [ $this, 'reset_users_permissions' ]
            ], self::PERMISSIONS);

            // Reset specific user permissions
            $this->_register_route('/identity/user/(?P<id>[\d]+)', [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => [ $this, 'reset_user_permissions' ],
                'args'     => [
                    'id' => $user_id
                ]
            ], self::PERMISSIONS);
        });
    }

    /**
     * Get list of roles with permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function get_roles(WP_REST_Request $request)
    {
        try {
            $result = [];

            foreach(AAM::api()->roles->get_editable_roles() as $role) {
                array_push($result, $this->_prepare_role_output(
                    $role, $this->_determine_access_level($request)
                ));
            }
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get a specific role with permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function get_role(WP_REST_Request $request)
    {
        try {
            $result  = $this->_prepare_role_output(
                AAM::api()->roles->get_role($request->get_param('slug')),
                $this->_determine_access_level($request)
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Set a single role permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function set_role_permissions(WP_REST_Request $request)
    {
        try {
            $role         = AAM::api()->roles->get_role($request->get_param('slug'));
            $access_level = $this->_determine_access_level($request);

            $this->_set_identity_permissions(
                $role,
                $request->get_param('permissions'),
                $access_level
            );

            $result = $this->_prepare_role_output($role, $access_level);
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Set role single permission
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function set_role_permission(WP_REST_Request $request)
    {
        try {
            $role         = AAM::api()->roles->get_role($request->get_param('slug'));
            $access_level = $this->_determine_access_level($request);

            $result = $this->_set_identity_permission(
                $role,
                $request->get_param('permission'),
                $request->get_param('effect'),
                $access_level
            );

            return $this->_prepare_role_output($role, $access_level);
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset permissions for all roles
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function reset_roles_permissions(WP_REST_Request $request)
    {
        try {
            $result = [
                'success' => AAM::api()->roles(
                    $this->_determine_access_level($request)
                )->reset()
            ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset permissions for a single role
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function reset_role_permissions(WP_REST_Request $request)
    {
        try {
            $result = [
                'success' => AAM::api()->roles(
                    $this->_determine_access_level($request)
                )->reset($request->get_param('slug'))
            ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get paginated list of users with permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function get_users(WP_REST_Request $request)
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
            $result = [
                'list' => [],
                'summary' =>  [
                    'total_count'    => AAM::api()->users->get_user_count(),
                    'filtered_count' => AAM::api()->users->get_user_count($filters)
                ]
            ];

            $access_level = $this->_determine_access_level($request);

            foreach(AAM::api()->users->get_users($filters) as $user) {
                array_push($result['list'], $this->_prepare_user_output(
                    $user, $access_level
                ));
            }
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get a specific user with permissions
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
            $result  = $this->_prepare_user_output(
                AAM::api()->users->get_user($request->get_param('id')),
                $this->_determine_access_level($request)
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Set a single user permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function set_user_permissions(WP_REST_Request $request)
    {
        try {
            $user         = AAM::api()->users->get_user($request->get_param('id'));
            $access_level = $this->_determine_access_level($request);

            $this->_set_identity_permissions(
                $user,
                $request->get_param('permissions'),
                $access_level
            );

            $result = $this->_prepare_user_output($user, $access_level);
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Set user single permission
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function set_user_permission(WP_REST_Request $request)
    {
        try {
            $user         = AAM::api()->users->get_user($request->get_param('id'));
            $access_level = $this->_determine_access_level($request);

            $this->_set_identity_permission(
                $user,
                $request->get_param('permission'),
                $request->get_param('effect'),
                $access_level
            );

            $result = $this->_prepare_user_output($user, $access_level);
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset permissions for all users
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function reset_users_permissions(WP_REST_Request $request)
    {
        try {
            $result = [
                'success' => AAM::api()->users(
                    $this->_determine_access_level($request)
                )->reset()
            ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset permissions for a single user
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function reset_user_permissions(WP_REST_Request $request)
    {
        try {
            $result = [
                'success' => AAM::api()->users(
                    $this->_determine_access_level($request)
                )->reset($request->get_param('id'))
            ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Prepare the role output
     *
     * @param AAM_Framework_Proxy_Role            $role
     * @param AAM_Framework_AccessLevel_Interface $access_level
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_role_output($role, $access_level)
    {
        $resource = $access_level->get_resource(
            AAM_Framework_Type_Resource::ROLE
        );

        return [
            'id'            => $role->slug,
            'name'          => $role->display_name,
            'permissions'   => $resource->get_permissions($role->get_core_instance()),
            'is_customized' => $resource->is_customized($role->get_core_instance())
        ];
    }

    /**
     * Prepare the user output
     *
     * @param AAM_Framework_Proxy_User            $user
     * @param AAM_Framework_AccessLevel_Interface $access_level
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_user_output($user, $access_level)
    {
        $resource = $access_level->get_resource(
            AAM_Framework_Type_Resource::USER
        );

        // Prepare user's display name
        if ($user->first_name && $user->last_name) {
            $display_name = sprintf('%s %s', $user->first_name, $user->last_name);
        } elseif ( $user->first_name ) {
            $display_name = $user->first_name;
        } elseif ( $user->last_name ) {
            $display_name = $user->last_name;
        } else {
            $display_name = $user->user_email;
        }

        return [
            'id'            => $user->ID,
            'display_name'  => $display_name,
            'permissions'   => $resource->get_permissions($user->get_core_instance()),
            'is_customized' => $resource->is_customized($user->get_core_instance())
        ];
    }

    /**
     * Set identity permissions
     *
     * @param AAM_Framework_Proxy_Interface       $identity
     * @param array                               $permissions
     * @param AAM_Framework_AccessLevel_Interface $access_level
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _set_identity_permissions(
        $identity, $permissions, $access_level
    ) {
        // Prepare proper resource
        if (is_a($identity, AAM_Framework_Proxy_Role::class)) {
            $resource = $access_level->get_resource(
                AAM_Framework_Type_Resource::ROLE
            );
        } else {
            $resource = $access_level->get_resource(
                AAM_Framework_Type_Resource::USER
            );
        }

        $normalized = [];

        // Normalize the array of permissions
        foreach($permissions as $permission) {
            $normalized[$permission['permission']] = [
                'effect' => $permission['effect']
            ];
        }

        return $resource->set_permissions(
            $normalized,
            $identity->get_core_instance()
        );
    }

    /**
     * Set a single permission for a given identity
     *
     * @param AAM_Framework_Proxy_Interface       $identity
     * @param string                              $permission
     * @param string                              $effect
     * @param AAM_Framework_AccessLevel_Interface $access_level
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _set_identity_permission(
        $identity, $permission, $effect, $access_level
    ) {
        // Prepare proper resource
        if (is_a($identity, AAM_Framework_Proxy_Role::class)) {
            $resource = $access_level->get_resource(
                AAM_Framework_Type_Resource::ROLE
            );
        } else {
            $resource = $access_level->get_resource(
                AAM_Framework_Type_Resource::USER
            );
        }

        return $resource->set_permission($identity, $permission, $effect);
    }

}