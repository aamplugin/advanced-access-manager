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
            $resource = $this->_get_resource($role_identifier);

            if (is_string($permission)) {
                $result = $resource->add_permission(
                    $permission, 'deny'
                );
            } elseif (is_array($permission)) {
                $result = $resource->add_permissions(
                    $permission, 'deny'
                );
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
            $resource = $this->_get_resource($role_identifier);

            if (is_string($permission)) {
                $result = $resource->add_permission($permission, 'allow');
            } elseif (is_array($permission)) {
                $result = $resource->add_permissions($permission, 'allow');
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
            $result   = null;
            $resource = $this->_get_resource($role_identifier);

            if (isset($resource[$permission])) {
                $result = $resource[$permission]['effect'] !== 'allow';
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
        return $this->is_denied_to($role_identifier, 'list');
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
            $result = $this->_get_resource($role_identifier)->reset();
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
     * @param mixed $identifier
     *
     * @return AAM_Framework_Resource_Role
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource($identifier = null)
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::ROLE,
            $identifier
        );
    }

}