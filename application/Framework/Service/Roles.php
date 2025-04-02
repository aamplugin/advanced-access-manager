<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM Roles Governance service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Service_Roles
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Get list of dynamic roles
     *
     * Note! This method return only list of dynamically assumed roles to
     * access level. It does not return roles stored in WordPress core.
     *
     * This is an artificial abstraction layer on top of the WordPress core
     * roles and capabilities to allow roles adjustment through JSON access policies
     * and dynamic manipulations.
     *
     * @return array|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function get_list()
    {
        try {
            $result   = [];
            $resource = $this->_get_resource();

            foreach($resource->get_permissions() as $slug => $perms) {
                if (wp_roles()->is_role($slug)) { // Ignore invalid roles
                    if (isset($perms['assume_role'])) {
                        $result[$slug] = $perms['assume_role']['effect'] === 'allow';
                    }
                }
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alias for the get_list
     *
     * @return array|WP_Error
     * @access public
     *
     * @version 7.0.0
     */
    public function list()
    {
        return $this->get_list();
    }

    /**
     * Deny one or multiple permissions
     *
     * @param mixed        $role_identifier
     * @param string|array $permission
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function deny($role_identifier, $permission)
    {
        try {
            $resource   = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier($role_identifier);

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
     * @param mixed        $role_identifier
     * @param string|array $permission
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function allow($role_identifier, $permission)
    {
        try {
            $resource   = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier($role_identifier);

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
     * Check if permission is denied
     *
     * @param mixed  $role_identifier
     * @param string $permission
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function is_denied_to($role_identifier, $permission)
    {
        try {
            $result     = null;
            $resource   = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier($role_identifier);
            $permission = $resource->get_permission($identifier, $permission);

            if (!empty($permission)) {
                $result = $permission['effect'] !== 'allow';
            }

            // Making sure that other implementations can affect the decision
            $result = apply_filters(
                'aam_role_is_denied_to_filter',
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
     * @param mixed  $role_identifier
     * @param string $permission
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function is_allowed_to($role_identifier, $permission)
    {
        $decision = $this->is_denied_to($role_identifier, $permission);

        return is_bool($decision) ? !$decision : $decision;
    }

    /**
     * Check if role is hidden
     *
     * @param mixed $role_identifier
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function is_hidden($role_identifier)
    {
        return $this->is_denied_to($role_identifier, 'list_role');
    }

    /**
     * Hide role
     *
     * @param mixed $role_identifier
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function hide($role_identifier)
    {
        return $this->deny($role_identifier, 'list_role');
    }

    /**
     * Show role
     *
     * @param mixed $role_identifier
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function show($role_identifier)
    {
        return $this->allow($role_identifier, 'list_role');
    }

    /**
     * Reset permissions
     *
     * Reset role permissions or permissions to all roles if $role_identifier is not
     * provided
     *
     * @param mixed $role_identifier [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function reset($role_identifier = null)
    {
        try {
            if (!empty($role_identifier)) {
                $result = $this->_get_resource()->reset(
                    $this->_normalize_resource_identifier($role_identifier)
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
     * Aggregate all roles' permissions
     *
     * This method returns all explicitly defined permissions for all the roles. It
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
     * Get role resource
     *
     * @return AAM_Framework_Resource_Role
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource()
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::ROLE
        );
    }

    /**
     * @inheritDoc
     *
     * @return WP_Role
     */
    private function _normalize_resource_identifier($resource_identifier)
    {
        $result = null;

        if (is_a($resource_identifier, WP_Role::class)) {
            $result = $resource_identifier;
        } elseif (is_a($resource_identifier, AAM_Framework_Proxy_Role::class)) {
            $result = $resource_identifier->get_core_instance();
        } elseif (is_string($resource_identifier)) {
            $result = wp_roles()->get_role($resource_identifier);
        }

        $result = apply_filters(
            'aam_normalize_role_identifier_filter',
            $result,
            $resource_identifier
        );

        // Allow wildcard support
        if (!is_object($result) || !property_exists($result, 'name')) {
            throw new OutOfRangeException('The resource identifier is invalid');
        }

        return $result;
    }

}