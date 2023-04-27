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
 * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/271
 * @since 6.9.7  https://github.com/aamplugin/advanced-access-manager/issues/259
 * @since 6.9.6  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.10
 */
class AAM_Core_Restful_Role
{

    /**
     * The namespace for the collection of endpoints
     */
    const NAMESPACE = 'aam/v2';

    /**
     * Single instance of itself
     *
     * @var AAM_Core_Restful_Role
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
     * @since 6.9.7 https://github.com/aamplugin/advanced-access-manager/issues/259
     * @since 6.9.7 Initial implementation of the method
     *
     * @access protected
     * @version 6.9.7
     */
    protected function __construct()
    {
        // Register API endpoint
        add_action('rest_api_init', function() {
            // Get the list of roles
            $this->_register_route('/roles', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_role_list'),
                'permission_callback' => function () {
                    return current_user_can('aam_manager')
                        && current_user_can('aam_manage_roles');
                },
                'args'                => array(
                    'fields' => array(
                        'description' => __('List of additional fields to return', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_fields_input($value);
                        }
                    )
                )
            ));

            // Get a specific role
            $this->_register_route('/role/(?<slug>[\w\-]+)', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_role'),
                'permission_callback' => function () {
                    return current_user_can('aam_manager')
                        && current_user_can('aam_manage_roles');
                },
                'args' => array(
                    'slug'   => array(
                        'description' => __('Unique role slug (aka ID)', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_role_accessibility($value);
                        }
                    ),
                    'fields' => array(
                        'description' => __('List of additional fields to return', AAM_KEY),
                        'type' => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_fields_input($value);
                        }
                    )
                )
            ));

