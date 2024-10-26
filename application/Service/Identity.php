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

    use AAM_Core_Contract_RequestTrait,
        AAM_Core_Contract_ServiceTrait;

    /**
     * AAM configuration setting that is associated with the feature
     *
     * @version 7.0.0
     */
    const FEATURE_FLAG = 'service.identity.enabled';

    /**
     * Constructor
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function __construct()
    {
        add_filter('aam_get_config_filter', function($result, $key) {
            if ($key === self::FEATURE_FLAG && is_null($result)) {
                $result = false;
            }

            return $result;
        }, 10, 2);

        $enabled = AAM::api()->configs()->get_config(self::FEATURE_FLAG);

        if (is_admin()) {
            // Hook that initialize the AAM UI part of the service
            if ($enabled) {
                add_action('aam_initialize_ui_action', function () {
                    AAM_Backend_Feature_Main_Identity::register();
                });
            }

            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'       => __('Identity Governance', AAM_KEY),
                    'description' => __('Control how other users and unauthenticated visitors can view and manage the profiles of registered users on the site.', AAM_KEY),
                    'setting'     => self::FEATURE_FLAG
                );

                return $services;
            }, 20);
        }

        if ($enabled) {
            $this->initialize_hooks();
        }
    }

    /**
     * Initialize service hooks
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function initialize_hooks()
    {
        // Register the resource
        add_filter(
            'aam_get_resource_filter',
            function($resource, $access_level, $resource_type) {
                if (is_null($resource)
                    && $resource_type === AAM_Framework_Type_Resource::IDENTITY
                ) {
                    $resource = new AAM_Framework_Resource_Identity(
                        $access_level
                    );
                }

                return $resource;
            }, 10, 3
        );

        // Register RESTful API endpoints
        AAM_Restful_IdentityService::bootstrap();

        // Control the list of editable roles
        add_filter('editable_roles', function($roles) {
            return $this->_filter_editable_roles($roles);
        });

        // Control list of roles that are listed above the Users table
        add_filter('views_users', function($views) {
            return $this->_filter_views_users($views);
        });

        // Filter the list of users
        add_action('pre_get_users', function($query) {
            $this->_pre_get_users($query);
        }, 999);

        // RESTful user querying
        add_filter('rest_user_query', function($args) {
            return $this->_rest_user_query($args);
        });

        // Check if user has ability to perform certain task on other users
        add_filter('map_meta_cap', function($caps, $cap, $_, $args) {
            return $this->_map_meta_cap($caps, $cap, $args);
        }, 999, 4);

        // Additionally tap into password management
        add_filter('show_password_fields', function($result, $user) {
            return $this->_show_password_fields($result, $user);
        }, 10, 2);
        add_filter('allow_password_reset', function($result, $user_id) {
            return $this->_allow_password_reset($result, $user_id);
        }, 10, 2);
        add_action('check_passwords', function($login, &$password, &$password2) {
            $this->_check_passwords($login, $password, $password2);
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
     *
     * @access private
     * @version 7.0.0
     */
    private function _filter_editable_roles($roles)
    {
        $service = AAM::api()->identities();

        foreach (array_keys($roles) as $slug) {
            // Filter out all the roles that are explicitly hidden with "Roles" rule
            // type or implicitly by the specified role level_n capability
            if ($service->is_denied_to('role', $slug, 'list_role') === true) {
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
     *
     * @access private
     * @version 7.0.0
     */
    private function _filter_views_users($views)
    {
        $service = AAM::api()->identities();

        foreach(array_keys($views) as $slug) {
            if ($slug !== 'all'
                && $service->is_denied_to('role', $slug, 'list_role') === true
            ) {
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
     *
     * @access private
     * @version 7.0.0
     */
    private function _pre_get_users($query)
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
     * @access private
     * @version 7.0.0
     */
    private function _rest_user_query($args)
    {
        return array_merge($args, $this->_prepare_filter_args());
    }

    /**
     * Prepare filter arguments for the user query object
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_filter_args()
    {
        $result = AAM::api()->identities()->get_user_query_filters();

        return is_array($result) ? $result : [];
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
     *
     * @access private
     * @version 7.0.0
     */
    private function _map_meta_cap($caps, $cap, $args)
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
            } elseif ($cap === 'aam_list_users') {
                $caps = $this->_authorize_user_action('list_user', $id, $caps);
            }
        }

        return $caps;
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
     * @version 7.0.0
     */
    private function _authorize_user_action($action, $user_id, $caps)
    {
        // If do not allow is declared, there is no need to do anything else
        if (!in_array('do_not_allow', $caps, true)) {
            $service = AAM::api()->identities();

            if ($service->is_denied_to('user', $user_id, $action)) {
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
     *
     * @access private
     * @version 7.0.0
     */
    private function _show_password_fields($result, $user)
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
     * @access private
     * @version 7.0.0
     */
    private function _allow_password_reset($result, $user_id)
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
     * @access private
     * @version 7.0.0
     */
    private function _check_passwords($login, &$password, &$password2)
    {
        $user = get_user_by('login', $login);

        // Take into consideration scenario when new user is being created
        if (is_a($user, 'WP_User')) {
            $is_profile = $user->ID === get_current_user_id();

            if (!$is_profile
                && !current_user_can('aam_change_password', $user->ID)
            ) {
                $password = $password2 = null;
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
     *
     * @access private
     * @version 7.0.0
     */
    private function _rest_pre_insert_user($data, $request)
    {
        $user = get_user_by('id', $request['id']);

        // Take into consideration scenario when new user is being created
        if (is_a($user, 'WP_User')) {
            $is_profile = $user->ID === get_current_user_id();

            if (!$is_profile
                && !current_user_can('aam_change_password', $user->ID)
                && property_exists($data, 'user_pass')
            ) {
                unset($data->user_pass);
            }
        }

        return $data;
    }

}