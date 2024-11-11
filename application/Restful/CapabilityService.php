<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for the Capabilities service
 *
 * @since 6.9.39 https://github.com/aamplugin/advanced-access-manager/issues/422
 *               https://github.com/aamplugin/advanced-access-manager/issues/423
 * @since 6.9.33 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.39
 */
class AAM_Restful_CapabilityService
{

    use AAM_Restful_ServiceTrait;

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 6.9.33
     */
    protected function __construct()
    {
        // Register API endpoint
        add_action('rest_api_init', function() {
            // Get list of all registered capabilities
            $this->_register_route('/capabilities', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_list'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'fields' => array(
                        'description' => 'List of additional fields to return',
                        'type'        => 'string'
                    ),
                    'list_all' => array(
                        'description' => 'List all capabilities or not',
                        'type'        => 'boolean',
                        'default'     => false
                    )
                )
            ));

            // Create new capability
            $this->_register_route('/capabilities', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'create_capability'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'slug' => array(
                        'description' => 'Capability slug',
                        'type'        => 'string',
                        'required'    => true
                    ),
                    'ignore_format' => [
                        'description' => 'Bypass the recommended by WP core standard',
                        'type'        => 'boolean',
                        'default'     => false
                    ]
                )
            ));

            // Edit existing capability
            $this->_register_route('/capability/(?P<slug>.+)', [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [ $this, 'update_capability' ],
                'permission_callback' => [ $this, 'check_permissions' ],
                'args'                => [
                    'slug' => [
                        'description' => 'Existing capability slug',
                        'type'        => 'string',
                        'required'    => true
                    ],
                    'new_slug' => [
                        'description' => 'New capability slug',
                        'type'        => 'string',
                        'required'    => true
                    ],
                    'ignore_format' => [
                        'description' => 'Bypass the recommended by WP core standard',
                        'type'        => 'boolean',
                        'default'     => false
                    ]
                ]
            ]);

            // Delete existing capability
            $this->_register_route('/capability/(?P<slug>.+)', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_capability'),
                'permission_callback' => array($this, 'check_permissions')
            ));
        });
    }

    /**
     * Get list of all capabilities
     *
     * If role_id or user_id is provided, add to the list of capabilities
     * also an attribute "is_granted"
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.33
     */
    public function get_list(WP_REST_Request $request)
    {
        try {
            $service  = $this->_get_service($request);
            $role_id  = $request->get_param('role_id');
            $user_id  = $request->get_param('user_id');
            $list_all = $request->get_param('list_all');

            if (!empty($role_id)) {
                $list = $service->get_role_capabilities($role_id, $list_all);
            } elseif (!empty($user_id)) {
                $list = $service->get_user_capabilities($user_id, $list_all);
            } else {
                $list = $service->get_all_capabilities();
            }

            // Return a pure and enriched array of capabilities
            $result = [];

            foreach(array_values($list) as $item) {
                array_push($result, $this->_prepare_output($item, $request));
            }
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Create new capability
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @since 6.9.39 https://github.com/aamplugin/advanced-access-manager/issues/423
     * @since 6.9.33 Initial implementation of the method
     *
     * @access public
     * @version 6.9.39
     */
    public function create_capability(WP_REST_Request $request)
    {
        try {
            $service       = $this->_get_service($request);
            $role_id       = $request->get_param('role_id');
            $user_id       = $request->get_param('user_id');
            $capability    = urldecode($request->get_param('slug'));
            $ignore_format = $request->get_param('ignore_format');

            if (!$ignore_format && !preg_match('/^[a-z\d\-_]+/', $capability)) {
                throw new InvalidArgumentException(
                    'Valid capability slug is required'
                );
            }

            // Step #1. Let's create the capability and automatically assign it to
            // the administrator role
            $result = $service->create($capability);

            // Step #2. Assign the newly created capability to role or user specified
            if (!empty($role_id)) {
                $service->add_to_role($role_id, $result['slug']);
            } elseif (!empty($user_id)) {
                $service->add_to_user(intval($user_id), $result['slug']);
            }
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Update existing capability
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.33
     */
    public function update_capability(WP_REST_Request $request)
    {
        try {
            $service       = $this->_get_service($request);
            $role_id       = $request->get_param('role_id');
            $user_id       = $request->get_param('user_id');
            $capability    = urldecode($request->get_param('slug'));
            $new_slug      = $request->get_param('new_slug');
            $ignore_format = $request->get_param('ignore_format');

            if (!$ignore_format && !preg_match('/^[a-z\d-_]+/', $new_slug)) {
                throw new InvalidArgumentException(
                    'Valid capability slug is required'
                );
            }

            if (!empty($role_id)) {
                $result = $service->update($capability, $new_slug, 'role', $role_id);
            } elseif (!empty($user_id)) {
                $result = $service->update($capability, $new_slug, 'user', $user_id);
            } else {
                $result = $service->update($capability, $new_slug);
            }
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Delete existing capability
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @version 6.9.33
     */
    public function delete_capability(WP_REST_Request $request)
    {
        try {
            $service    = $this->_get_service($request);
            $role_id    = $request->get_param('role_id');
            $user_id    = $request->get_param('user_id');
            $capability = urldecode($request->get_param('slug'));

            if (!empty($role_id)) {
                $result = $service->delete($capability, 'role', $role_id);
            } elseif (!empty($user_id)) {
                $result = $service->delete($capability, 'user', $user_id);
            } else {
                $result = $service->delete($capability);
            }

            $result = [ 'success' => $result ];
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
     * @version 6.9.12
     */
    public function check_permissions()
    {
        return current_user_can('aam_manager')
            && current_user_can('aam_manage_capabilities');
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
     * @version 6.9.33
     */
    private function _register_route($route, $args)
    {
        // Add the common arguments to all routes
        $args = array_merge_recursive(array(
            'args' => array(
                'role_id' => array(
                    'description'       => 'Role ID (aka slug)',
                    'type'              => 'string',
                    'validate_callback' => function ($value, $request) {
                        return $this->_validate_role_id($value, $request);
                    }
                ),
                'user_id' => array(
                    'description'       => 'User ID',
                    'type'              => 'integer',
                    'validate_callback' => function ($value, $request) {
                        return $this->_validate_user_id($value, $request);
                    }
                )
            )
        ), $args);

        register_rest_route(
            'aam/v2/service',
            $route,
            apply_filters(
                'aam_rest_route_args_filter', $args, $route, 'aam/v2/service'
            )
        );
    }

    /**
     * Validate role accessibility
     *
     * @param int             $value
     * @param WP_REST_Request $request
     *
     * @return boolean|WP_Error
     *
     * @access private
     * @version 6.9.33
     */
    private function _validate_role_id($value)
    {
        if (!empty($value)) {
            $result = $this->_validate_role_accessibility($value);
        } else {
            $result = true;
        }

        return $result;
    }

    /**
     * Validate user accessibility
     *
     * @param int             $value
     * @param WP_REST_Request $request
     *
     * @return boolean|WP_Error
     *
     * @access private
     * @version 6.9.33
     */
    private function _validate_user_id($value, $request)
    {
        if (!empty($value)) {
            $result = $this->_validate_user_accessibility(
                intval($value), $request->get_method()
            );
        } else {
            $result = true;
        }

        return $result;
    }

    /**
     * Get service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_Capabilities
     *
     * @access private
     * @version 6.9.33
     */
    private function _get_service(WP_REST_Request $request)
    {
        return AAM_Framework_Manager::capabilities([
            'subject'        => $this->_determine_subject($request),
            'error_handling' => 'exception'
        ]);
    }

    /**
     * Enrich capability model with additional attributes
     *
     * @param array           $output
     * @param WP_REST_Request $request
     *
     * @return array
     *
     * @access private
     * @version 6.9.33
     */
    private function _prepare_output($output, WP_REST_Request $request)
    {
        $fields = $this->_determine_fields($request);

        if (in_array('permissions', $fields, true)) {
            $output['permissions'] = $this->_prepare_permissions($output, $request);
        }

        // Filter out only properties that are requested
        $result = [];

        foreach($this->_determine_fields($request) as $field) {
            if (array_key_exists($field, $output)) {
                $result[$field] = $output[$field];
            }
        }

        return $result;
    }

    /**
     * Prepare permissions for give capability model
     *
     * @param array           $output
     * @param WP_REST_Request $request
     *
     * @return array
     *
     * @since 6.9.39 https://github.com/aamplugin/advanced-access-manager/issues/422
     * @since 6.9.33 Initial implementation of the method
     *
     * @access private
     * @version 6.9.39
     */
    private function _prepare_permissions($output, WP_REST_Request $request)
    {
        $result = [];

        $slug   = $output['slug'];
        $manage = AAM_Framework_Manager::configs()->get_config(
            'core.settings.editCapabilities', true
        );

        $update = apply_filters('aam_cap_can_filter', true, $slug, 'update');
        $delete = apply_filters('aam_cap_can_filter', true, $slug, 'delete');
        $toggle = apply_filters('aam_cap_can_filter', true, $slug, 'toggle');

        // Adding the permissions data as well
        if ($manage && $update !== false) {
            array_push($result, 'allow_update');
        }

        if ($manage && $delete !== false) {
            // Additional validation
            if ($request->get_param('user_id')) {
                $user_caps = AAM_Framework_Manager::subject([
                    'error_handling' => 'exception'
                ])->get('user', $request->get_param('user_id'))->caps;

                if (array_key_exists($slug, $user_caps)) {
                    array_push($result, 'allow_delete');
                }
            } else {
                array_push($result, 'allow_delete');
            }
        }

        if ($toggle !== false) {
            array_push($result, 'allow_toggle');
        }

        return $result;
    }

    /**
     * Determine list of additional fields to return
     *
     * @param WP_REST_Request $request
     *
     * @return array
     *
     * @access private
     * @version 6.9.33
     */
    private function _determine_fields(WP_REST_Request $request)
    {
        $result = ['slug'];
        $fields = $request->get_param('fields');

        if (!empty($fields) && is_string($fields)) {
            $result = array_merge($result, explode(',', $fields));
        }

        return array_unique($result);
    }

}