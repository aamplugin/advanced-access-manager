<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Users & Roles Governance service
 *
 * @package AAM
 * @version 6.9.28
 */
class AAM_Service_IdentityGovernance
{
    use AAM_Core_Contract_RequestTrait,
        AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the feature
     *
     * @version 6.9.28
     */
    const FEATURE_FLAG = 'core.service.user-governance.enabled';

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
        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
                add_action('aam_init_ui_action', function () {
                    AAM_Backend_Feature_Main_IdentityGovernance::register();
                });
            }

            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('User Governance', AAM_KEY),
                    'description' => __('Manager how other users and unauthenticated visitors can see and manage registered users on the site.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 20);
        }

        if (AAM_Core_Config::get(self::FEATURE_FLAG, true)) {
            $this->initializeHooks();
        }
    }

    /**
     * Initialize service hooks
     *
     * @return void
     *
     * @access protected
     * @version 6.9.28
     */
    protected function initializeHooks()
    {
        // Register RESTful API endpoints
        AAM_Core_Restful_IdentityGovernanceService::bootstrap();

        add_action('init', function() {
            add_filter('editable_roles', array($this, 'filter_roles'));
            add_action('pre_get_users', array($this, 'filter_users'), 999);
            add_filter('views_users', array($this, 'filter_users_in_view'));
            // RESTful user querying
            add_filter('rest_user_query', array($this, 'rest_user_query_args'));
        }, 1);

        // Check if user has ability to perform certain task on other users
        add_filter('map_meta_cap', array($this, 'map_meta_caps'), 999, 4);

        // Additionally tap into password management
        add_filter('show_password_fields', array($this, 'can_change_password'), 10, 2);
        add_filter('allow_password_reset', array($this, 'can_reset_password'), 10, 2);
        add_action('check_passwords', array($this, 'can_update_password'), 10, 3);
        add_filter('rest_pre_insert_user', array($this, 'can_update_restful_password'), 10, 2);
    }

    /**
     * Filter list of allowed roles
     *
     * @param array $roles
     *
     * @return array
     *
     * @access public
     * @version 6.9.28
     */
    public function filter_roles($roles)
    {
        foreach (array_keys($roles) as $slug) {
            // Filter out all the roles that are explicitly hidden with "Roles" rule
            // type or implicitly by the specified level_n capability
            if (!$this->can_list_role($slug)) {
                unset($roles[$slug]);
            }
        }

        return $roles;
    }

    /**
     * Filter user query
     *
     * Exclude all users that have higher user level
     *
     * @param object $query
     *
     * @return void
     *
     * @access public
     * @version 6.9.28
     */
    public function filter_users($query)
    {
        $query->query_vars = array_merge(
            $query->query_vars,
            $this->_prepare_filter_args()
        );
    }

    /**
     * Filter users for RESTful API calls
     *
     * @param array $args
     *
     * @return array
     *
     * @access public
     * @version 6.9.28
     */
    public function rest_user_query_args($args)
    {
        return array_merge($args, $this->_prepare_filter_args());
    }

    /**
     * Filter list of roles in the "Users" table
     *
     * The top list of roles requires some additional filtering
     *
     * @param array $views
     *
     * @return array
     *
     * @access public
     * @version 6.9.28
     */
    public function filter_users_in_view($views)
    {
        foreach(array_keys($views) as $slug) {
            if ($slug !== 'all' && !$this->can_list_role($slug)) {
                unset($views[$slug]);
            }
        }

        return $views;
    }

    /**
     * Check if role can be listed to the current user
     *
     * @param string $role_slug
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.28
     */
    public function can_list_role($role_slug)
    {
        $object = AAM::getUser()->getObject(
            AAM_Core_Object_IdentityGovernance::OBJECT_TYPE
        );

        // Get max user level
        if ($role_slug === '*'){
            $max_level = 0;
        } else {
            $max_level = AAM_Core_API::maxLevel(
                AAM_Core_API::getRoles()->get_role($role_slug)->capabilities
            );
        }

        return $object->is_allowed_to('role', $role_slug, 'list_role') !== false
            && $object->is_allowed_to('role_level', $max_level, 'list_role') !== false;
    }

    /**
     * Check if user can change other user's password
     *
     * This method determines if password change fields are going to be displayed
     *
     * @param boolean $result
     * @param WP_User $user
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.28
     */
    public function can_change_password($result, $user)
    {
        $is_profile = $user->ID === get_current_user_id();

        if (!$is_profile && !current_user_can('aam_change_password', $user->ID)) {
            $result = false;
        }

        return $result;
    }

    /**
     * Check if user can reset other user's password
     *
     * This method determines if password reset fields are going to be displayed
     *
     * @param boolean $result
     * @param int     $user
     *
     * @return boolean
     *
     * @access public
     * @version 6.9.28
     */
    public function can_reset_password($result, $user_id)
    {
        $is_profile = $user_id === get_current_user_id();

        if (!$is_profile && !current_user_can('aam_change_password', $user_id)) {
            $result = false;
        }

        return $result;
    }

    /**
     * Check if user can update other user's password
     *
     * @param mixed  $login
     * @param string $password
     * @param string $password2
     *
     * @return void
     *
     * @access public
     * @version 6.9.28
     */
    public function can_update_password($login, &$password, &$password2)
    {
        $user       = get_user_by('login', $login);
        $is_profile = $user->ID === get_current_user_id();

        if (!$is_profile && !current_user_can('aam_change_password', $user->ID)) {
            $password = $password2 = null;
        }
    }

    /**
     * Check if user can update other user's password through RESTful API
     *
     * @param object          $data
     * @param WP_REST_Request $request
     *
     * @return object
     *
     * @access public
     * @version 6.9.28
     */
    public function can_update_restful_password($data, $request)
    {
        $user       = get_user_by('id', $request['id']);
        $is_profile = $user->ID === get_current_user_id();

        if (!$is_profile
            && !current_user_can('aam_change_password', $user->ID)
            && property_exists($data, 'user_pass')
        ) {
            unset($data->user_pass);
        }

        return $data;
    }

    /**
     * Check user capability
     *
     * This add additional layout on top of WordPress core functionality. Based on
     * the capability passed in the $args array as "0" element, it performs additional
     * check on user's ability to manage other users
     *
     * @param array  $caps
     * @param string $cap
     * @param int    $_
     * @param array  $args
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function map_meta_caps($caps, $cap, $_, $args)
    {
        $id = (isset($args[0]) ? $args[0] : null);

        // If targeting user ID is not provided, no need to do anything
        if (!empty($id)) {
            if ($cap === 'promote_user') {
                $caps = $this->_authorize_user_action(
                    'change_user_role', $id, $caps
                );
            } elseif ($cap === 'edit_user') {
                $caps = $this->_authorize_user_action('edit_user', $id, $caps);
            } elseif ($cap === 'delete_user') {
                $caps = $this->_authorize_user_action('delete_user', $id, $caps);
            } elseif ($cap === 'aam_change_password') {
                $caps = $this->_authorize_user_action(
                    'change_user_password', $id, $caps
                );
            }
        }

        return $caps;
    }

    /**
     * Prepare filter arguments for the user query object
     *
     * @return array
     *
     * @access private
     * @version 6.9.28
     */
    private function _prepare_filter_args()
    {
        global $wpdb;

        $response = [];

        // Exclude any users and role that are not allowed to be listed
        $options = AAM::getUser()->getObject(
            AAM_Core_Object_IdentityGovernance::OBJECT_TYPE
        )->getOption();

        // Making sure that query var properties are properly initialized
        $role__not_in  = [];
        $login__not_in = [];
        $target_levels = [];

        foreach($options as $target => $permissions) {
            $rule_type = explode('|', $target);

            if (isset($permissions['list_user'])
                && $permissions['list_user'] === 'deny'
            ) {
                if ($rule_type[0] === 'user_role') {
                    array_push($role__not_in, $rule_type[1]);
                } elseif ($rule_type[0] === 'user' && $rule_type[1] !== '*') {
                    array_push($login__not_in, $rule_type[1]);
                } elseif ($rule_type[0] === 'user_level') {
                    array_push($target_levels, intval($rule_type[1]));
                }
            }
        }

        if (count($role__not_in)) {
            $response['role__not_in'] = $role__not_in;
        }

        if (count($login__not_in)) {
            $response['login__not_in'] = $login__not_in;
        }

        if (count($target_levels) > 0) {
            $response['meta_key']     = $wpdb->get_blog_prefix() . 'user_level';
            $response['meta_value']   = $target_levels;
            $response['meta_compare'] = 'NOT IN';
            $response['meta_type']    = 'NUMERIC';
        }

        return $response;
    }

    /**
     * Determine if current user can perform provide action against other user
     *
     * @param string $action
     * @param int    $user_id
     * @param array  $caps
     *
     * @return array
     *
     * @access private
     * @version 6.9.28
     */
    private function _authorize_user_action($action, $user_id, $caps)
    {
        // If do not allow is declared, there is no need to do anything else
        if (!in_array('do_not_allow', $caps, true)) {
            $user   = get_user_by('id', $user_id);
            $object = AAM::getUser()->getObject(
                AAM_Core_Object_IdentityGovernance::OBJECT_TYPE
            );

            if (is_a($user, 'WP_User')) {
                $checks = array(
                    ['user', $user->user_login, $action],
                    ['user_level', $user->user_level, $action]
                );

                if (is_array($user->roles)) {
                    foreach($user->roles as $role) {
                        array_push($checks, ['user_role', $role, $action]);
                    }
                }

                foreach($checks as $args) {
                    if (call_user_func_array([$object, 'is_allowed_to'], $args) === false) {
                        $caps[] = 'do_not_allow';

                        break; // no need to check further
                    }
                }
            }
        }

        return $caps;
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_IdentityGovernance::bootstrap();
}