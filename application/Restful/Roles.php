<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for role management
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Restful_Roles
{

    use AAM_Restful_ServiceTrait;

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
            // Get the list of roles
            $this->_register_route('/roles', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_role_list'),
                'args'     => array(
                    'fields' => array(
                        'description' => 'List of additional fields to return',
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_fields_input($value);
                        }
                    )
                )
            ], [ 'aam_manager', 'aam_list_roles' ], false);

            // Get a specific role
            $this->_register_route('/role/(?P<role_slug>[\w\-%+]+)', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_role'),
                'args'     => array(
                    'role_slug'   => array(
                        'description' => 'Unique role slug (aka ID)',
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_role_accessibility($value);
                        }
                    ),
                    'fields' => array(
                        'description' => 'List of additional fields to return',
                        'type' => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_fields_input($value);
                        }
                    )
                )
            ], [ 'aam_manager', 'aam_list_roles' ], false);

            // Create new role
            $this->_register_route('/roles', [
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_role'),
                'args'     => array(
                    'slug' => array(
                        'description' => 'Unique role slug',
                        'type'        => 'string',
                        'validate_callback' => function($value, $request) {
                            return $this->_validate_role_slug_uniqueness(
                                $value, $request
                            );
                        }
                    ),
                    'name' => array(
                        'description' => 'Role name',
                        'type'        => 'string',
                        'required'    => true
                    ),
                    'capabilities' => array(
                        'description' => 'List of capabilities to assign',
                        'type'        => 'array',
                        'items'       => array(
                            'type' => 'string'
                        )
                    ),
                    'clone_role' => array(
                        'description' => 'Clone role slug (aka ID)',
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_role_accessibility($value);
                        }
                    ),
                    'clone_role_settings' => array(
                        'description' => 'Clone role settings',
                        'type'        => 'boolean'
                    ),
                    'fields' => array(
                        'description' => 'List of additional fields to return',
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_fields_input($value);
                        }
                    )
                )
            ], [ 'aam_manager', 'aam_create_roles' ], false);

            // Update existing role
            $this->_register_route('/role/(?P<role_slug>[\w\-%+]+)', [
                'methods'  => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_role'),
                'args'     => array(
                    'role_slug'   => array(
                        'description' => 'Unique role slug (aka ID)',
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_role_accessibility($value);
                        }
                    ),
                    'new_slug' => array(
                        'description' => 'Unique role slug',
                        'type'        => 'string'
                    ),
                    'name' => array(
                        'description' => 'Role name',
                        'type'        => 'string'
                    ),
                    'add_capabilities' => array(
                        'description' => 'List of capabilities to assign',
                        'type'        => 'array',
                        'items'       => array(
                            'type' => 'string'
                        )
                    ),
                    'remove_capabilities' => array(
                        'description' => 'List of capabilities to remove',
                        'type'        => 'array',
                        'items'       => array(
                            'type' => 'string'
                        )
                    )
                )
            ], [ 'aam_manager', 'aam_edit_roles' ], false);

            // Delete role
            $this->_register_route('/role/(?P<role_slug>[\w\-%+]+)', [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_role'),
                'args'     => array(
                    'role_slug'   => array(
                        'description' => 'Unique role slug (aka ID)',
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_role_accessibility($value);
                        }
                    )
                )
            ], [ 'aam_manager', 'aam_delete_roles' ], false);
        });
    }

    /**
     * Get list of all editable roles
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     *
     * @version 7.0.0
     */
    public function get_role_list(WP_REST_Request $request)
    {
        try {
            $result = [];

            // Determine the list of additional fields to return
            $fields = $this->_determine_additional_fields($request);

            // Fetch the complete list of editable roles and transform then into the
            // response array
            foreach(AAM::api()->roles->get_editable_roles() as $role) {
                array_push($result, $this->_prepare_output($role, $fields));
            }
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get specific role by slug
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     *
     * @version 7.0.0
     */
    public function get_role(WP_REST_Request $request)
    {
        try {
            $result = $this->_prepare_output(
                AAM::api()->role(urldecode($request->get_param('role_slug'))),
                $this->_determine_additional_fields($request)
            );
        } catch (Exception $ex) {
            $result = $this->_prepare_error_response($ex);
        }

        return rest_ensure_response($result);
    }

    /**
     * Create new role
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     *
     * @version 7.0.0
     */
    public function create_role(WP_REST_Request $request)
    {
        try {
            // Prepare the basic data attributes for new role: id, name and list of
            // capabilities
            $name                = $request->get_param('name');
            $slug                = $request->get_param('slug'); // optional
            $clone_role          = $request->get_param('clone_role'); // optional
            $clone_role_settings = $request->get_param('clone_role_settings'); // optional
            $capabilities        = $request->get_param('capabilities'); // optional

            // Making sure that we have at least empty array of capabilities
            $capabilities = is_array($capabilities) ? $capabilities : array();

            // If clone role is specified, verify that role exists and current user
            // can manage it
            if (is_string($clone_role) && strlen($clone_role) > 0) {
                $cloning_role = AAM::api()->role($clone_role);
                $cloning_caps = array_filter(
                    $cloning_role->capabilities, function($effect) {
                        return !empty($effect);
                    }
                );

                $capabilities = array_merge(
                    $capabilities,
                    array_keys($cloning_caps),
                    // Also adding role's slug to the list of capabilities
                    // https://github.com/aamplugin/advanced-access-manager/issues/97
                    array($clone_role)
                );
            }

            $role = AAM::api()->roles->create($name, $slug, $capabilities);

            // Cloning settings
            if ($clone_role_settings === true && !empty($cloning_role)) {
                $this->_clone_settings($role, $cloning_role);
            }

            // Inform any other processes about new role creation event
            do_action('aam_role_created_action', $role, $request);

            $result = $this->_prepare_output(
                $role,
                $this->_determine_additional_fields($request)
            );
        } catch (Exception $ex) {
            $result = $this->_prepare_error_response($ex);
        }

        return rest_ensure_response($result);
    }

    /**
     * Update existing role
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     *
     * @version 7.0.0
     */
    public function update_role(WP_REST_Request $request)
    {
        try {
            $name         = $request->get_param('name'); // optional
            $slug         = urldecode($request->get_param('role_slug'));
            $new_slug     = $request->get_param('new_slug'); // optional
            $add_caps     = $request->get_param('add_capabilities'); // optional
            $deprive_caps = $request->get_param('deprive_capabilities'); // optional
            $remove_caps  = $request->get_param('remove_capabilities'); // optional

            // Update role
            $role = AAM::api()->roles->update($slug, [
                'name'         => $name,
                'slug'         => $new_slug,
                'add_caps'     => $add_caps,
                'deprive_caps' => $deprive_caps,
                'remove_caps'  => $remove_caps
            ]);

            // Inform any other processes about role updated event
            do_action('aam_rest_update_role_action', $role, $request);

            $result = $this->_prepare_output(
                $role,
                $this->_determine_additional_fields($request)
            );
        } catch (Exception $ex) {
            $result = $this->_prepare_error_response($ex);
        }

        return rest_ensure_response($result);
    }

    /**
     * Delete existing role
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     *
     * @version 7.0.0
     */
    public function delete_role(WP_REST_Request $request)
    {
        try {
            // Delete role
            $result = [
                'success' => AAM::api()->roles->delete(urldecode(
                    $request->get_param('role_slug')
                ))
            ];
        } catch (Exception $ex) {
            $result = $this->_prepare_error_response($ex);
        }

        return rest_ensure_response($result);
    }

    /**
     * Clone access settings
     *
     * @param AAM_Framework_Proxy_Role $role
     * @param AAM_Framework_Proxy_Role $parent
     *
     * @return boolean
     * @access private
     *
     * @version 7.0.7
     */
    private function _clone_settings($role, $parent)
    {
        // Base role
        $service = AAM::api()->settings(sprintf('%s:%s',
            AAM_Framework_Type_AccessLevel::ROLE,
            $role->slug
        ));

        // From role
        $from = AAM::api()->settings(sprintf('%s:%s',
            AAM_Framework_Type_AccessLevel::ROLE,
            $parent->slug
        ));

        // Clone the settings
        return $service->set_settings($from->get_settings());
    }

    /**
     * Prepare role model for response
     *
     * @param AAM_Framework_AccessLevel_Role $role
     * @param array                         $fields
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_output($role, $fields = [])
    {
        $response = array(
            'slug' => $role->slug,
            'name' => translate_user_role($role->display_name),
        );

        // Adding additional information to each role
        foreach($fields as $field) {
            if ($field === 'capabilities') {
                $response[$field] = $role->capabilities;
            } elseif ($field === 'permissions') {
                $response[$field] = $this->_get_role_permissions($role);
            } elseif ($field === 'user_count') {
                $response[$field] = $role->user_count;
            } else {
                $custom = apply_filters(
                    'aam_role_rest_field_filter', null, $role, $field
                );

                if ($custom !== null) {
                    $response[$field] = $custom;
                }
            }
        }

        return apply_filters(
            'aam_rest_role_output_filter', $response, $role, $fields
        );
    }

    /**
     * Get list of actions user can perform upon role
     *
     * @param AAM_Framework_AccessLevel_Role $role
     * @return array
     *
     * @version 7.0.0
     */
    private function _get_role_permissions($role)
    {
        $permissions = array('allow_manage');
        $user_count   = $role->user_count;

        if (current_user_can('aam_edit_roles')) {
            $permissions[] = 'allow_edit';

            if ($user_count === 0) {
                $permissions[] = 'allow_slug_update';
            }
        }

        if (current_user_can('aam_create_roles')) {
            $permissions[] = 'allow_clone';
        }

        if (current_user_can('aam_delete_roles') && ($user_count === 0)) {
            $permissions[] = 'allow_delete';
        }

        return $permissions;
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

        return !empty($fields) ? wp_parse_list($fields) : [];
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

    /**
     * Validate role slug and its uniqueness
     *
     * @param string          $value Role slug (aka ID)
     * @param WP_REST_Request $value Current request
     *
     * @return bool|WP_Error
     * @access private
     *
     * @version 7.0.0
     */
    private function _validate_role_slug_uniqueness($value, WP_REST_Request $request)
    {
        $response = true;

        if (is_string($value)) {
            $slug = sanitize_key($value);

            if ($slug === $request->get_param('slug')) {
                $response = true; // do nothing, we do not update the slug
            } elseif (strlen($slug) > 0) {
                if (wp_roles()->is_role($slug)) {
                    $response = new WP_Error(
                        'rest_invalid_param',
                        sprintf("The role with '%s' slug already exists", $slug),
                        array('status'  => 400)
                    );
                }
            } else {
                $response = new WP_Error(
                    'rest_invalid_param',
                    sprintf("Invalid role slug '%s'", $value),
                    array('status'  => 400)
                );
            }
        }

        return $response;
    }

    /**
     * Validate the array of keys
     *
     * @param array|null $value Input array of values
     *
     * @return bool|WP_Error
     * @access private
     *
     * @version 7.0.0
     */
    private function _validate_keys_array_input($value)
    {
        $response = true;

        if (is_array($value) && count($value) > 0) {
            $invalid_keys = [];

            foreach($value as $key) {
                if (strlen(sanitize_key($key)) !== strlen($key)) {
                    $invalid_keys[] = $key;
                }
            }

            if (count($invalid_keys) > 0) {
                $response = new WP_Error(
                    'rest_invalid_param',
                    sprintf('Invalid keys: %s', implode(', ', $invalid_keys)),
                    array('status'  => 400)
                );
            }
        }

        return $response;
    }

}