<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM Users Governance service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Service_Users
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Deny one or multiple permissions
     *
     * @param mixed        $user_identifier
     * @param string|array $permission
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function deny($user_identifier, $permission)
    {
        try {
            $resource  = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier($user_identifier);

            if (is_string($permission)) {
                $result = $resource->set_permission(
                    $identifier, $permission, 'deny'
                );
            } elseif (is_array($permission)) {
                $result = true;

                foreach($permission as $p) {
                    $result = $result && $resource->set_permission(
                        $identifier, $p, 'deny'
                    );
                }
            } else {
                throw new InvalidArgumentException('Invalid permission type');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Allow one or multiple permissions
     *
     * @param mixed        $user_identifier
     * @param string|array $permission
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function allow($user_identifier, $permission)
    {
        try {
            $resource  = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier($user_identifier);

            if (is_string($permission)) {
                $result = $resource->set_permission(
                    $identifier, $permission, 'allow'
                );
            } elseif (is_array($permission)) {
                $result = true;

                foreach($permission as $p) {
                    $result = $result && $resource->set_permission(
                        $identifier, $p, 'allow'
                    );
                }
            } else {
                throw new InvalidArgumentException('Invalid permission type');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if permission is denied for a given user
     *
     * This method is façade as it also does additional validation for each user's
     * role to ensure proper protection
     *
     * @param mixed  $user_identifier
     * @param string $permission
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function is_denied_to($user_identifier, $permission)
    {
        try {
            $result     = null;
            $resource   = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier($user_identifier);
            $data       = $resource->get_permission($identifier, $permission);

            if (!empty($data)) {
                $result = $data['effect'] !== 'allow';
            } else { // Get the list of all user roles and properly merge permissions
                $user_roles    = array_values($identifier->roles);
                $multi_support = $this->config->get(
                    'core.settings.multi_access_levels'
                );

                // Determine the final list of roles
                $roles = $multi_support ? $user_roles : [ array_shift($user_roles) ];

                // Iterate over the list of roles and merge permissions properly
                $permissions = [];
                $acl         = $resource->get_access_level();

                foreach($roles as $slug) {
                    if (wp_roles()->is_role($slug)) { // Ignore invalid roles
                        $permissions = $this->misc->merge_permissions(
                            $acl->get_resource(
                                AAM_Framework_Type_Resource::ROLE
                            )->get_permissions(wp_roles()->get_role($slug)),
                            $permissions,
                            AAM_Framework_Type_Resource::ROLE
                        );
                    }
                }

                if (isset($permissions[$permission])) {
                    $result = $permissions[$permission]['effect'] !== 'allow';
                }
            }

            // Making sure that other implementations can affect the decision
            $result = apply_filters(
                'aam_user_is_denied_to_filter',
                $result,
                $permission,
                $resource
            );

            // Prepare the final result
            $result = is_bool($result) ? $result : false;
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if permission is allowed
     *
     * @param mixed  $user_identifier
     * @param string $permission
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function is_allowed_to($user_identifier, $permission)
    {
        $decision = $this->is_denied_to($user_identifier, $permission);

        return is_bool($decision) ? !$decision : $decision;
    }

    /**
     * Reset permissions
     *
     * Reset user permissions or permissions to all users if $user_identifier is not
     * provided
     *
     * @param mixed $user_identifier [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function reset($user_identifier = null)
    {
        try {
            if (!empty($user_identifier)) {
                $result = $this->_get_resource()->reset(
                    $this->_normalize_resource_identifier($user_identifier)
                );
            } else {
                $result = $this->_get_resource()->reset();
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if user is hidden
     *
     * @param mixed $user_identifier
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function is_hidden($user_identifier)
    {
        return $this->is_denied_to($user_identifier, 'list_user');
    }

    /**
     * Hide user
     *
     * @param mixed $user_identifier
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function hide($user_identifier)
    {
        return $this->deny($user_identifier, 'list_user');
    }

    /**
     * Show user
     *
     * @param mixed $user_identifier
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function show($user_identifier)
    {
        return $this->allow($user_identifier, 'list_user');
    }

    /**
     * Aggregate all users' permissions
     *
     * This method returns all explicitly defined permissions for all the users. It
     * also includes permissions defined with JSON access policies, if the service
     * is enabled.
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function aggregate()
    {
        try {
            $result = $this->_get_resource()->get_permissions();
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get user resource
     *
     * @return AAM_Framework_Resource_User
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource()
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::USER
        );
    }

    /**
     * @inheritDoc
     *
     * @return WP_User
     */
    private function _normalize_resource_identifier($resource_identifier)
    {
        $result = null;

        if (is_a($resource_identifier, WP_User::class)) {
            $result = $resource_identifier;
        } elseif (is_a($resource_identifier, AAM_Framework_Proxy_User::class)) {
            $result = $resource_identifier->get_core_instance();
        } else {
            $user = AAM_Framework_Manager::_()->users->get_user(
                $resource_identifier
            );

            if (is_a($user, AAM_Framework_Proxy_User::class)) {
                $result = $user->get_core_instance();
            }
        }

        $result = apply_filters(
            'aam_normalize_user_identifier_filter',
            $result,
            $resource_identifier
        );

        // Allow wildcard support
        if (!is_object($result) || !property_exists($result, 'ID')) {
            throw new OutOfRangeException('The resource identifier is invalid');
        }

        return $result;
    }

}