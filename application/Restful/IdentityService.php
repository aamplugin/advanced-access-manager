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
            // Get the list of rules
            $this->_register_route('/identities', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_permissions'),
                'permission_callback' => array($this, 'check_permissions')
            ));

            // Create a new permissions
            $this->_register_route('/identity', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'create_permissions'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'permissions' => array(
                        'description'       => 'Collection of permissions',
                        'type'              => 'array',
                        'required'          => true,
                        'items'             => array(
                            'type'       => 'object',
                            'properties' => array(
                                'permission' => array(
                                    'type' => 'string',
                                    'required' => true,
                                    'enum' => AAM_Framework_Service_Identities::PERMISSION_TYPES
                                ),
                                'effect' => array(
                                    'type' => 'string',
                                    'required' => true,
                                    'enum' => AAM_Framework_Service_Identities::EFFECT_TYPES
                                ),
                                'identity_type' => [
                                    'type'     => 'string',
                                    'required' => true,
                                    'enum'     => AAM_Framework_Service_Identities::IDENTITY_TYPE
                                ],
                                'identity' => [
                                    'type'     => [ 'string', 'number' ],
                                    'required' => true
                                ]
                            )
                        )
                    )
                )
            ));

            // Get existing permission by ID
            $this->_register_route('/identity/(?P<id>[\w_]+)', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_permission'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => __('Permission unique ID', AAM_KEY),
                        'type'        => 'string',
                        'required'    => true
                    )
                )
            ));

            // Update an existing permission by ID
            $this->_register_route('/identity/(?P<id>[\w_]+)', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_permission'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => __('Permission unique ID', AAM_KEY),
                        'type'        => 'string',
                        'required'    => true
                    ),
                    'effect' => array(
                        'type' => 'string',
                        'required' => true,
                        'enum' => AAM_Framework_Service_Identities::EFFECT_TYPES
                    )
                )
            ));

            // Delete permission
            $this->_register_route('/identity/(?P<id>[\w_]+)', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_permission'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => __('Permission unique ID', AAM_KEY),
                        'type'        => 'string',
                        'required'    => true
                    )
                )
            ));

            // Reset all permissions
            $this->_register_route('/identity', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'reset_permissions'),
                'permission_callback' => array($this, 'check_permissions')
            ));
        });
    }

    /**
     * Get list of all permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function get_permissions(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = [];

            foreach($service->get_permissions() as $permission) {
                array_push($result, $this->_enrich_model($permission));
            }
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Create new permissions
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function create_permissions(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = [];

            foreach($request->get_param('permissions') as $permission) {
                array_push($result, $this->_enrich_model(
                    $service->create_permission($permission)
                ));
            }
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get permission by ID
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function get_permission(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $this->_enrich_model($service->get_permission_by_id(
                $request->get_param('id')
            ));
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Update permission
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function update_permission(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->update_permission(
                $request->get_param('id'),
                $request->get_param('effect')
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Delete existing permission
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 7.0.0
     */
    public function delete_permission(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = [
                'success' => $service->delete_permission($request->get_param('id'))
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
    public function reset_permissions(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->reset();
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
     * Enrich the identity model with additional information
     *
     * @param array $data
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _enrich_model($data)
    {
        $type = $data['identity_type'];

        // Adding also display name
        if ($type === 'user') {
            if (is_numeric($data['identity'])) {
                $user = get_user_by('ID', $data['identity']);

                if (is_a($user, 'WP_User')) {
                    $data['identity_title'] = $user->display_name;
                } else {
                    $data['identity_title'] = __('User does not exist', AAM_KEY);
                }
            } else {
                $data['identity_title'] = apply_filters(
                    'aam_rest_identity_identity_title_filter',
                    __('Unknown user name', AAM_KEY),
                    $data
                );
            }
        } elseif (in_array($type, ['role', 'user_role'], true)) {
            $names = wp_roles()->get_names();

            if (array_key_exists($data['identity'], $names)) {
                $data['identity_title'] = translate_user_role(
                    $names[$data['identity']]
                );
            } else {
                $data['identity_title'] = apply_filters(
                    'aam_rest_identity_identity_title_filter',
                    __('Unknown role name', AAM_KEY),
                    $data
                );
            }
        } elseif (in_array($type, ['role_level', 'user_level'], true)) {
            $data['identity_title'] = sprintf(
                __('Access Level %s', AAM_KEY), $data['identity']
            );
        }

        return apply_filters('aam_rest_identity_model_filter', $data);
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