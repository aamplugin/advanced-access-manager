<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * User Level Filter service
 *
 * @since 6.7.9 https://github.com/aamplugin/advanced-access-manager/issues/193
 * @since 6.4.0 Enhanced https://github.com/aamplugin/advanced-access-manager/issues/71
 * @since 6.0.0 Initial implementation of the class
 *
 * @package AAM
 * @version 6.7.9
 */
class AAM_Service_UserLevelFilter
{
    use AAM_Core_Contract_ServiceTrait;

    /**
     * Service alias
     *
     * Is used to get service instance if it is enabled
     *
     * @version 6.4.0
     */
    const SERVICE_ALIAS = 'user-level';

    /**
     * AAM configuration setting that is associated with the service
     *
     * @version 6.0.0
     */
    const FEATURE_FLAG = 'core.service.user-level-filter.enabled';

    /**
     * Constructor
     *
     * @return void
     *
     * @since 6.7.9 https://github.com/aamplugin/advanced-access-manager/issues/193
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.7.9
     */
    protected function __construct()
    {
        if (is_admin()) {
            // Hook that returns the detailed information about the nature of the
            // service. This is used to display information about service on the
            // Settings->Services tab
            add_filter('aam_service_list_filter', function ($services) {
                $services[] = array(
                    'title'          => __('User Level Filter', AAM_KEY),
                    'description'    => __('Extend default WordPress core users and roles handling, and make sure that users with lower user level cannot see or manager users and roles with higher level.', AAM_KEY),
                    'setting'        => self::FEATURE_FLAG,
                    'defaultEnabled' => false
                );

                return $services;
            }, 1);
        }

        if (AAM_Core_Config::get(self::FEATURE_FLAG, false)) {
            $this->initializeHooks();
        }
    }

    /**
     * Initialize service hooks
     *
     * @return void
     *
     * @since 6.4.0 Enhanced https://github.com/aamplugin/advanced-access-manager/issues/71
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.4.0
     */
    protected function initializeHooks()
    {
        // User/role filters
        add_action('init', function() {
            add_filter('editable_roles', array($this, 'filterRoles'));
            add_action('pre_get_users', array($this, 'filterUserQuery'), 999);
            add_filter('views_users', array($this, 'filterViews'));
            // RESTful user querying
            add_filter('rest_user_query', array($this, 'prepareUserQueryArgs'));
        }, 1);

        // Check if user has ability to perform certain task on other users
        add_filter('map_meta_cap', array($this, 'mapMetaCaps'), 999, 4);

        // Determine if current user is allowed to manage specific user level
        add_filter(
            'aam_user_can_manage_level_filter', array($this, 'isUserLevelAllowed'), 10, 2
        );

        // Service fetch
        $this->registerService();
    }

    /**
     * Determine if current user is allowed to manage provided user level
     *
     * @param boolean $allowed
     * @param int     $level
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function isUserLevelAllowed($allowed, $level)
    {
        $allow_equal_level = true;

        if (AAM_Core_API::capExists('aam_manage_same_user_level')) {
            $allow_equal_level = current_user_can('aam_manage_same_user_level');
        }

        $user_level = AAM::getUser()->getMaxLevel();

        if ($allow_equal_level) {
            $allowed = $user_level >= $level;
        } else {
            $allowed = $user_level > $level;
        }

        return $allowed;
    }

    /**
     * Filter list of allowed roles
     *
     * @param array $roles
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function filterRoles($roles)
    {
        static $levels = array(); // to speed-up the execution

        foreach ($roles as $id => $role) {
            if (!empty($role['capabilities']) && is_array($role['capabilities'])) {
                if (!isset($levels[$id])) {
                    $levels[$id] = AAM_Core_API::maxLevel($role['capabilities']);
                }

                if (!$this->isUserLevelAllowed(true, $levels[$id])) {
                    unset($roles[$id]);
                }
            }
        }

        return $roles;
    }

    /**
     * Prepare the user query arguments
     *
     * @param array $args
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function prepareUserQueryArgs($args)
    {
        $args['role__not_in'] = $this->prepareExcludedRoleList();

        return $args;
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
     * @version 6.0.0
     */
    public function filterUserQuery($query)
    {
        $query->query_vars['role__not_in'] = $this->prepareExcludedRoleList();
    }

    /**
     * Prepare the list of roles that are not allowed
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function prepareExcludedRoleList()
    {
        $exclude = array();
        $roles   = AAM_Core_API::getRoles();

        foreach ($roles->role_objects as $id => $role) {
            $roleMax = AAM_Core_API::maxLevel($role->capabilities);

            if (!$this->isUserLevelAllowed(true, $roleMax)) {
                $exclude[] = $id;
            }
        }

        return $exclude;
    }

    /**
     * Filter user list view options
     *
     * @param array $views
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function filterViews($views)
    {
        $roles = AAM_Core_API::getRoles();

        foreach ($roles->role_objects as $id => $role) {
            $roleMax = AAM_Core_API::maxLevel($role->capabilities);
            if (isset($views[$id]) && !$this->isUserLevelAllowed(true, $roleMax)) {
                unset($views[$id]);
            }
        }

        return $views;
    }

    /**
     * Check user capability
     *
     * This is a hack function that add additional layout on top of WordPress
     * core functionality. Based on the capability passed in the $args array as
     * "0" element, it performs additional check on user's capability to manage
     * post, users etc.
     *
     * @param array  $caps
     * @param string $cap
     * @param int    $user_id
     * @param array  $args
     *
     * @return array
     *
     * @access public
     * @version 6.0.0
     */
    public function mapMetaCaps($caps, $cap, $user_id, $args)
    {
        $id = (isset($args[0]) ? $args[0] : null);

        if (in_array($cap, array('edit_user', 'delete_user')) && !empty($id)) {
            $caps = $this->authorizeUserUpdate($caps, $id);
        }

        return $caps;
    }

    /**
     * Check if current user is allowed to manager specified user
     *
     * @param array $caps
     * @param int   $userId
     *
     * @return array
     *
     * @access protected
     * @version 6.0.0
     */
    protected function authorizeUserUpdate($caps, $userId)
    {
        $user      = AAM::api()->getUser($userId);
        $userLevel = AAM_Core_API::maxLevel($user->allcaps);

        if (!$this->isUserLevelAllowed(true, $userLevel)) {
            $caps[] = 'do_not_allow';
        }

        return $caps;
    }

}

if (defined('AAM_KEY')) {
    AAM_Service_UserLevelFilter::bootstrap();
}