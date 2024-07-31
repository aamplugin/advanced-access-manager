<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Identity Governance resource
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Identity
implements
    AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_PermissionTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::IDENTITY;

    /**
     * Check whether given role is allowed to perform an action
     *
     * @param string $role_slug
     * @param string $action
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_role_allowed_to($role_slug, $action)
    {
        return $this->_is_allowed_to('role', $role_slug, $action);
    }

    /**
     * Check whether given level is allowed to perform an action
     *
     * @param int    $level
     * @param string $action
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_role_level_allowed_to($level, $action)
    {
        return $this->_is_allowed_to('role_level', $level, $action);
    }

    /**
     * Check whether given user is allowed to perform an action
     *
     * @param string $user_login
     * @param string $action
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_user_allowed_to($user_login, $action)
    {
        return $this->_is_allowed_to('user', $user_login, $action);
    }

    /**
     * Check whether given user level is allowed to perform an action
     *
     * @param int    $level
     * @param string $action
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_user_level_allowed_to($level, $action)
    {
        return $this->_is_allowed_to('user_level', $level, $action);
    }

    /**
     * Check whether given user role is allowed to perform an action
     *
     * @param string $role_slug
     * @param string $action
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_user_role_allowed_to($role_slug, $action)
    {
        return $this->_is_allowed_to('user_role', $role_slug, $action);
    }

    /**
     * Check if certain action is allowed
     *
     * Method returns boolean true/false if rule is defined or null otherwise
     *
     * @param string $target
     * @param string $action
     *
     * @return boolean|null
     *
     * @access private
     * @version 7.0.0
     */
    private function _is_allowed_to($rule_type, $identifier, $action)
    {
        $result = null;
        $target = $rule_type . ($identifier ? "|{$identifier}" : '');

        if (isset($this->_settings[$target][$action])) {
            $result = ($this->_settings[$target][$action] === 'allow');
        }

        return apply_filters(
            'aam_user_governance_is_allowed_to_filter',
            $result,
            $rule_type,
            $identifier,
            $action,
            $this
        );
    }

    /**
     * Merge access settings
     *
     * @param array $incoming
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     * @todo Do we really need to store rules with "allow" "deny" flags instead of boolean?
     */
    public function merge_settings($incoming)
    {
        // Determine the array of unique targets
        $targets = array_keys($incoming);
        foreach (array_keys($this->_settings) as $key) {
            if (!in_array($key, $targets, true)) {
                $targets[] = $key;
            }
        }

        $merged  = [];
        $convert = function($v) { return $v !== 'allow'; };

        // Iterate over the array of all targets and merge settings
        foreach($targets as $target) {
            $merged[$target] = array_map(function($v) {
                return $v === true ? 'deny' : 'allow';
            }, $this->_merge_binary_settings(
                array_map($convert, isset($incoming[$target]) ? $incoming[$target] : []),
                array_map($convert, isset($this->_settings[$target]) ? $this->_settings[$target] : []),
            ));
        }

        return $merged;
    }

}