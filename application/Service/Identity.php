<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Users & Roles (aka Identity) Governance service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Service_Identity
{

    use AAM_Service_BaseTrait;

    /**
     * WordPress core user caps mapped to AAM permissions
     *
     * @var array
     * @access private
     *
     * @version 7.0.0
     */
    private $_user_caps_to_listen = [
        'edit_user'        => 'edit_user',
        'edit_user_meta'   => 'edit_user',
        'add_user_meta'    => 'edit_user',
        'delete_user_meta' => 'edit_user',
        'promote_user'     => 'promote_user',
        'delete_user'      => 'delete_user',
        'remove_user'      => 'delete_user'
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
        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            add_action('aam_initialize_ui_action', function () {
                AAM_Backend_Feature_Main_Identity::register();
            });
        }

        // Register RESTful API endpoints
        AAM_Restful_Identity::bootstrap();

        $this->initialize_hooks();
    }

    /**
     * Initialize service hooks
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function initialize_hooks()
    {
        // Control the list of editable roles
        add_filter('editable_roles', function($roles) {
            return $this->_filter_editable_roles($roles);
        }, 10, 1);

        // Control list of roles that are listed above the Users table
        add_filter('views_users', function($roles) {
            return $this->_filter_views_users($roles);
        }, 10, 1);

        // Filter the list of users
        add_action('pre_get_users', function($query) {
            $this->_pre_get_users($query);
        }, PHP_INT_MAX, 1);

        // RESTful user querying
        add_filter('rest_user_query', function($args) {
            return $this->_rest_user_query($args);
        }, PHP_INT_MAX, 1);

        // Check if user has ability to perform certain task on other users
        add_filter('map_meta_cap', function($caps, $cap, $_, $args) {
            return $this->_map_meta_cap($caps, $cap, $args);
        }, PHP_INT_MAX, 4);

        // Additionally tap into password management
        add_filter('show_password_fields', function($result, $user) {
            return $this->_show_password_fields($result, $user);
        }, 10, 2);
        add_filter('allow_password_reset', function($result, $user_id) {
            return $this->_allow_password_reset($result, $user_id);
        }, 10, 2);
        add_action('check_passwords', function($login, &$pwd1, &$pwd2) {
            if (!AAM::api()->misc->is_super_admin()) {
                $this->_check_passwords($login, $pwd1, $pwd2);
            }
        }, 10, 3);
        add_filter('rest_pre_insert_user', function($data, $request) {
            return $this->_rest_pre_insert_user($data, $request);
        }, 10, 2);
    }

    /**
     * Filter list of allowed roles
     *
     * @param array $roles
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _filter_editable_roles($roles)
    {
        $service = AAM::api()->roles();

        foreach (array_keys($roles) as $slug) {
            if ($service->is_hidden($slug)) {
                unset($roles[$slug]);
            }
        }

        return $roles;
    }

    /**
     * Filter list of roles in the "Users" table
     *
     * The top list of roles requires some additional filtering
     *
     * @param array $views
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _filter_views_users($views)
    {
        $service = AAM::api()->roles();

        foreach(array_keys($views) as $slug) {
            if ($slug !== 'all' && $service->is_hidden($slug)) {
                unset($views[$slug]);
            }
        }

        return $views;
    }

    /**
     * Filter user query
     *
     * Exclude all users that have higher user level
     *
     * @param object $query
     *
     * @return void
     * @access private
     *
     * @version 7.0.0
     */
    private function _pre_get_users($query)
    {
        $query->query_vars = $this->_prepare_filter_args($query->query_vars);
    }

    /**
     * Filter users for RESTful API calls
     *
     * @param array $args
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _rest_user_query($args)
    {
        return $this->_prepare_filter_args($args);
    }

    /**
     * Prepare filter arguments for the user query object
     *
     * @param array $args
     *
     * @return array
     * @access private
     *
     * @version 7.0.1
     */
    private function _prepare_filter_args($args)
    {
        // Identify the list roles & users that are hidden
        $role_aggregate = AAM::api()->roles()->aggregate();

        // Extract the list of user roles
        $roles_not_in = [];
        $users_not_in = [];

        foreach($role_aggregate as $role_id => $perms) {
            if (array_key_exists('list_user', $perms)
                && $perms['list_user']['effect'] === 'deny') {
                    array_push($roles_not_in, $role_id);
            }
        }

        $user_aggregate = AAM::api()->users()->aggregate();

        foreach($user_aggregate as $user_id => $perms) {
            if (array_key_exists('list_user', $perms)
                && is_numeric($user_id)
                && $perms['list_user']['effect'] === 'deny') {
                    array_push($users_not_in, $user_id);
            }
        }

        if (!empty($args['include'])) {
            $include         = array_diff((array) $args['include'], $users_not_in);
            $args['include'] = empty($include) ? [ 0 ] : $include;
        } elseif (!empty($args['exclude'])) {
            $args['exclude'] = array_unique(array_merge(
                (array) $args['exclude'],
                $users_not_in
            ));
        } else {
            $args['exclude'] = $users_not_in;
        }

        // Customize the user query accordingly to the permissions defined above
        if (!empty($args['role__in'])) {
            // Remove roles that are hidden
            $role__in         = array_diff((array) $args['role__in'], $roles_not_in);
            $args['role__in'] = empty($role__in) ? [ 'do_not_allow' ] : $role__in;
        } elseif (!empty($args['role__not_in'])) {
            $args['role__not_in'] = array_unique(array_merge(
                (array) $args['role__not_in'],
                $roles_not_in
            ));
        } else {
            $args['role__not_in'] = $roles_not_in;
        }

        return apply_filters('aam_user_query_args_filter', $args, [
            'users' => $user_aggregate,
            'roles' => $roles_not_in
        ]);
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
     * @param array  $args
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _map_meta_cap($caps, $cap, $args)
    {
        $user_id = (isset($args[0]) ? $args[0] : null);

        // If targeting user ID is not provided, no need to do anything
        if (!empty($user_id)
            && in_array($cap, array_keys($this->_user_caps_to_listen), true)
        ) {
            if (AAM::api()->users()->is_denied_to($user_id, $cap)) {
                array_push($caps, 'do_not_allow');
            }
        }

        return $caps;
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
     * @access private
     *
     * @version 7.0.0
     */
    private function _show_password_fields($result, $user)
    {
        $is_profile = $user->ID === get_current_user_id();

        $user->ID;

        if (!$is_profile) {
            $result = AAM::api()->users()->is_allowed_to(
                $user, 'change_user_password'
            );
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
     * @access private
     *
     * @version 7.0.0
     */
    private function _allow_password_reset($result, $user_id)
    {
        $is_profile = ($user_id === get_current_user_id());

        if (!$is_profile) {
            $result = AAM::api()->users()->is_allowed_to(
                $user_id, 'change_user_password'
            );
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
     * @access private
     *
     * @version 7.0.0
     */
    private function _check_passwords($login, &$password, &$password2)
    {
        $user = get_user_by('login', $login);

        // Take into consideration scenario when new user is being created
        if (is_a($user, 'WP_User')) {
            $is_profile = $user->ID === get_current_user_id();

            if (!$is_profile) {
                if (AAM::api()->users()->is_denied_to($user, 'change_user_password')) {
                    $password = $password2 = null;
                }
            }
        }
    }

    /**
     * Check if user can update other user's password through RESTful API
     *
     * @param object          $data
     * @param WP_REST_Request $request
     *
     * @return object
     * @access private
     *
     * @version 7.0.0
     */
    private function _rest_pre_insert_user($data, $request)
    {
        $user = get_user_by('id', $request['id']);

        // Take into consideration scenario when new user is being created
        if (is_a($user, 'WP_User')) {
            $is_profile = $user->ID === get_current_user_id();

            if (!$is_profile && property_exists($data, 'user_pass')) {
                if (AAM::api()->users()->is_denied_to($user, 'change_user_password')) {
                    unset($data->user_pass);
                }
            }
        }

        return $data;
    }

}