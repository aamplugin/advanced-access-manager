<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * User resource class
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_User implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::USER;

    /**
     * Initialize the resource
     *
     * @param mixed $resource_identifier
     *
     * @return void
     * @access protected
     *
     * @version 7.0.0
     */
    protected function pre_init_hook($resource_identifier)
    {
        if (is_a($resource_identifier, AAM_Framework_Proxy_User::class)) {
            $this->_core_instance = $resource_identifier;
            $this->_internal_id   = $resource_identifier->ID;
        } else {
            $user = AAM_Framework_Manager::_()->users->user($resource_identifier);

            if (is_a($user, AAM_Framework_Proxy_User::class)) {
                $this->_core_instance = $user;
                $this->_internal_id   = $user->ID;
            } elseif (is_scalar($resource_identifier)) {
                $this->_internal_id = $resource_identifier;
            }
        }

        if (empty($this->_internal_id)) {
            throw new OutOfRangeException('The user resource identifier is invalid');
        }
    }

    /**
     * Restrict permission
     *
     * @param string|array $permission
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function deny($permission)
    {
        return $this->_set_permission($permission, 'deny');
    }

    /**
     * Allow permission
     *
     * @param string|array $permission
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function allow($permission)
    {
        return $this->_set_permission($permission, 'allow');
    }

    /**
     * Check is specific permission is denied
     *
     * @param string $permission
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_denied_to($permission)
    {
        $decision = null;

        if (array_key_exists($permission, $this->_permissions)) {
            $decision = $this->_permissions[$permission]['effect'] !== 'allow';
        }

        return apply_filters(
            'aam_identity_is_denied_to_filter',
            is_bool($decision) ? $decision : null,
            $permission,
            $this
        );
    }

    /**
     * Determines if access level has certain permission
     *
     * @param string $permission
     *
     * @return bool|null
     *
     * @access public
     * @version 7.0.0
     */
    public function is_allowed_to($permission)
    {
        $decision = $this->is_denied_to($permission);

        return is_bool($decision) ? !$decision : $decision;
    }

    /**
     * Set permission(s)
     *
     * @param string|array $permission
     * @param string       $effect
     *
     * @return bool
     * @access private
     *
     * @version 7.0.0
     */
    private function _set_permission($permission, $effect)
    {
        if (is_string($permission)) {
            $result = $this->set_permissions(array_merge(
                $this->get_permissions(true),
                [ $permission => [ 'effect' => $effect ] ]
            ));
        } elseif (is_array($permission)) {
            $permissions = $this->get_permissions(true);

            foreach($permission as $perm) {
                $permissions[$perm] = [ 'effect' => $effect ];
            }

            $result = $this->set_permissions($permissions);
        }

        return $result;
    }

    /**
     * Get settings namespace
     *
     * @return string
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_settings_ns()
    {
        // Compile the namespace
        return constant('static::TYPE') . '.' . $this->get_internal_id(true);
    }

}