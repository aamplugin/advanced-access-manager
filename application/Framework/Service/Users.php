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
            $resource = $this->_get_resource($user_identifier);

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
            $resource = $this->_get_resource($user_identifier);

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
            $result   = null;
            $resource = $this->_get_resource($user_identifier);

            if (isset($resource[$permission])) {
                $result = $resource[$permission]['effect'] !== 'allow';
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
            $result = $this->_get_resource($user_identifier)->reset();
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Get user resource
     *
     * @param mixed $identifier
     *
     * @return AAM_Framework_Resource_User
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource($identifier = null)
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::USER,
            $identifier
        );
    }

}