<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM access levels utility
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Utility_AccessLevels implements AAM_Framework_Utility_Interface
{

    use AAM_Framework_Utility_BaseTrait;

    /**
     * Collection of already instantiated access levels
     *
     * @var array
     *
     * @access private
     * @version 7.0.0
     */
    private $_access_levels = [];

    /**
     * Get access level
     *
     * @param string  $type
     * @param mixed   $identifier
     *
     * @return AAM_Framework_AccessLevel_Interface
     *
     * @access public
     * @version 7.0.0
     */
    public function get($type, $identifier = null)
    {
        if ($type === AAM_Framework_Type_AccessLevel::ROLE) {
            $result = $this->get_role($identifier);
        } elseif ($type === AAM_Framework_Type_AccessLevel::USER) {
            $result = $this->get_user($identifier);
        } elseif ($type === AAM_Framework_Type_AccessLevel::GUEST) {
            $result = $this->get_visitor();
        } elseif ($type === AAM_Framework_Type_AccessLevel::ALL) {
            $result = $this->get_default();
        } else {
            $result = apply_filters(
                'aam_get_access_level_filter',
                null,
                $type,
                $identifier
            );
        }

        if (!is_object($result)) {
            throw new InvalidArgumentException(sprintf(
                "Unsupported access level: %s", esc_js($type)
            ));
        }

        return $result;
    }

    /**
     * Get role access level
     *
     * @param string $role_slug
     *
     * @return AAM_Framework_AccessLevel_Role
     *
     * @access public
     * @version 7.0.0
     */
    public function get_role($role_slug)
    {
        if (wp_roles()->is_role($role_slug)) {
            $result = new AAM_Framework_AccessLevel_Role(
                wp_roles()->get_role($role_slug)
            );
        } else {
            throw new OutOfRangeException(sprintf(
                "Role %s does not exist", esc_js($role_slug)
            ));
        }

        // Role access level initialization action to allow other
        // implementations to alter current state of the role after it is
        // instantiated
        do_action('aam_role_instantiated_action', $result);

        return $result;
    }

    /**
     * Get user subject
     *
     * @param mixed $identifier
     *
     * @return AAM_Framework_AccessLevel_User
     *
     * @access public
     * @version 7.0.0
     */
    public function get_user($identifier)
    {
        $user = $this->_determine_user_core_instance($identifier);

        // Convert the WP_User into user access level
        if (is_a($user, 'WP_User')) {
            $result = new AAM_Framework_AccessLevel_User($user);
        } else {
            throw new OutOfRangeException(sprintf(
                "User %s does not exist", esc_js($identifier)
            ));
        }

        // User access level initialization action to allow other
        // implementations to alter current state of the user after it is
        // instantiated
        do_action('aam_user_instantiated_action', $result);

        return $result;
    }

    /**
     * Get visitor access level
     *
     * @return AAM_Framework_AccessLevel_Visitor
     *
     * @access public
     * @version 7.0.0
     */
    public function get_visitor()
    {
        $result = new AAM_Framework_AccessLevel_Visitor();

        // Visitor access level initialization action to allow other
        // implementations to alter current state of the visitor after it is
        // instantiated
        do_action('aam_visitor_instantiated_action', $result);

        return $result;
    }

    /**
     * Get default access level
     *
     * @return AAM_Framework_AccessLevel_Default
     *
     * @access public
     * @version 7.0.0
     */
    public function get_default()
    {
        $result = new AAM_Framework_AccessLevel_Default();

        // Default access level initialization action to allow other
        // implementations to alter current state of the default after it is
        // instantiated
        do_action('aam_default_instantiated_action', $result);

        return $result;
    }

    /**
     * Based on user identifier prepare and return instance of WP_User
     *
     * @param string|int|WP_User $identifier
     *
     * @return WP_User
     *
     * @access private
     * @version 7.0.0
     */
    private function _determine_user_core_instance($identifier)
    {
        if (is_numeric($identifier)) { // Get user by ID
            $user = get_user_by('id', $identifier);
        } elseif (is_string($identifier)) {
            if (strpos($identifier, '@') > 0) { // Email?
                $user = get_user_by('email', $identifier);
            } else {
                $user = get_user_by('login', $identifier);
            }
        } elseif (is_a($identifier, 'WP_User')) {
            $user = $identifier;
        } else {
            throw new InvalidArgumentException('Invalid user identifier');
        }

        if ($user === false) { // User not found
            throw new OutOfRangeException(sprintf(
                'Cannot find user by identifier %s', esc_js($identifier)
            ));
        }

        return $user;
    }

}