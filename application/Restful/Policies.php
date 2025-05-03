<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for the Access Policies service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Restful_Policies
{

    use AAM_Restful_ServiceTrait;

    /**
     * Necessary permissions to access endpoint
     *
     * @version 7.0.0
     */
    const PERMISSIONS = [
        'aam_manager',
        'aam_manage_policies'
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
            // Get the list of all policies
            $this->_register_route('/policies', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_policies' ],
                'args'     => array(
                    'fields' => array(
                        'description' => 'List of additional fields to return',
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_fields_input($value);
                        }
                    )
                )
            ], self::PERMISSIONS);

            // Get a single policy
            $this->_register_route('/policy/(?P<id>[\d]+)', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_policy' ],
                'args'     => [
                    'id' => [
                        'description' => 'Policy ID',
                        'type'        => 'number',
                        'required'    => true
                    ],
                    'fields' => array(
                        'description' => 'List of additional fields to return',
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_fields_input($value);
                        }
                    )
                ]
            ], self::PERMISSIONS);

            // Create new policy
            $this->_register_route('/policies', [
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => [ $this, 'create_policy' ],
                'args'     => [
                    'policy' => [
                        'description' => 'JSON policy',
                        'type'        => 'string',
                        'required'    => true
                    ],
                    'title' => [
                        'description' => 'Policy title',
                        'type'        => 'string',
                        'required'    => false
                    ],
                    'excerpt' => [
                        'description' => 'Policy short description',
                        'type'        => 'string',
                        'required'    => false
                    ],
                    'effect' => [
                        'description' => 'Attach or detach policy',
                        'type'        => 'string',
                        'required'    => false,
                        'default'     => 'attach',
                        'enum'        => [ 'attach', 'detach' ]
                    ],
                    'status' => [
                        'description' => 'Policy status',
                        'type'        => 'string',
                        'required'    => false,
                        'default'     => 'publish'
                    ],
                    'fields' => array(
                        'description' => 'List of additional fields to return',
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_fields_input($value);
                        }
                    )
                ]
            ], self::PERMISSIONS);

            // Attach/detach policy
            $this->_register_route('/policy/(?P<id>[\d]+)', [
                'methods'  => WP_REST_Server::EDITABLE,
                'callback' => [ $this, 'toggle_policy' ],
                'args'     => [
                    'id' => [
                        'description' => 'Policy ID',
                        'type'        => 'number',
                        'required'    => true
                    ],
                    'effect' => [
                        'description' => 'Attach or detach policy',
                        'type'        => 'string',
                        'required'    => true,
                        'enum'        => [ 'attach', 'detach' ]
                    ],
                    'fields' => array(
                        'description' => 'List of additional fields to return',
                        'type'        => 'string',
                        'validate_callback' => function ($value) {
                            return $this->_validate_fields_input($value);
                        }
                    )
                ]
            ], self::PERMISSIONS);

            // Delete policy
            $this->_register_route('/policy/(?P<id>[\d]+)', [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => [ $this, 'delete_policy' ],
                'args'     => [
                    'id' => [
                        'description' => 'Policy ID',
                        'type'        => 'number',
                        'required'    => true
                    ]
                ]
            ], self::PERMISSIONS);

            // Reset policy settings
            $this->_register_route('/policies', [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => [ $this, 'reset_policies' ]
            ], self::PERMISSIONS);
        });
    }

    /**
     * Get list of all registered policies
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function get_policies(WP_REST_Request $request)
    {
        try {
            $result = [];

            foreach($this->_get_service($request)->policies() as $policy) {
                array_push($result, $this->_prepare_policy_item($policy, $request));
            }
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Toggle registered policy
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function toggle_policy(WP_REST_Request $request)
    {
        try {
            $policy_id = intval($request->get_param('id'));
            $effect    = $request->get_param('effect');

            if ($effect === 'attach') {
                $this->_get_service($request)->attach($policy_id);
            } else {
                $this->_get_service($request)->detach($policy_id);
            }

            $result = $this->_prepare_policy_item(
                $this->_get_service($request)->policy($policy_id), $request
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Create new policy
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function create_policy(WP_REST_Request $request)
    {
        try {
            $service = $this->_get_service($request);

            $policy_id = $service->create(
                [
                    'json'    => $request->get_param('policy'),
                    'title'   => $request->get_param('title'),
                    'excerpt' => $request->get_param('excerpt')
                ],
                $request->get_param('status'),
                $request->get_param('effect')
            );

            $result = $this->_prepare_policy_item(
                $service->policy($policy_id), $request
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get a single registered policy
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function get_policy(WP_REST_Request $request)
    {
        try {
            $policy = $this->_get_service($request)->policy(
                intval($request->get_param('id'))
            );

            $result = $this->_prepare_policy_item($policy, $request);
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Delete a policy
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function delete_policy(WP_REST_Request $request)
    {
        try {
            $policy_id = intval($request->get_param('id'));

            if (current_user_can('aam_delete_policy', $policy_id)) {
                wp_delete_post($policy_id);
            }

            $result = [ 'success' => true ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset policy settings
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     * @access public
     *
     * @version 7.0.0
     */
    public function reset_policies(WP_REST_Request $request)
    {
        try {
            $result = [ 'success' => $this->_get_service($request)->reset() ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Prepare policy item output
     *
     * @param array           $policy
     * @param WP_REST_Request $request
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _prepare_policy_item($policy, $request)
    {
        // Determine list of fields to return
        $fields = $request->get_param('fields');
        $fields = empty($fields) ? [] : wp_parse_list($fields);
        $fields = array_unique(array_merge(
            [ 'id', 'title', 'status', 'is_attached' ], $fields
        ));

        // Determine the list of permissions
        $permissions = [
            'toggle_policy'
        ];

        if (current_user_can('aam_edit_policy', $policy['id'])) {
            array_push($permissions, 'edit_policy');
        }

        if (current_user_can('aam_delete_policy', $policy['id'])) {
            array_push($permissions, 'delete_policy');
        }

        return array_filter([
            'id'          => $policy['id'],
            'title'       => $policy['ref']->post_title,
            'status'      => $policy['status'],
            'excerpt'     => $policy['ref']->post_excerpt,
            'json'        => $policy['json'],
            'is_attached' => $policy['is_attached'],
            'permissions' => $permissions
        ], function($k) use ($fields) {
            return in_array($k, $fields, true);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Get Policies framework service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_Policies
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_service($request)
    {
        return AAM::api()->policies(
            $this->_determine_access_level($request),
            [ 'error_handling' => 'exception' ]
        );
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

}