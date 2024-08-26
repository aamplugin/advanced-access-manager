<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service to manager access levels
 *
 * @package AAM
 * @version 6.9.9
 */
class AAM_Framework_Service_AccessLevel
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Collection of already instantiated access levels
     *
     * @var array
     *
     * @access private
     * @version 6.9.28
     */
    private $_access_levels = [];

    /**
     * Single instance of a current user
     *
     * @var AAM_Framework_AccessLevel_Abstract
     *
     * @access private
     * @version 6.9.28
     */
    private $_current_user = null;

    /**
     * Determine subject based on access level and ID
     *
     * @param string $access_level_type
     * @param mixed  $identifier
     *
     * @return AAM_Framework_AccessLevel_Abstract
     *
     * @access public
     * @version 6.9.9
     */
    public function get($access_level_type, $identifier = null, $reload = false)
    {
        $cache_key = $this->_determine_cache_key($access_level_type, $identifier);

        if (!array_key_exists($cache_key, $this->_access_levels) || $reload) {
            if ($access_level_type === AAM_Framework_Type_AccessLevel::ROLE) {
                $access_level = $this->get_role($identifier);
            } elseif ($access_level_type === AAM_Framework_Type_AccessLevel::USER) {
                $access_level = $this->get_user($identifier);
            } elseif ($access_level_type === AAM_Framework_Type_AccessLevel::GUEST) {
                $access_level = $this->get_visitor();
            } elseif ($access_level_type === AAM_Framework_Type_AccessLevel::ALL) {
                $access_level = $this->get_default();
            } else {
                $access_level = apply_filters(
                    'aam_get_access_level_filter',
                    null,
                    $access_level_type,
                    $identifier
                );
            }

            if (!is_object($access_level)) {
                throw new InvalidArgumentException(
                    "Unsupported access level type {$access_level_type}"
                );
            } else {
                $this->_access_levels[$cache_key] = $access_level;
            }

            do_action('aam_access_level_instantiated_action', $access_level);
        }

        return $this->_access_levels[$cache_key];
    }

    /**
     * Get role subject
     *
     * @param string|WP_Role $slug Role slug or instance
     *
     * @return AAM_Framework_AccessLevel_Role
     *
     * @access public
     * @version 6.9.9
     */
    public function get_role($identifier)
    {
        return AAM_Framework_Manager::roles()->get_role($identifier);
    }

    /**
     * Get user subject
     *
     * @param int|string|WP_User|null $id User identifier or null for current user
     *
     * @return AAM_Framework_AccessLevel_User
     *
     * @access public
     * @version 6.9.9
     */
    public function get_user($identifier = null)
    {
        if (is_null($identifier)) { // Get current user
            $user = $this->get_current_user();
        } else {
            $user = $this->_determine_user_core_instance($identifier);
        }

        // Convert the WP_User into user access level
        if (is_a($user, 'WP_User')) {
            $user = new AAM_Framework_AccessLevel_User($user);
        }

        return $user;
    }

    /**
     * Get current user
     *
     * It will return user access level if current user is authenticated and visitor
     * access level otherwise
     *
     * @return AAM_Framework_AccessLevel_Abstract
     *
     * @access public
     * @version 6.9.28
     */
    public function get_current_user()
    {
        if (is_null($this->_current_user)) {
            $user_id = get_current_user_id();

            // Determine if we should pass valid user ID or not
            $this->set_current_user(
                is_numeric($user_id) && $user_id > 0 ? $user_id : null
            );
        }

        return $this->_current_user;
    }

    /**
     * Undocumented function
     *
     * @param [type] $identifier
     * @return void
     */
    public function set_current_user($identifier = null)
    {
        if (is_null($identifier)) {
            $this->_current_user = new AAM_Framework_AccessLevel_Visitor();
        } else {
            $this->_current_user = new AAM_Framework_AccessLevel_User(
                $this->_determine_user_core_instance($identifier)
            );
        }
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function reload_current_user()
    {
        $this->_current_user = null;

        return $this->get_current_user();
    }

    /**
     * Get visitor subject
     *
     * @return AAM_Framework_AccessLevel_Visitor
     *
     * @access public
     * @version 6.9.9
     */
    public function get_visitor()
    {
        return new AAM_Framework_AccessLevel_Visitor();
    }

    /**
     * Get default subject
     *
     * @return AAM_Framework_AccessLevel_Default
     *
     * @access public
     * @version 6.9.9
     */
    public function get_default()
    {
        return new AAM_Framework_AccessLevel_Default();
    }

    /**
     * Based on user identifier prepare and return instance of WP_User
     *
     * @param string|int|WP_User $identifier
     *
     * @return WP_User
     *
     * @access private
     * @version 6.9.28
     * @throws DomainException
     * @throws InvalidArgumentException
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
            throw new DomainException('Invalid user identifier');
        }

        if ($user === false) { // User not found
            throw new InvalidArgumentException(
                sprintf('Cannot find user by identifier %s', $identifier)
            );
        }

        return $user;
    }

    /**
     * Undocumented function
     *
     * @param [type] $access_level_type
     * @param [type] $identifier
     * @return void
     */
    private function _determine_cache_key($access_level_type, $identifier)
    {
        $key = $access_level_type;

        if ($access_level_type === AAM_Framework_Type_AccessLevel::USER) {
            $user = $this->_determine_user_core_instance($identifier);
            $key .= '::' . $user->ID;
        } elseif ($access_level_type === AAM_Framework_Type_AccessLevel::ROLE) {
            if (is_a($identifier, 'WP_Role')) {
                $key .= '::' . $identifier->name;
            } elseif (is_string($identifier)) {
                $key .= '::' . $identifier;
            } else {
                throw new InvalidArgumentException('Invalid role identifier');
            }
        }

        return $key;
    }

}