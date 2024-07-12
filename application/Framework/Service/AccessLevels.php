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
 * @version 7.0.0
 */
class AAM_Framework_Service_AccessLevels
{

    use AAM_Framework_Service_BaseTrait;

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
     * @param boolean $reload
     * @param array   $runtime_context
     *
     * @return AAM_Framework_AccessLevel_Interface
     *
     * @access public
     * @version 7.0.0
     */
    public function get(
        $type,
        $identifier = null,
        $reload = false,
        array $runtime_context = null
    ) {
        try {
            if ($type === AAM_Framework_Type_AccessLevel::ROLE) {
                $result = $this->get_role($identifier, $reload);
            } elseif ($type === AAM_Framework_Type_AccessLevel::USER) {
                $result = $this->get_user($identifier, $reload);
            } elseif ($type === AAM_Framework_Type_AccessLevel::GUEST) {
                $result = $this->get_visitor($reload);
            } elseif ($type === AAM_Framework_Type_AccessLevel::ALL) {
                $result = $this->get_default($reload);
            } else {
                $result = apply_filters(
                    'aam_get_access_level_filter',
                    null,
                    $type,
                    $identifier
                );
            }

            if (!is_object($result)) {
                throw new InvalidArgumentException(
                    "Unsupported access level: {$type}"
                );
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $runtime_context);
        }

        return $result;
    }

    /**
     * Get role access level
     *
     * @param string  $role_slug
     * @param boolean $reload
     * @param array   $runtime_context
     *
     * @return AAM_Framework_AccessLevel_Role
     *
     * @access public
     * @version 7.0.0
     */
    public function get_role(
        $role_slug, $reload = false, array $runtime_context = null
    ) {
        try {
            $cache_key = $this->_determine_cache_key(
                AAM_Framework_Type_AccessLevel::ROLE, $role_slug
            );

            if (!array_key_exists($cache_key, $this->_access_levels) || $reload) {
                if (wp_roles()->is_role($role_slug)) {
                    $result = new AAM_Framework_AccessLevel_Role(
                        wp_roles()->get_role($role_slug)
                    );
                } else {
                    throw new OutOfRangeException(
                        "Role {$role_slug} does not exist"
                    );
                }

                $this->_access_levels[$cache_key] = $result;

                // Role access level initialization action to allow other
                // implementations to alter current state of the role after it is
                // instantiated
                do_action('aam_role_instantiated_action', $result);
            } else {
                $result = $this->_access_levels[$cache_key];
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $runtime_context);
        }

        return $result;
    }

    /**
     * Get user subject
     *
     * @param int|string|WP_User $identifier
     * @param boolean            $reload
     * @param array              $runtime_context
     *
     * @return AAM_Framework_AccessLevel_User
     *
     * @access public
     * @version 7.0.0
     */
    public function get_user(
        $identifier, $reload = false, array $runtime_context = null
    ) {
        try {
            $cache_key = $this->_determine_cache_key(
                AAM_Framework_Type_AccessLevel::USER, $identifier
            );

            if (!array_key_exists($cache_key, $this->_access_levels) || $reload) {
                $user = $this->_determine_user_core_instance($identifier);

                // Convert the WP_User into user access level
                if (is_a($user, 'WP_User')) {
                    $result = new AAM_Framework_AccessLevel_User($user);
                } else {
                    throw new OutOfRangeException(
                        "User {$identifier} does not exist"
                    );
                }

                $this->_access_levels[$cache_key] = $result;

                // User access level initialization action to allow other
                // implementations to alter current state of the user after it is
                // instantiated
                do_action('aam_user_instantiated_action', $result);
            } else {
                $result = $this->_access_levels[$cache_key];
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $runtime_context);
        }

        return $result;
    }

    /**
     * Get visitor access level
     *
     * @param boolean $reload
     * @param array   $runtime_context
     *
     * @return AAM_Framework_AccessLevel_Visitor
     *
     * @access public
     * @version 7.0.0
     */
    public function get_visitor($reload = false, array $runtime_context = null)
    {
        try {
            $cache_key = $this->_determine_cache_key(
                AAM_Framework_Type_AccessLevel::VISITOR
            );

            if (!array_key_exists($cache_key, $this->_access_levels) || $reload) {
                $result = new AAM_Framework_AccessLevel_Visitor();

                $this->_access_levels[$cache_key] = $result;

                // Visitor access level initialization action to allow other
                // implementations to alter current state of the visitor after it is
                // instantiated
                do_action('aam_visitor_instantiated_action', $result);
            } else {
                $result = $this->_access_levels[$cache_key];
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $runtime_context);
        }

        return $result;
    }

    /**
     * Get default access level
     *
     * @param boolean $reload
     * @param array   $runtime_context
     *
     * @return AAM_Framework_AccessLevel_Default
     *
     * @access public
     * @version 7.0.0
     */
    public function get_default($reload = false, array $runtime_context = null)
    {
        try {
            $cache_key = $this->_determine_cache_key(
                AAM_Framework_Type_AccessLevel::ALL
            );

            if (!array_key_exists($cache_key, $this->_access_levels) || $reload) {
                $result = new AAM_Framework_AccessLevel_Default();

                $this->_access_levels[$cache_key] = $result;

                // Default access level initialization action to allow other
                // implementations to alter current state of the default after it is
                // instantiated
                do_action('aam_default_instantiated_action', $result);
            } else {
                $result = $this->_access_levels[$cache_key];
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $runtime_context);
        }

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
            throw new OutOfRangeException(
                sprintf('Cannot find user by identifier %s', $identifier)
            );
        }

        return $user;
    }

    /**
     * Determine cache cache based on access level
     *
     * @param string $access_level_type
     * @param mixed  $identifier
     *
     * @return string
     *
     * @access private
     * @version 7.0.0
     */
    private function _determine_cache_key($access_level_type, $identifier = null)
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