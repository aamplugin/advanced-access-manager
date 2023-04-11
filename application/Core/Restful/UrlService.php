<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * RESTful API for the URL Access service
 *
 * @package AAM
 * @since 6.9.9
 */
class AAM_Core_Restful_UrlService
{

    /**
     * The namespace for the collection of endpoints
     */
    const NAMESPACE = 'aam/v2/service';

    /**
     * Single instance of itself
     *
     * @var AAM_Core_Restful_UrlService
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
     * @access protected
     * @version 6.9.9
     */
    protected function __construct()
    {
        // Register API endpoint
        add_action('rest_api_init', function() {
            // Get the list of rules
            $this->_register_route('/url', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_rule_list'),
                'permission_callback' => array($this, 'check_permissions'),
                'args' => array()
            ));

            // Create a new rule
            $this->_register_route('/url', array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'create_rule'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'url' => array(
                        'description' => __('URL or URI for the rule', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_url($value, $request);
                        }
                    ),
                    'type' => array(
                        'description' => __('Rule type', AAM_KEY),
                        'type'        => 'string',
                        'required'    => true,
                        'enum'        => array_values(
                            AAM_Framework_Service_Urls::RULE_TYPE_ALIAS
                        )
                    ),
                    'http_redirect_code' => array(
                        'description' => __('HTTP redirect code', AAM_KEY),
                        'type'        => 'number'
                    ),
                    'message' => array(
                        'description' => __('Custom access denied message', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_message($value, $request);
                        }
                    ),
                    'redirect_page_id' => array(
                        'description' => __('Existing page ID to redirect to', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_redirect_page_id($value, $request);
                        }
                    ),
                    'redirect_url' => array(
                        'description' => __('Valid URL to redirect to', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_redirect_url($value, $request);
                        }
                    ),
                    'callback' => array(
                        'description' => __('Custom callback function', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_callback($value, $request);
                        }
                    )
                )
            ));

