<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for the Users & Roles Governance service
 *
 * @package AAM
 * @version 6.9.28
 */
class AAM_Restful_IdentityGovernanceService
{

    use AAM_Restful_ServiceTrait;

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 6.9.28
     */
    protected function __construct()
    {
        // Register API endpoint
        add_action('rest_api_init', function() {
            // Get the list of rules
            $this->_register_route('/identity-governance', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_rule_list'),
                'permission_callback' => array($this, 'check_permissions')
            ));

            // Create a new rule
            $this->_register_route('/identity-governance', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'create_rule'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'rule_type' => array(
                        'description'       => 'Rule type',
                        'type'              => 'string',
                        'required'          => true,
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_rule_type($value, $request);
                        }
                    ),
                    'user_list' => array(
                        'description'       => 'Collection of targeting usernames or user IDs',
                        'type'              => 'array',
                        'minItems'          => 1,
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_user_list($value, $request);
                        }
                    ),
                    'role_list' => array(
                        'description'       => 'Collection of targeting role slugs',
                        'type'              => 'array',
                        'minItems'          => 1,
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_role_list($value, $request);
                        }
                    ),
                    'level_list' => array(
                        'description'       => 'Collection of targeting levels',
                        'type'              => 'array',
                        'minItems'          => 1,
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_level_list($value, $request);
                        }
                    ),
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
                                    'enum' => AAM_Framework_Service_IdentityGovernance::PERMISSION_TYPES
                                ),
                                'effect' => array(
                                    'type' => 'string',
                                    'required' => true,
                                    'enum' => AAM_Framework_Service_IdentityGovernance::EFFECT_TYPES
                                ),
                            )
                        )
                    )
                )
            ));

            // Get a rule
            $this->_register_route('/identity-governance/(?P<id>[\d]+)', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_rule'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => __('Rule unique ID', AAM_KEY),
                        'type'        => 'number',
                        'required'    => true
                    )
                )
            ));

            // Update an existing rule
            $this->_register_route('/identity-governance/(?P<id>[\d]+)', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_rule'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => __('Rule unique ID', AAM_KEY),
                        'type'        => 'number',
                        'required'    => true
                    ),
                    'rule_type' => array(
                        'description'       => 'Rule type',
                        'type'              => 'string',
                        'required'          => true,
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_rule_type($value, $request);
                        }
                    ),
                    'user_login' => array(
                        'description'       => 'Usernames or user IDs',
                        'type'              => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_user($value, $request);
                        }
                    ),
                    'role_slug' => array(
                        'description'       => 'Role slugs',
                        'type'              => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_role($value, $request);
                        }
                    ),
                    'level' => array(
                        'description'       => 'Capability level',
                        'type'              => 'integer',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_level($value, $request);
                        }
                    ),
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
                                    'enum' => AAM_Framework_Service_IdentityGovernance::PERMISSION_TYPES
                                ),
                                'effect' => array(
                                    'type' => 'string',
                                    'required' => true,
                                    'enum' => AAM_Framework_Service_IdentityGovernance::EFFECT_TYPES
                                ),
                            )
                        )
                    )
                )
            ));

            // Delete a rule
            $this->_register_route('/identity-governance/(?P<id>[\d]+)', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_rule'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => __('Rule unique ID', AAM_KEY),
                        'type'        => 'number',
                        'required'    => true
                    )
                )
            ));

            // Reset all rules
            $this->_register_route('/identity-governance', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'reset_rules'),
                'permission_callback' => array($this, 'check_permissions')
            ));
        });
    }

    /**
     * Get list of all rules
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.28
     */
    public function get_rule_list(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = [];

            foreach($service->get_rule_list() as $rule) {
                array_push($result, $this->_enrich_rule($rule));
            }
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Create new rule
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.28
     */
    public function create_rule(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $data    = $request->get_params();
            $result  = array();

            // If rule type is "user" or "role", split incoming comma-separated list
            // of identifiers and create rule for each identifier individually
            if ($data['rule_type'] === 'user') {
                foreach($data['user_list'] as $user) {
                    array_push($result, $service->create_rule(array(
                        'rule_type'   => $data['rule_type'],
                        'user_login'  => $user,
                        'permissions' => $data['permissions']
                    )));
                }
            } elseif (in_array($data['rule_type'], ['role', 'user_role'], true)) {
                foreach($data['role_list'] as $role) {
                    array_push($result, $service->create_rule(array(
                        'rule_type'   => $data['rule_type'],
                        'role_slug'    => $role,
                        'permissions' => $data['permissions']
                    )));
                }
            } elseif (in_array($data['rule_type'], ['role_level', 'user_level'], true)) {
                foreach($data['level_list'] as $level) {
                    array_push($result, $service->create_rule(array(
                        'rule_type'   => $data['rule_type'],
                        'level'       => intval($level),
                        'permissions' => $data['permissions']
                    )));
                }
            } else {
                $result = $service->create_rule($data);
            }
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get a rule
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.28
     */
    public function get_rule(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $this->_enrich_rule($service->get_rule_by_id(
                intval($request->get_param('id'))
            ));
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Update a rule
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.28
     */
    public function update_rule(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = $service->update_rule(
                intval($request->get_param('id')),
                $request->get_params()
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Delete a rule
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.28
     */
    public function delete_rule(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);
            $result  = [
                'success' => $service->delete_rule(intval($request->get_param('id')))
            ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset all rules
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.28
     */
    public function reset_rules(WP_REST_Request $request)
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
     * @version 6.9.28
     */
    public function check_permissions()
    {
        return current_user_can('aam_manager')
                && current_user_can('aam_manage_user_governance');
    }

    /**
     * Enrich rule with additional information
     *
     * @param array $rule
     *
     * @return array
     *
     * @access private
     * @version 6.9.28
     */
    private function _enrich_rule($rule)
    {
        // Adding also display name
        if ($rule['rule_type'] === 'user') {
            if ($rule['user_login'] === '*') {
                $rule['display_name'] = __('All Users', AAM_KEY);

                if (!defined('AAM_COMPLETE_PACKAGE')) {
                    $rule['display_name'] .= ' (premium feature)';
                }
            } else {
                $user = get_user_by('login', $rule['user_login']);

                if (is_a($user, 'WP_User')) {
                    $rule['display_name'] = "{$user->display_name} ({$user->user_email})";
                } else {
                    $rule['display_name'] = __('User does not exist', AAM_KEY);
                }
            }
        } elseif (in_array($rule['rule_type'], ['role', 'user_role'], true)) {
            if ($rule['role_slug'] === '*') {
                $rule['display_name'] = __('All Roles', AAM_KEY);

                if (!defined('AAM_COMPLETE_PACKAGE')) {
                    $rule['display_name'] .= ' (premium feature)';
                }
            } else {
                $names                = AAM_Core_API::getRoles()->get_names();
                $rule['display_name'] = translate_user_role(
                    $names[$rule['role_slug']]
                );
            }
        } elseif (in_array($rule['rule_type'], ['role_level', 'user_level'], true)) {
            $rule['display_name'] = $rule['level'];
        } else {
            $rule['display_name'] = apply_filters(
                'aam_user_governance_rule_type_name_filter',
                null,
                $rule['rule_type'],
                $rule
            );
        }

        return $rule;
    }

    /**
     * Validate the rule type
     *
     * @param string $value
     *
     * @return boolean|WP_Error
     *
     * @access private
     * @version 6.9.28
     */
    private function _validate_rule_type($value)
    {
        $response      = true;
        $allowed_types = apply_filters(
            'aam_allowed_user_governance_rule_types_filter',
            AAM_Framework_Service_IdentityGovernance::RULE_TYPES,
            true
        );

        if (!in_array($value, $allowed_types, true)) {
            $response = new WP_Error(
                'rest_invalid_param',
                __('The rule type is not a valid', AAM_KEY),
                array('status'  => 400)
            );
        }

        return $response;
    }

    /**
     * Validate array of users
     *
     * @param array           $users
     * @param WP_REST_Request $request
     *
     * @return boolean|WP_Error
     *
     * @access private
     * @version 6.9.28
     */
    private function _validate_user_list($users, $request)
    {
        $response  = true;
        $rule_type = $request->get_param('rule_type');

        if ($rule_type === 'user' && count($users) === 0) {
            $response = new WP_Error(
                'rest_invalid_param',
                __('There has to be at least one user provided', AAM_KEY),
                array('status'  => 400)
            );
        }

        return $response;
    }

    /**
     * Validate array of levels
     *
     * @param array           $levels
     * @param WP_REST_Request $request
     *
     * @return boolean|WP_Error
     *
     * @access private
     * @version 6.9.28
     */
    private function _validate_level_list($levels, $request)
    {
        $response  = true;
        $rule_type = $request->get_param('rule_type');

        if (in_array($rule_type, ['user_level', 'role_level'], true)
            && count($levels) === 0
        ) {
            $response = new WP_Error(
                'rest_invalid_param',
                __('There has to be at least one level provided', AAM_KEY),
                array('status'  => 400)
            );
        }

        return $response;
    }

    /**
     * Validate user identifier
     *
     * @param string          $user
     * @param WP_REST_Request $request
     *
     * @return boolean|WP_Error
     *
     * @access private
     * @version 6.9.28
     */
    private function _validate_user($user, $request)
    {
        $response  = true;
        $rule_type = $request->get_param('rule_type');

        if ($rule_type === 'user' && empty($user)) {
            $response = new WP_Error(
                'rest_invalid_param',
                __('Username, email or ID has to be provided', AAM_KEY),
                array('status'  => 400)
            );
        }

        return $response;
    }

    /**
     * Validate array of roles
     *
     * @param array           $roles
     * @param WP_REST_Request $request
     *
     * @return boolean|WP_Error
     *
     * @access private
     * @version 6.9.28
     */
    private function _validate_role_list($roles, $request)
    {
        $response  = true;
        $rule_type = $request->get_param('rule_type');

        if (in_array($rule_type, ['role', 'user_role'], true)
            && count($roles) === 0
        ) {
            $response = new WP_Error(
                'rest_invalid_param',
                __('There has to be at least one role provided', AAM_KEY),
                array('status'  => 400)
            );
        }

        return $response;
    }

    /**
     * Validate role identifier
     *
     * @param string          $role
     * @param WP_REST_Request $request
     *
     * @return boolean|WP_Error
     *
     * @access private
     * @version 6.9.28
     */
    private function _validate_role($role, $request)
    {
        $response  = true;
        $rule_type = $request->get_param('rule_type');

        if (in_array($rule_type, ['role', 'user_role'], true) && empty($role)) {
            $response = new WP_Error(
                'rest_invalid_param',
                __('Valid role slug has to be provided', AAM_KEY),
                array('status'  => 400)
            );
        }

        return $response;
    }

    /**
     * Validate level
     *
     * @param string|int      $value
     * @param WP_REST_Request $request
     *
     * @return boolean|WP_Error
     *
     * @access private
     * @version 6.9.28
     */
    private function _validate_level($value, $request)
    {
        $response  = true;
        $rule_type = $request->get_param('rule_type');

        if (in_array($rule_type, ['user_level', 'role_level'], true)
            && !is_numeric($value)
        ) {
            $response = new WP_Error(
                'rest_invalid_param',
                __('The level has to be an integer value', AAM_KEY),
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
     * @return AAM_Framework_Service_IdentityGovernance
     *
     * @access private
     * @version 6.9.33
     */
    private function _get_service(WP_REST_Request $request)
    {
        return AAM_Framework_Manager::identity_governance([
            'subject'        => $this->_determine_subject($request),
            'error_handling' => 'exception'
        ]);
    }

}