            // Create new role
            $this->_register_route('/role', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'create_role'),
                'permission_callback' => function () {
                    return current_user_can('aam_manager')
                        && current_user_can('aam_create_roles');
                },
                'args'                => array(
                    'slug' => array(
                        'description' => __('Unique role slug', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function($value, $request) {
                            return $this->_validate_role_slug_uniqueness(
                                $value, $request
                            );
                        }
                    ),
                    'name' => array(
                        'description' => __('Role name', AAM_KEY),
                        'type'        => 'string',
                        'required'    => true
                    ),
                    'capabilities' => array(
                        'description' => __('List of capabilities to assign', AAM_KEY),
                        'type'        => 'array',
                        'items'       => array(
                            'type'    => 'string',
                            'pattern' => '[\w\-]+'
                        ),
                        'validate_callback' => function ($value) {
                            return $this->_validate_keys_array_input($value);
                        }
                    ),
                    'parent_role' => array(
                        'description' => __('Parent role slug (aka ID)', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_role_accessibility($value);
                        }
                    ),
                    'clone_role' => array(
                        'description' => __('Clone role slug (aka ID)', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_role_accessibility($value);
                        }
                    ),
                    'clone_role_settings' => array(
                        'description' => __('Clone role settings', AAM_KEY),
                        'type'        => 'boolean'
                    ),
                    'fields' => array(
                        'description' => __('List of additional fields to return', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_fields_input($value);
                        }
                    )
                )
            ));

            // Update existing role
            $this->_register_route('/role/(?<slug>[\w\-]+)', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_role'),
                'permission_callback' => function () {
                    return current_user_can('aam_manager')
                        && current_user_can('aam_edit_roles');
                },
                'args'                => array(
                    'new_slug' => array(
                        'description' => __('Unique role slug', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function($value, $request) {
                            return $this->_validate_role_slug_uniqueness(
                                $value, $request
                            );
                        }
                    ),
                    'name' => array(
                        'description' => __('Role name', AAM_KEY),
                        'type'        => 'string',
                        'required'    => true
                    ),
                    'add_capabilities' => array(
                        'description' => __('List of capabilities to assign', AAM_KEY),
                        'type'        => 'array',
                        'items'       => array(
                            'type'    => 'string',
                            'pattern' => '[\w\-]+'
                        ),
                        'validate_callback' => function ($value) {
                            return $this->_validate_keys_array_input($value);
                        }
                    ),
                    'remove_capabilities' => array(
                        'description' => __('List of capabilities to remove', AAM_KEY),
                        'type'        => 'array',
                        'items'       => array(
                            'type'    => 'string',
                            'pattern' => '[\w\-]+'
                        ),
                        'validate_callback' => function ($value) {
                            return $this->_validate_keys_array_input($value);
                        }
                    )
                )
            ));

            // Delete role
            $this->_register_route('/role/(?<slug>[\w\-]+)', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_role'),
                'permission_callback' => function () {
                    return current_user_can('aam_manager')
                        && current_user_can('aam_delete_roles');
                },
                'args' => array(
                    'slug'   => array(
                        'description' => __('Unique role slug (aka ID)', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_role_accessibility($value);
                        }
                    ),
                    'fields' => array(
                        'description' => __('List of additional fields to return', AAM_KEY),
                        'type'        => 'string',
                        'fields' => array(
                            'description' => __('List of additional fields to return', AAM_KEY),
                            'type'        => 'string',
                            'validate_callback' => function ($value) {
                                return $this->_validate_fields_input($value);
                            }
                        )
                    )
                )
            ));
        });
    }

    /**
     * Get list of all editable roles
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @version 6.9.6
     */
    public function get_role_list(WP_REST_Request $request)
    {
        $response = array();

        // Determine the list of additional fields to return
        $fields = $this->_determine_additional_fields($request);

        // Fetch the complete list of editable roles and transform then into the
        // response array
        foreach(AAM_Framework_Manager::roles()->get_editable_roles() as $role) {
            array_push($response, $this->prepare_role_output($role, $fields));
        }

        return new WP_REST_Response($response);
    }

    /**
     * Get specific role by slug
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @version 6.9.6
     */
    public function get_role(WP_REST_Request $request)
    {
        $response = null;

        try {
            $response = new WP_REST_Response(($this->prepare_role_output(
                AAM_Framework_Manager::roles()->get_role_by_slug(
                    $request->get_param('slug')
                ),
                $this->_determine_additional_fields($request)
            )));
        } catch (Exception $ex) {
            $response = $this->_prepare_error_response($ex);
        }

        return $response;
    }

    /**
     * Create new role
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @version 6.9.6
     */
    public function create_role(WP_REST_Request $request)
    {
        $response = new WP_REST_Response();

        // Get the role service
        $service = AAM_Framework_Manager::roles();

        // Prepare the basic data attributes for new role: id, name and list of
        // capabilities
        $name                = $request->get_param('name');
        $slug                = $request->get_param('slug'); // optional
        $clone_role          = $request->get_param('clone_role'); // optional
        $clone_role_settings = $request->get_param('clone_role_settings'); // optional
        $capabilities        = $request->get_param('capabilities'); // optional

        try {
            // Making sure that we have at least empty array of capabilities
            $capabilities = is_array($capabilities) ? $capabilities : array();

            // If clone role is specified, verify that role exists and current user
            // can manage it
            if (is_string($clone_role) && strlen($clone_role) > 0) {
                $cloning_role = $service->get_role_by_slug($clone_role);

                $capabilities = array_merge(
                    $capabilities,
                    array_keys($cloning_role->capabilities),
                    // Also adding role's slug to the list of capabilities
                    // https://github.com/aamplugin/advanced-access-manager/issues/97
                    array($clone_role)
                );
            }

            $role = $service->create_role($name, $slug, $capabilities);

            // Cloning settings
            if ($clone_role_settings === true && !empty($cloning_role)) {
                $cloned = $this->_clone_settings($role, $cloning_role);

                if ($cloned !== true) {
                    $response->set_status(206);
                }
            }

            // Inform any other processes about new role creation event
            do_action('aam_role_created_action', $role, $request);

            $response->set_data($this->prepare_role_output(
                $role,
                $this->_determine_additional_fields($request)
            ));
        } catch (DomainException $ex) {
            $response = $this->_prepare_error_response(
                $ex, 'rest_domain_rule_failure', 409
            );
        } catch (Exception $ex) {
            $response = $this->_prepare_error_response($ex);
        }

        return $response;
    }

    /**
     * Update existing role
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/271
     * @since 6.9.6  Initial implementation of the method
     *
     * @version 6.9.10
     */
    public function update_role(WP_REST_Request $request)
    {
        $response = new WP_REST_Response();

        $name                = $request->get_param('name'); // optional
        $slug                = $request->get_param('slug'); // changing role ID
        $new_slug            = $request->get_param('new_slug'); // optional
        $add_capabilities    = $request->get_param('add_capabilities'); // optional
        $remove_capabilities = $request->get_param('remove_capabilities'); // optional

        // Get role service
        $service = AAM_Framework_Manager::roles();

        try {
            $role = $service->get_role_by_slug($slug);

            // Setting new slug if provided
            if (!empty($new_slug)) {
                $role->set_slug(sanitize_key($new_slug));
            }

            // Set new display name if provided
            if (!empty($name)) {
                $role->set_display_name($name);
            }

            // Adding the list of capabilities
            if (is_array($add_capabilities)) {
                array_walk($add_capabilities, function($cap) use ($role) {
                    $role->add_capability($cap);
                });
            }

            // Removing the list of capabilities
            if (is_array($remove_capabilities)) {
                array_walk($remove_capabilities, function($cap) use ($role) {
                    $role->remove_capability($cap);
                });
            }

            // Finally storing the changes
            $service->update_role($role);

            // Inform any other processes about role updated event
            do_action('aam_role_updated_action', $role, $request);

            $response->set_data($this->prepare_role_output(
                $role,
                $this->_determine_additional_fields($request)
            ));
        } catch (DomainException $ex) {
            $response = $this->_prepare_error_response(
                $ex, 'rest_domain_rule_failure', 409
            );
        } catch (Exception $ex) {
            $response = $this->_prepare_error_response($ex);
        }

        return $response;
    }

    /**
     * Delete existing role
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @version 6.9.6
     */
    public function delete_role(WP_REST_Request $request)
    {
        $response = new WP_REST_Response();

        // Get role service
        $service = AAM_Framework_Manager::roles();

        try {
            $role = $service->get_role_by_slug($request->get_param('slug'));

            if ($service->delete_role($role)) {
                $response->set_data($this->prepare_role_output(
                    $role,
                    $this->_determine_additional_fields($request)
                ));
            } else {
                throw new Exception(__('Failed to delete the role', AAM_KEY));
            }
        } catch (DomainException $ex) {
            $response = $this->_prepare_error_response(
                $ex, 'rest_domain_rule_failure', 409
            );
        } catch (Exception $ex) {
            $response = $this->_prepare_error_response($ex);
        }

        return $response;
    }

    /**
     * Clone access settings
     *
     * @param AAM_Framework_Proxy_Role $role
     * @param AAM_Framework_Proxy_Role $parent
     *
     * @return boolean
     *
     * @access private
     * @version 6.9.6
     */
    private function _clone_settings($role, $parent)
    {
        $settings = AAM_Core_AccessSettings::getInstance();

        // Clone the settings
        $settings->set("role.{$role->slug}", $settings->get("role.{$parent->slug}"));

        return $settings->save();
    }

    /**
     * Prepare role model for response
     *
     * @param AAM_Framework_Proxy_Role $role
     * @param array                    $fields
     *
     * @return array
     * @version 6.9.6
     */
    protected function prepare_role_output(
        AAM_Framework_Proxy_Role $role, $fields = array()
    ) {
        $response = array(
            'slug' => $role->slug,
            'name' => translate_user_role($role->display_name),
        );

        // Adding additional information to each role
        foreach($fields as $field) {
            if ($field === 'capabilities') {
                $response[$field] = $role->capabilities;
            } elseif ($field === 'permissions') {
                $response[$field] = $this->get_role_permissions($role);
            } elseif ($field === 'user_count') {
                $response[$field] = AAM_Framework_Manager::roles()->get_role_user_count(
                    $role
                );
            } else {
                $custom = apply_filters(
                    'aam_role_rest_field_filter', null, $role, $field
                );

                if ($custom !== null) {
                    $response[$field] = $custom;
                }
            }
        }

        return $response;
    }

    /**
     * Get list of actions user can perform upon role
     *
     * @param AAM_Framework_Proxy_Role $role
     *
     * @return array
     * @version 6.9.6
     */
    protected function get_role_permissions(AAM_Framework_Proxy_Role $role)
    {
        $permissions = array('allow_manage');
        $user_count   = AAM_Framework_Manager::roles()->get_role_user_count($role);

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

        return apply_filters('aam_role_permissions_filter', $permissions, $role);
    }

    /**
     * Determine list of additional fields to return
     *
     * @param WP_REST_Request $request
     *
     * @return array
     *
     * @access private
     * @version 6.9.6
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
     * @version 6.9.6
     */
    private function _register_route($route, $args)
    {
        register_rest_route(
            self::NAMESPACE,
            $route,
            apply_filters('aam_rest_route_args_filter', $args, $route, self::NAMESPACE)
        );
    }

    /**
     * Validate the input field "fields"
     *
     * @param string|null $value Input value
     *
     * @return bool|WP_Error
     *
     * @access private
     * @version 6.9.6
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
     * Validate role accessibility
     *
     * @param string $slug Role unique slug (aka ID)
     *
     * @return bool|WP_Error
     *
     * @access private
     * @version 6.9.6
     */
    private function _validate_role_accessibility($slug)
    {
        $response = true;

        try {
            AAM_Framework_Manager::roles()->get_role_by_slug($slug);
        } catch (UnderflowException $_) {
            $response = new WP_Error(
                'rest_not_found',
                sprintf(
                    __("The role '%s' does not exist or is not editable"),
                    $slug
                ),
                array('status'  => 404)
            );
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
     *
     * @access private
     * @version 6.9.6
     */
    private function _validate_role_slug_uniqueness($value, WP_REST_Request $request)
    {
        $response = true;

        if (is_string($value)) {
            $slug = sanitize_key($value);

            if ($slug === $request->get_param('slug')) {
                $response = true; // do nothing, we do not update the slug
            } elseif (strlen($slug) > 0) {
                if (AAM_Framework_Manager::roles()->is_role($slug)) {
                    $response = new WP_Error(
                        'rest_invalid_param',
                        sprintf(
                            __("The role with '%s' slug already exists"),
                            $slug
                        ),
                        array('status'  => 400)
                    );
                }
            } else {
                $response = new WP_Error(
                    'rest_invalid_param',
                    sprintf(
                        __("Invalid role slug '%s'"),
                        $value
                    ),
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
     *
     * @access private
     * @version 6.9.6
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
                    sprintf(
                        __('Invalid keys: %s'),
                        implode(', ', $invalid_keys)
                    ),
                    array('status'  => 400)
                );
            }
        }

        return $response;
    }

    /**
     * Prepare the failure response
     *
     * @param Exception $ex
     * @param string    $code
     * @param integer   $status
     *
     * @return WP_REST_Response
     *
     * @access private
     * @version 6.9.6
     */
    private function _prepare_error_response(
        $ex, $code = 'rest_unexpected_error', $status = 500
    ) {
        $message = $ex->getMessage();
        $data    = array('status' => $status);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            $data['details'] = array(
                'trace' => $ex->getTrace()
            );
        } elseif ($status === 500) { // Mask the real error if debug mode is off
            $message = __('Unexpected application error', AAM_KEY);
        }

        return new WP_REST_Response(new WP_Error($code, $message, $data), $status);
    }

    /**
     * Bootstrap the api
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.6
     */
    public static function bootstrap()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

}