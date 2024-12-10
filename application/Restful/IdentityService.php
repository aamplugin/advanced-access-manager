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
class AAM_Restful_IdentityService
{

    use AAM_Restful_ServiceTrait;

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
            // Get the list of roles with permissions
            $this->_register_route('/identity/roles', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_role_list'),
                'permission_callback' => array($this, 'check_permissions')
            ));

            // Get a specific role with permissions
            $this->_register_route('/identity/role/(?P<slug>.+)', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_role'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => [
                    'slug' => array(
                        'description' => 'Role slug',
                        'type'        => 'string',
                        'required'    => true
                    )
                ]
            ));

            // Get the list of users with permissions
            $this->_register_route('/identity/users', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_user_list'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => [
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
                ]
            ));

            // Get a specific role with permissions
            $this->_register_route('/identity/user/(?P<id>[\d]+)', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_user'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => [
                    'id' => array(
                        'description' => 'User id',
                        'type'        => 'number',
                        'required'    => true
                    )
                ]
            ));

            // Set identity permissions
            $this->_register_route('/identity/(?P<type>[\w]+)/(?P<id>[^/]+)', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_identity_permissions'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => [
                    'type' => [
                        'description' => 'Identity type: either role or user',
                        'type'        => 'string',
                        'required'    => true,
                        'enum'        => [ 'role', 'user' ]
                    ],
                    'id' => [
                        'description' => 'Identity identifier',
                        'type'        => [ 'string', 'number' ],
                        'required'    => true
                    ],
                    'permissions' => array(
                        'description' => 'Collection of permissions',
                        'type'        => 'array',
                        'required'    => true,
                        'items'       => array(
                            'type' => 'object',
                            'properties' => array(
                                'permission' => array(
                                    'type'     => 'string',
                                    'required' => true
                                ),
                                'effect' => array(
                                    'type'     => 'string',
                                    'default'  => 'deny',
                                    'enum'     => [ 'allow', 'deny' ]
                                )
                            )
                        )
                    )
                ]
            ));

            // Set a single identity permission
            $this->_register_route('/identity/(?P<type>[\w]+)/(?P<id>.+)/(?P<permission>[\w-]+)', [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_identity_permission'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => [
                    'type' => [
                        'description' => 'Identity type: either role or user',
                        'type'        => 'string',
                        'required'    => true,
                        'enum'        => [ 'role', 'user' ]
                    ],
                    'id' => [
                        'description' => 'Identity identifier',
                        'type'        => [ 'string', 'number' ],
                        'required'    => true
                    ],
                    'permission' => [
                        'description' => 'Permission',
                        'type'        => 'string',
                        'required'    => true
                    ],
                    'effect' => [
                        'type'     => 'string',
                        'default'  => 'deny',
                        'enum'     => [ 'allow', 'deny' ]
                    ]
                ]
            ]);


            // Reset permissions for all identities with specific type
            $this->_register_route('/identity/(?P<type>[\w]+)', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'reset_permissions'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'type' => array(
                        'description' => __('Identity type', AAM_KEY),
                        'type'        => 'string',
                        'required'    => true,
                        'enum'        => [ 'role', 'user' ]
                    )
                )
            ));

            // Reset identity permissions
            $this->_register_route('/identity/(?P<type>[\w]+)/(?P<id>.+)', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'reset_identity_permissions'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'type' => array(
                        'description' => __('Identity type', AAM_KEY),
                        'type'        => 'string',
                        'required'    => true,
                        'enum'        => [ 'role', 'user' ]
                    ),
                    'id' => [
                        'description' => 'Identity identifier',
                        'type'        => [ 'string', 'number' ],
                        'required'    => true
                    ]
                )
            ));

            // Reset all permissions
            $this->_register_route('/identities', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'reset_all_permissions'),
                'permission_callback' => array($this, 'check_permissions')
            ));
        });
    }

    /**
     * Get list of roles with permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function get_role_list(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = [];

            foreach($service->get_roles() as $role) {
                array_push($result, $this->_prepare_role_output($role));
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
     *
     * @access public
     * @version 7.0.0
     */
    public function get_role(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $this->_prepare_role_output(
                $service->role($request->get_param('slug'))
            );
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
            $user_data = $this->_get_service($request)->get_users($filters, 'full');
            $result    = [ 'list' => [], 'summary' => $user_data['summary'] ];

            foreach($user_data['list'] as $user) {
                array_push($result['list'], $this->_prepare_user_output($user));
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
     *
     * @access public
     * @version 7.0.0
     */
    public function get_user(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $this->_prepare_user_output(
                $service->user($request->get_param('id'))
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Update identity's permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function update_identity_permissions(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $type    = $request->get_param('type');

            if ($type === AAM_Framework_Type_Resource::ROLE) {
                $identity = $service->role($request->get_param('id'));
            } else {
                $identity = $service->user($request->get_param('id'));
            }

            $normalized = [];

            // Normalize the array of permissions
            foreach($request->get_param('permissions') as $permission) {
                $normalized[$permission['permission']] = [
                    'effect' => $permission['effect']
                ];
            }

            $identity->set_permissions($normalized, true);

            $result = $identity->get_permissions();
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Update identity's single permission
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function update_identity_permission(WP_REST_Request $request)
    {
        try {
            $service    = $this->_get_service($request);
            $permission = $request->get_param('permission');
            $effect     = $request->get_param('effect');
            $type       = $request->get_param('type');

            if ($type === AAM_Framework_Type_Resource::ROLE) {
                $identity = $service->role($request->get_param('id'));
            } else {
                $identity = $service->user($request->get_param('id'));
            }

            if ($effect !== 'allow') {
                $identity->deny($permission);
            } else {
                $identity->allow($permission);
            }

            $permissions = $identity->get_permissions();
            $result      = $permissions[$permission];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset permissions by identity type
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
            $result = [
                'success' => $this->_get_service($request)->reset(
                    $request->get_param('type')
                )
            ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset all permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function reset_all_permissions(WP_REST_Request $request)
    {
        try {
            $result = [
                'success' => $this->_get_service($request)->reset()
            ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset specific identity permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function reset_identity_permissions(WP_REST_Request $request)
    {
        try {
            $result = [
                'success' => $this->_get_service($request)->reset(
                    $request->get_param('type'),
                    $request->get_param('id')
                )
            ];
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
     * @version 7.0.0
     */
    public function check_permissions()
    {
        return current_user_can('aam_manager')
                && current_user_can('aam_manage_identities');
    }

    /**
     * Prepare the role output
     *
     * @param AAM_Framework_Resource_Role $role
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_role_output($role)
    {
        return [
            'id'            => $role->slug,
            'name'          => $role->display_name,
            'permissions'   => $role->get_permissions(),
            'is_customized' => $role->is_customized()
        ];
    }

    /**
     * Prepare the user output
     *
     * @param AAM_Framework_Resource_User $user
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_user_output($user)
    {
        return [
            'id'            => $user->ID,
            'display_name'  => $user->display_name,
            'permissions'   => $user->get_permissions(),
            'is_customized' => $user->is_customized()
        ];
    }

    /**
     * Get service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_Identities
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_service(WP_REST_Request $request)
    {
        return AAM::api()->identities([
            'access_level'   => $this->_determine_access_level($request),
            'error_handling' => 'exception'
        ]);
    }

}