<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * REST API for the Ability service
 *
 * @package AAM
 * @version 8.0.0
 */
class AAM_Restful_Ability
{
    use AAM_Restful_ServiceTrait;

    /**
     * Permissions to manage this REST API
     * 
     * @var array
     * @version 8.0.0
     */
    const PERMISSIONS = [ 
        'aam_manager', 
        'aam_manage_abilities' 
    ];

    /**
     * Constructor
     * 
     * @version 8.0.0
     */
    protected function __construct()
    {
        add_action('rest_api_init', function() {
            $this->_register_route('/abilities', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_items' ]
            ], static::PERMISSIONS);

            $this->_register_route('/abilities', [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [ $this, 'reset_items' ]
            ], static::PERMISSIONS);

            $this->_register_route('/ability', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_item' ],
                'args' => [
                    'resource' => [
                        'type'        => 'string',
                        'required'    => true,
                        'description' => 'Registered ability name',
                        'validate_callback' => function ($value) {
                            return $this->_validate_ability($value);
                        }
                    ]
                ]
            ], static::PERMISSIONS);

            $this->_register_route('/ability', [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [ $this, 'update_item' ],
                'args' => [
                    'resource' => [
                        'type'        => 'string',
                        'required'    => true,
                        'description' => 'Registered ability name',
                        'validate_callback' => function ($value) {
                            return $this->_validate_ability($value);
                        }
                    ],
                    'effect' => [
                        'type' => 'string',
                        'required' => true,
                        'enum' => [ 'allow', 'deny' ]
                    ]
                ]
            ], static::PERMISSIONS);

            $this->_register_route( '/ability', [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [ $this, 'reset_item' ],
                'args' => [
                    'resource' => [
                        'type' => 'string',
                        'required' => true,
                        'description' => 'Registered ability name',
                        'validate_callback' => function ($value) {
                            return $this->_validate_ability($value);
                        }
                    ]
                ]
            ], static::PERMISSIONS);
        });
    }

    /**
     * Get all abilities
     * 
     * @param WP_REST_Request $request
     * 
     * @return WP_REST_Response|WP_Error|WP_HTTP_Response|mixed
     * @access public
     * 
     * @version 8.0.0
     */
    public function get_items(WP_REST_Request $request)
    {
        try {
            $result = apply_filters(
                'aam_rest_ability_access_list_filter', 
                 $this->_list_items($request), 
                $this->_determine_access_level($request)
            );
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Reset ability controls
     * 
     * @param WP_REST_Request $request
     * 
     * @return WP_REST_Response|WP_Error|WP_HTTP_Response|mixed
     * @access public
     * 
     * @version 8.0.0
     */
    public function reset_items(WP_REST_Request $request)
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
     * Reset ability access controls
     * 
     * @param WP_REST_Request $request
     * 
     * @return WP_REST_Response|WP_Error|WP_HTTP_Response|mixed
     * @access public
     * 
     * @version 8.0.0
     */
    public function reset_item(WP_REST_Request $request)
    {
        try {
            $resource = $request->get_param('resource');
            $service  = $this->_get_service($request);
            $result   = [ 'success' => $service->reset($resource) ];
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Get a single ability
     * 
     * @param WP_REST_Request $request
     * 
     * @return WP_REST_Response|WP_Error|WP_HTTP_Response|mixed
     * @access public
     * 
     * @version 8.0.0
     */
    public function get_item(WP_REST_Request $request)
    {
        try {
            $result = $this->_get_item($request);
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Update ability access controls
     * 
     * @param WP_REST_Request $request
     * 
     * @return WP_REST_Response|WP_Error|WP_HTTP_Response|mixed
     * @access public
     * 
     * @version 8.0.0
     */
    public function update_item(WP_REST_Request $request)
    {
        try {
            $resource = $request->get_param('resource');
            $effect   = $request->get_param('effect');
            $service  = $this->_get_service($request);

            if ($effect === 'allow') {
                $service->allow($resource);
            } else {
                $service->deny($resource);
            }

            $result = $this->_get_item($request);
        } catch (Exception $e) {
            $result = $this->_prepare_error_response($e);
        }

        return rest_ensure_response($result);
    }

    /**
     * Prepare the list of abilities
     * 
     * @param WP_REST_Request $request
     * 
     * @return array
     * @access private
     * 
     * @version 8.0.0
     */
    private function _list_items(WP_REST_Request $request)
    {
        $items = [];

        if (function_exists('wp_get_abilities')) {
            $service = $this->_get_service($request);

            foreach (wp_get_abilities() as $ability) {
                $name   = $ability->get_name();
                $meta   = $ability->get_meta();
                $mcp    = isset($meta['mcp']) && is_array($meta['mcp']) ? $meta['mcp'] : [];
                $public = isset($mcp['public']) ? (bool) $mcp['public'] : !empty($meta['public']);

                $items[] = [
                    'name'         => $name,
                    'label'        => $ability->get_label(),
                    'description'  => $ability->get_description(),
                    'category'     => $ability->get_category(),
                    'namespace'    => strtok($name, '/'),
                    'mcp_exposed'  => $public,
                    'rest_exposed' => !empty($meta['show_in_rest']),
                    'effect'       => $service->is_allowed($name) ? 'allow' : 'deny',
                    'customized'   => $service->is_customized($name)
                ];
            }
        }

        return $items;
    }

    /**
     * Get a single ability
     * 
     * @param WP_REST_Request $request
     * 
     * @return array
     * @access private
     * 
     * @version 8.0.0
     */
    private function _get_item($request)
    {
        $list     = $this->_list_items($request);
        $resource = $request->get_param('resource');
        $matched  = array_filter($list, function($i) use ($resource) {
            return $i['name'] === $resource;
        });

        if (empty($matched)) {
            throw new OutOfRangeException('Ability does not exist');
        } else {
            $result = array_shift($matched);
        }

        return $result;
    }

    /**
     * Validate ability
     * 
     * @param string $name
     * 
     * @return bool|WP_Error
     * @access private
     * 
     * @version 8.0.0
     */
    private function _validate_ability($name)
    {
        $found = $response = false;

        if (function_exists('wp_get_abilities')) {
            foreach (wp_get_abilities() as $ability) {
                if ($ability->get_name() === $name) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $response = new WP_Error(
                    'rest_invalid_param',
                    'The ability name is invalid',
                    [ 'status'  => 400 ]
                );
            } else {
                $response = true;
            }
        } else {
            $response = new WP_Error(
                'rest_invalid_param',
                'The ability functionality is not enabled on your server',
                [ 'status'  => 503 ]
            );
        }

        return $response;
    }

    /**
     * Get Ability framework service
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Framework_Service_Abilities
     * @access private
     *
     * @version 8.0.0
     */
    private function _get_service($request)
    {
        return AAM::api()->abilities(
            $this->_determine_access_level($request),
            [ 'error_handling' => 'exception' ]
        );
    }

}