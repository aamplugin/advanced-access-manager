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
 * @package AAM
 * @version 7.0.0
 */
class AAM_Restful_Capability
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
                    ],
                    'is_granted' => [
                        'description' => 'Grant this cap to current access level',
                        'type'        => 'boolean',
                        'default'     => true
                    ]
                )
            ));

            // Update existing capability
            $this->_register_route('/capability/(?P<capability>.+)', [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [ $this, 'update_capability' ],
                'permission_callback' => [ $this, 'check_permissions' ],
                'args'                => [
                    'capability' => [
                        'description' => 'Existing capability slug',
                        'type'        => 'string',
                        'required'    => true
                    ],
                    'slug' => [
                        'description' => 'New capability slug',
                        'type'        => 'string',
                        'required'    => true
                    ],
                    'ignore_format' => [
                        'description' => 'Bypass the recommended by WP core standard',
                        'type'        => 'boolean',
                        'default'     => false
                    ],
                    'globally' => [
                        'description' => 'Wether this change affect only this access level or all',
                        'type'        => 'boolean',
                        'default'     => false
                    ]
                ]
            ]);

            // Delete existing capability
            $this->_register_route('/capability/(?P<slug>.+)', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_capability'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => [
                    'globally' => [
                        'description' => 'Wether this change affect only this access level or all',
                        'type'        => 'boolean',
                        'default'     => false
                    ]
                ]
            ));
        });
    }

    /**
     * Get list of all capabilities
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function get_list(WP_REST_Request $request)
    {
        try {
            if ($request->get_param('list_all')) {
                $caps = AAM::api()->caps->get_all_caps();
            } else {
                $access_level = $this->_determine_access_level($request);
                $caps         = $this->_get_access_level_caps($access_level);
            }

            // Return a pure and enriched array of capabilities
            $result = [];

            foreach($caps as $capability) {
                array_push($result, $this->_prepare_output($capability, $request));
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
     * @access public
     *
     * @version 7.0.0
     */
    public function create_capability(WP_REST_Request $request)
    {
        try {
            $capability    = urldecode($request->get_param('slug'));
            $ignore_format = $request->get_param('ignore_format');
            $is_granted    = $request->get_param('is_granted');

            // Step #1. Let's create the capability and automatically assign it to
            // the administrator role
            if (wp_roles()->is_role('administrator')) {
                AAM::api()->capabilities('role:administrator')->allow(
                    $capability, $ignore_format
                );
            }

            // Step #2. Assign the newly created capability to current access level
            $this->_get_service($request)->add(
                $capability, $is_granted, $ignore_format
            );

            // Step #3. Prepare the output
            $result = $this->_prepare_output($capability, $request);
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
     * @access public
     *
     * @version 7.0.0
     */
    public function update_capability(WP_REST_Request $request)
    {
        try {
            $capability    = urldecode($request->get_param('capability'));
            $slug          = $request->get_param('slug');
            $ignore_format = $request->get_param('ignore_format');
            $globally      = $request->get_param('globally');

            if ($globally) {
                // Iterating over the list of all roles and replace capabilities
                foreach(array_keys(wp_roles()->role_names) as $role_slug) {
                    AAM::api()->capabilities('role:' . $role_slug)->replace(
                        $capability, $slug, $ignore_format
                    );
                }

                // Finally do the same for the current user
                if (is_user_logged_in()) {
                    AAM::api()->capabilities()->replace(
                        $capability, $slug, $ignore_format
                    );
                }
            } else {
                $this->_get_service($request)->replace(
                    $capability, $slug, $ignore_format
                );
            }

            $result = $this->_prepare_output($slug, $request);
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
     * @access public
     *
     * @version 7.0.0
     */
    public function delete_capability(WP_REST_Request $request)
    {
        try {
            $capability = urldecode($request->get_param('slug'));
            $globally   = $request->get_param('globally');

            if ($globally) {
                // Iterating over the list of all roles and replace capabilities
                foreach(array_keys(wp_roles()->role_names) as $role_slug) {
                    AAM::api()->capabilities('role:' . $role_slug)->remove(
                        $capability
                    );
                }

                // Finally do the same for the current user
                if (is_user_logged_in()) {
                    AAM::api()->capabilities()->remove($capability);
                }
            } else {
                $this->_get_service($request)->remove($capability);
            }

            $result = [ 'success' => true ];
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
            && current_user_can('aam_manage_capabilities');
    }

    /**
     * Get service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_Capabilities
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_service(WP_REST_Request $request)
    {
        return AAM::api()->capabilities(
            $this->_determine_access_level($request),
            [ 'error_handling' => 'exception' ]
        );
    }

    /**
     * Get array of all capabilities added to given access level
     *
     * @param AAM_Framework_AccessLevel_Interface $access_level
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_access_level_caps($access_level)
    {
        if ($access_level::TYPE === AAM_Framework_Type_AccessLevel::USER) {
            $result = $access_level->allcaps;
        } elseif ($access_level::TYPE === AAM_Framework_Type_AccessLevel::ROLE) {
            $result = $access_level->capabilities;
        } else {
            $result = [];
        }

        return array_keys($result);
    }

    /**
     * Prepare the output
     *
     * @param string          $capability
     * @param WP_Rest_Request $request
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_output($capability, $request)
    {
        $service = $this->_get_service($request);
        $fields  = $this->_determine_fields($request);

        // Prepare the output model
        $output = [
            'slug'        => $capability,
            'description' => apply_filters(
                'aam_capability_description_filter', null, $capability
            ),
            'permissions' => $this->_prepare_permissions($capability, $service),
            'is_granted'  => $service->is_allowed($capability)
        ];

        // Prepare the final output
        $result = [];

        foreach($fields as $field) {
            if (array_key_exists($field, $output)) {
                $result[$field] = $output[$field];
            }
        }

        return $result;
    }

    /**
     * Prepare permissions for give capability model
     *
     * @param string                             $capability
     * @param AAM_Framework_Service_Capabilities $service
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_permissions($capability, $service)
    {
        $result = [];

        $manage = AAM::api()->config->get('service.capability.edit_caps');
        $update = apply_filters('aam_cap_can_filter', true, $capability, 'update');
        $delete = apply_filters('aam_cap_can_filter', true, $capability, 'delete');
        $toggle = apply_filters('aam_cap_can_filter', true, $capability, 'toggle');

        // Adding the permissions data as well
        if ($manage && $update !== false) {
            array_push($result, 'allow_update');
        }

        if ($manage && $delete !== false) {
            // Additional validation
            if ($service->exists($capability)) {
                array_push($result, 'allow_delete');
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
     * @access private
     *
     * @version 7.0.0
     */
    private function _determine_fields(WP_REST_Request $request)
    {
        $result = [ 'slug' ];
        $fields = $request->get_param('fields');

        if (!empty($fields) && is_string($fields)) {
            $result = array_merge($result, wp_parse_list($fields));
        }

        return array_unique($result);
    }

}