            // Get a rule
            $this->_register_route('/url/(?<id>[\d]+)', array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_rule'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => __('URL unique ID', AAM_KEY),
                        'type'        => 'number',
                        'required'    => true
                    )
                )
            ));

            // Update an existing rule
            $this->_register_route('/url/(?<id>[\d]+)', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'update_rule'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => __('URL unique ID', AAM_KEY),
                        'type'        => 'number',
                        'required'    => true
                    ),
                    'url' => array(
                        'description' => __('URL or URI for the rule', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_url($value, $request);
                        }
                    ),
                    'type' => array(
                        'description' => __('Rule type', AAM_KEY),
                        'type'        => 'string',
                        'required'    => true,
                        'enum'        => array_values(
                            AAM_Framework_Service_Urls::RULE_TYPE_ALIAS
                        )
                    ),
                    'http_redirect_code' => array(
                        'description' => __('HTTP redirect code', AAM_KEY),
                        'type'        => 'number'
                    ),
                    'message' => array(
                        'description' => __('Custom access denied message', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_message($value, $request);
                        }
                    ),
                    'redirect_page_id' => array(
                        'description' => __('Existing page ID to redirect to', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_redirect_page_id($value, $request);
                        }
                    ),
                    'redirect_url' => array(
                        'description' => __('Valid URL to redirect to', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_redirect_url($value, $request);
                        }
                    ),
                    'callback' => array(
                        'description' => __('Custom callback function', AAM_KEY),
                        'type'        => 'string',
                        'validate_callback' => function ($value, $request) {
                            return $this->_validate_callback($value, $request);
                        }
                    )
                )
            ));

            // Delete a rule
            $this->_register_route('/url/(?<id>[\d]+)', array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_rule'),
                'permission_callback' => array($this, 'check_permissions'),
                'args'                => array(
                    'id' => array(
                        'description' => __('URL unique ID', AAM_KEY),
                        'type'        => 'number',
                        'required'    => true
                    )
                )
            ));

            // Reset all rules
            $this->_register_route('/url/reset', array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'reset_rules'),
                'permission_callback' => array($this, 'check_permissions'),
                'args' => array()
            ));
        });
    }

    /**
     * Get list of all editable roles
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @since 6.9.9
     */
    public function get_rule_list(WP_REST_Request $request)
    {
        $service = AAM_Framework_Manager::urls(
            new AAM_Framework_Model_ServiceContext(array(
                'subject' => $this->_determine_subject($request)
            ))
        );

        return rest_ensure_response($service->get_rule_list());
    }

    /**
     * Create new rule
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     *
     * @access public
     * @since 6.9.9
     */
    public function create_rule(WP_REST_Request $request)
    {
        $service = AAM_Framework_Manager::urls(
            new AAM_Framework_Model_ServiceContext(array(
                'subject' => $this->_determine_subject($request)
            ))
        );

        try {
            $result = $service->create_rule($request->get_params());
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
     * @since 6.9.9
     */
    public function get_rule(WP_REST_Request $request)
    {
        $service = AAM_Framework_Manager::urls(array(
            'subject' => $this->_determine_subject($request)
        ));

        try {
            $result = $service->get_rule_by_id(
                intval($request->get_param('id'))
            );
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
     * @since 6.9.9
     */
    public function update_rule(WP_REST_Request $request)
    {
        $service = AAM_Framework_Manager::urls(array(
            'subject' => $this->_determine_subject($request)
        ));

        try {
            $result = $service->update_rule(
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
     * @since 6.9.9
     */
    public function delete_rule(WP_REST_Request $request)
    {
        $service = AAM_Framework_Manager::urls(array(
            'subject' => $this->_determine_subject($request)
        ));

        try {
            $result = $service->delete_rule(intval($request->get_param('id')));
        } catch (UnderflowException $e) {
            $result = $this->_prepare_error_response($e, 'rest_not_found', 404);
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
     * @since 6.9.9
     */
    public function reset_rules(WP_REST_Request $request)
    {
        $service = AAM_Framework_Manager::urls(array(
            'subject' => $this->_determine_subject($request)
        ));

        try {
            $result = $service->reset_rules();
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
     * @since 6.9.9
     */
    public function check_permissions() {
        return current_user_can('aam_manager')
                && (current_user_can('aam_manage_uri')
                || current_user_can('aam_manage_url_access'));
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
     */
    private function _register_route($route, $args)
    {
        // Add the common arguments to all routes
        $args = array_merge(array(
            'args' => array(
                'access_level' => array(
                    'description' => __('Access level for the controls', AAM_KEY),
                    'type'        => 'string',
                    'enum'        => array(
                        AAM_Core_Subject_Role::UID,
                        AAM_Core_Subject_User::UID,
                        AAM_Core_Subject_Visitor::UID,
                        AAM_Core_Subject_Default::UID
                    )
                ),
                'role_id' => array(
                    'description' => __('Role ID (aka slug)', AAM_KEY),
                    'type'        => 'string',
                    'validate_callback' => function ($value, $request) {
                        return $this->_validate_role_id($value, $request);
                    }
                ),
                'user_id' => array(
                    'description' => __('User ID', AAM_KEY),
                    'type'        => 'integer',
                    'validate_callback' => function ($value, $request) {
                        return $this->_validate_user_id($value, $request);
                    }
                )
            )
        ), $args);

        register_rest_route(
            self::NAMESPACE,
            $route,
            apply_filters(
                'aam_rest_route_args_filter', $args, $route, self::NAMESPACE
            )
        );
    }

    /**
     * Determine current subject
     *
     * @param WP_REST_Request $request
     *
     * @return AAM_Core_Subject
     *
     * @access private
     * @since 6.9.9
     */
    private function _determine_subject(WP_REST_Request $request)
    {
        $access_level = $request->get_param('access_level');
        $subject_id   = null;

        if ($access_level === AAM_Core_Subject_Role::UID) {
            $subject_id = $request->get_param('role_id');
        } elseif ($access_level === AAM_Core_Subject_User::UID) {
            $subject_id = $request->get_param('user_id');
        }

        return AAM_Framework_Manager::subject()->get($access_level, $subject_id);
    }

    /**
     * Validate the input value "role_id"
     *
     * @param string|null     $value   Input value
     * @param WP_REST_Request $request Request
     *
     * @return bool|WP_Error
     *
     * @access private
     */
    private function _validate_role_id($value, $request)
    {
        $response     = true;
        $access_level = $request->get_param('access_level');
        $is_empty     = !is_string($value) || strlen($value) === 0;

        if ($access_level === AAM_Core_Subject_Role::UID) {
            if ($is_empty) {
                $response = new WP_Error(
                    'rest_invalid_param',
                    __('The role_id is required', AAM_KEY),
                    array('status'  => 400)
                );
            } else { // Verifying that the role exists and is accessible
                $response = $this->_validate_role_accessibility($value);
            }
        } elseif (!$is_empty) {
            $response = new WP_Error(
                'rest_invalid_param',
                __('The role_id param works only with access_level=role', AAM_KEY),
                array('status'  => 400)
            );
        }

        return $response;
    }

    /**
     * Validate the input value "user_id"
     *
     * @param string|null     $value   Input value
     * @param WP_REST_Request $request Request
     *
     * @return bool|WP_Error
     *
     * @access private
     */
    private function _validate_user_id($value, $request)
    {
        $response = true;

        if (is_numeric($value)) {
            $access_level = $request->get_param('access_level');

            if ($access_level !== AAM_Core_Subject_User::UID) {
                $response = new WP_Error(
                    'rest_invalid_param',
                    __('The user_id param works only with access_level=user', AAM_KEY),
                    array('status'  => 400)
                );
            } else { // Verifying that the user exists and is accessible
                $response = $this->_validate_user_accessibility(intval($value));
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
     * Validate user accessibility
     *
     * @param int $user_id User ID
     *
     * @return bool|WP_Error
     *
     * @access private
     */
    private function _validate_user_accessibility($user_id)
    {
        $response = true;

        $user = apply_filters('aam_get_user', get_user_by('id', $user_id));

        if ($user === false || is_wp_error($user)) {
            $response = new WP_Error(
                'rest_not_found',
                sprintf(
                    __("The user with ID '%s' does not exist or is not editable"),
                    $user_id
                ),
                array('status'  => 404)
            );
        }

        return $response;
    }

    /**
     * Validate the 'url' param
     *
     * @param string $value
     *
     * @return boolean|WP_Error
     *
     * @access private
     * @since 6.9.9
     */
    private function _validate_url($value)
    {
        $response = true;
        $url      = wp_validate_redirect($value);

        if (empty($url)) {
            $response = new WP_Error(
                'rest_invalid_param',
                __('The url is not a valid URL', AAM_KEY),
                array('status'  => 400)
            );
        }

        return $response;
    }

    /**
     * Validate custom message
     *
     * @param string           $value
     * @param WP_REST_Request $request
     *
     * @return boolean|WP_Error
     *
     * @access private
     * @since 6.9.9
     */
    private function _validate_message($value, $request)
    {
        $response  = true;
        $rule_type = $request->get_param('type');
        $message   = esc_js(trim($value));

        if ($rule_type === 'custom_message' && strlen($message) === 0) {
            $response = new WP_Error(
                'rest_invalid_param',
                __('The custom_message cannot be empty or be unsafe', AAM_KEY),
                array('status'  => 400)
            );
        }

        return $response;
    }

    /**
     * Validate redirect page ID
     *
     * @param int             $value
     * @param WP_REST_Request $request
     *
     * @return boolean|WP_Error
     *
     * @access private
     * @since 6.9.9
     */
    private function _validate_redirect_page_id($value, $request)
    {
        $response  = true;
        $rule_type = $request->get_param('type');
        $page_id   = intval($value);

        if ($rule_type === 'page_redirect') {
            if ($page_id === 0 || get_post($page_id) === null) {
                $response = new WP_Error(
                    'rest_invalid_param',
                    __('The redirect_page_id refers to non-existing page', AAM_KEY),
                    array('status'  => 400)
                );
            }
        }

        return $response;
    }

    /**
     * Validate redirect URL
     *
     * @param string          $value
     * @param WP_REST_Request $request
     *
     * @return boolean|WP_Error
     *
     * @access private
     * @since 6.9.9
     */
    private function _validate_redirect_url($value, $request)
    {
        $response  = true;
        $rule_type = $request->get_param('type');
        $url       = wp_validate_redirect($value);

        if ($rule_type === 'url_redirect' && empty($url)) {
            $response = new WP_Error(
                'rest_invalid_param',
                __('The redirect_url is not valid URL', AAM_KEY),
                array('status'  => 400)
            );
        }

        return $response;
    }

    /**
     * Validate the callback value
     *
     * @param string          $value
     * @param WP_REST_Request $request
     *
     * @return boolean|WP_Error
     *
     * @access private
     * @since 6.9.9
     */
    private function _validate_callback($value, $request)
    {
        $response  = true;
        $rule_type = $request->get_param('type');

        if ($rule_type === 'trigger_callback' && is_callable($value, true) === false) {
            $response = new WP_Error(
                'rest_invalid_param',
                __('The callback is not valid PHP callback', AAM_KEY),
                array('status'  => 400)
            );
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
     */
    public static function bootstrap()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

}