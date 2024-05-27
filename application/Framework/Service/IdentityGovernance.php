<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service Users & Roles Governance
 *
 * @package AAM
 * @version 6.9.28
 */
class AAM_Framework_Service_IdentityGovernance
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Rule types
     *
     * @version 6.9.28
     */
    const RULE_TYPES = array(
        'role',
        'user_role',
        'role_level',
        'user',
        'user_level'
    );

    /**
     * Allowed effect types
     *
     * @version 6.9.28
     */
    const EFFECT_TYPES = array(
        'allow',
        'deny'
    );

    /**
     * Allowed permission types
     *
     * @version 6.9.28
     */
    const PERMISSION_TYPES = array(
        'list_role',
        'list_user',
        'edit_user',
        'delete_user',
        'change_user_password',
        'change_user_role'
    );

    /**
     * Return list of rules for give subject
     *
     * @param array $inline_context Context
     *
     * @return array
     *
     * @access public
     * @version 6.9.28
     */
    public function get_rule_list($inline_context = null)
    {
        $response = array();
        $subject  = $this->_get_subject($inline_context);
        $object   = $subject->getObject(AAM_Core_Object_IdentityGovernance::OBJECT_TYPE);

        $options  = $object->getOption();
        $explicit = $object->getExplicitOption();

        if (is_array($options) && count($options)) {
            foreach($options as $target => $permissions) {
                array_push(
                    $response,
                    $this->_prepare_rule(
                        $target,
                        $permissions,
                        !array_key_exists($target, $explicit)
                    )
                );
            }
        }

        return $response;
    }

    /**
     * Get existing rule by ID
     *
     * @param int   $id             Sudo-id for the rule
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 6.9.28
     * @throws UnderflowException If rule does not exist
     */
    public function get_rule_by_id($id, $inline_context = null)
    {
        // Validating that incoming data is correct and normalize is for storage
        $subject  = $this->_get_subject($inline_context);
        $object   = $subject->getObject(AAM_Core_Object_IdentityGovernance::OBJECT_TYPE);
        $explicit = $object->getExplicitOption();

        // Find the rule that we are updating
        $rule = false;

        foreach($object->getOption() as $target => $permissions) {
            if (abs(crc32($target)) === $id) {
                $rule = array(
                    'target'      => $target,
                    'permissions' => $permissions
                );
            }
        }

        if ($rule === false) {
            throw new UnderflowException('Rule does not exist');
        }

        return $this->_prepare_rule(
            $rule['target'],
            $rule['permissions'],
            !array_key_exists($rule['target'], $explicit)
        );
    }

    /**
     * Create new URL rule
     *
     * @param array $rule           Rule settings
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 6.9.28
     * @throws Exception If fails to persist the rule
     */
    public function create_rule(array $rule, $inline_context = null)
    {
        // Validating that incoming data is correct and normalize is for storage
        $result  = $this->_validate_rule($rule);
        $subject = $this->_get_subject($inline_context);
        $object  = $subject->getObject(AAM_Core_Object_IdentityGovernance::OBJECT_TYPE);
        $success = $object->store($result['target'], $result['permissions']);

        if (!$success) {
            throw new Exception('Failed to persist the rule');
        }

        return $this->_prepare_rule($result['target'], $result['permissions']);
    }

    /**
     * Update existing rule
     *
     * @param int   $id             Sudo-id for the rule
     * @param array $rule           Rule data
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 6.9.28
     * @throws UnderflowException If rule does not exist
     * @throws Exception If fails to persist a rule
     */
    public function update_rule($id, array $rule, $inline_context = null)
    {
        // Validating that incoming data is correct and normalize is for storage
        $result  = $this->_validate_rule($rule);
        $subject = $this->_get_subject($inline_context);
        $object  = $subject->getObject(AAM_Core_Object_IdentityGovernance::OBJECT_TYPE);

        // Find the rule that we are updating
        $found = false;

        // Note! Getting here all rules (even inherited) to ensure that user can
        // override the inherited rule
        $original_options = $object->getOption();
        $new_options      = array();

        foreach($original_options as $target => $permissions) {
            if (abs(crc32($target)) === $id) {
                $found                          = true;
                $new_options[$result['target']] = $result['permissions'];
            } else {
                $new_options[$target] = $permissions;
            }
        }

        if ($found) {
            $object->setExplicitOption($new_options);
            $success = $object->save();
        } else {
            throw new UnderflowException('Rule does not exist');
        }

        if (!$success) {
            throw new Exception('Failed to update the rule');
        }

        return $this->_prepare_rule($result['target'], $result['permissions']);
    }

    /**
     * Delete rule
     *
     * @param int   $id             Sudo-id for the rule
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 6.9.28
     * @throws UnderflowException If rule does not exist
     * @throws Exception If fails to persist a rule
     */
    public function delete_rule($id, $inline_context = null)
    {
        $subject = $this->_get_subject($inline_context);
        $object  = $subject->getObject(AAM_Core_Object_IdentityGovernance::OBJECT_TYPE);

        // Find the rule that we are updating
        $found = null;

        // Note! User can delete only explicitly set rule (overwritten rule)
        $original_options = $object->getExplicitOption();
        $new_options      = array();

        foreach($original_options as $target => $permissions) {
            if (abs(crc32($target)) === $id) {
                $found = array(
                    'target'      => $target,
                    'permissions' => $permissions
                );
            } else {
                $new_options[$target] = $permissions;
            }
        }

        if ($found) {
            $object->setExplicitOption($new_options);
            $success = $object->save();
        } else {
            throw new UnderflowException('Rule does not exist');
        }

        if (!$success) {
            throw new Exception('Failed to persist the rule');
        }

        $subject->flushCache();

        return $this->_prepare_rule($found['target'], $found['permissions']);
    }

    /**
     * Reset all rules
     *
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 6.9.9
     */
    public function reset_rules($inline_context = null)
    {
        $response = array();

        // Reset the object
        $subject = $this->_get_subject($inline_context);
        $object  = $subject->getObject(AAM_Core_Object_IdentityGovernance::OBJECT_TYPE);

        // Communicate about number of rules that were deleted
        $response['deleted_rules_count'] = count($object->getExplicitOption());

        // Reset
        $response['success'] = $object->reset();

        return $response;
    }

    /**
     * Prepare rule model
     *
     * @param string $target
     * @param array  $permissions
     * @param bool   $is_inherited
     *
     * @return array
     *
     * @access private
     * @version 6.9.28
     */
    private function _prepare_rule($target, $permissions, $is_inherited = false)
    {
        // Extract rule type and identifier
        if (strpos($target, '|') !== false) {
            list($rule_type, $identifier) = explode('|', $target);
        } else {
            $rule_type  = $target;
            $identifier = null;
        }

        $response = array(
            'id'           => abs(crc32($target)),
            'is_inherited' => $is_inherited,
            'rule_type'    => $rule_type,
            'permissions'  => array()
        );

        if ($identifier !== null) {
            if (in_array($rule_type, ['role', 'user_role'], true)) {
                $response['role_slug'] = $identifier;
            } elseif ($rule_type === 'user') {
                $response['user_login'] = $identifier;
            } elseif (in_array($rule_type, ['role_level', 'user_level'], true)) {
                $response['level'] = intval($identifier);
            }
        }

        // Finally adding list of permissions
        foreach($permissions as $type => $effect) {
            array_push($response['permissions'], array(
                'permission' => $type,
                'effect'     => $effect
            ));
        }

        return $response;
    }

    /**
     * Validate the rule's incoming data
     *
     * @param array $rule Incoming rule's data
     *
     * @return array
     *
     * @access private
     * @version 6.9.28
     */
    private function _validate_rule(array $rule)
    {
        $rule_type     = isset($rule['rule_type']) ? $rule['rule_type'] : null;
        $allowed_types = apply_filters(
            'aam_allowed_user_governance_rule_types_filter', self::RULE_TYPES, true
        );

        if (!in_array($rule_type, $allowed_types, true)) {
            throw new DomainException(
                'Invalid rule type. Allowed (' . implode(',', $allowed_types) . ')'
            );
        }

        // Do some additional validate
        if ($rule_type === 'user') {
            $identifier = $this->_validate_user($rule['user_login']);
        } elseif (in_array($rule_type, ['role', 'user_role'], true)) {
            $identifier = $this->_validate_role($rule['role_slug']);
        } elseif (in_array($rule_type, ['role_level', 'user_level'], true)) {
            $identifier = $this->_validate_level($rule['level']);
        }

        $permissions = array();

        foreach($rule['permissions'] as $item) {
            if (in_array($item['permission'], self::PERMISSION_TYPES, true)) {
                $permission = $item['permission'];
            } else {
                throw new DomainException(
                    "Permission type {$item['permission']} is invalid"
                );
            }

            if (in_array($item['effect'], self::EFFECT_TYPES, true)) {
                $effect = $item['effect'];
            } else {
                throw new DomainException("Effect {$item['effect']} is invalid");
            }

            $permissions[$permission] = $effect;
        }

        return array(
            'target'      => $rule_type . ($identifier ? '|' : '') . $identifier,
            'permissions' => $permissions
        );
    }

    /**
     * Validate user
     *
     * @param string|init $identifier
     *
     * @return string
     *
     * @access private
     * @version 6.9.28
     */
    private function _validate_user($identifier)
    {
        if (is_numeric($identifier)) { // Get user by ID
            $user = get_user_by('id', $identifier);
        } elseif (is_string($identifier) && $identifier !== '*') {
            if (strpos($identifier, '@') > 0) { // Email?
                $user = get_user_by('email', $identifier);
            } else {
                $user = get_user_by('login', $identifier);
            }
        }

        if ($identifier === '*') {
            $response = $identifier;
        } elseif (!is_a($user, 'WP_User')) {
            throw new DomainException(
                'Invalid user identifier or user does not exist'
            );
        } else {
            $response = $user->user_login;
        }

        return $response;
    }

    /**
     * Validate role
     *
     * @param string $slug
     *
     * @return string
     *
     * @access private
     * @version 6.9.28
     */
    private function _validate_role($slug)
    {
        $roles = AAM_Core_API::getRoles();

        if ($slug !== '*' && !$roles->is_role($slug)) {
            throw new DomainException(
                'Invalid role identifier or role does not exist'
            );
        }

        return $slug;
    }

    /**
     * Validate level
     *
     * @param string|int $level
     *
     * @return int
     *
     * @access private
     * @version 6.9.28
     */
    private function _validate_level($level)
    {
        if (!is_numeric($level)) {
            throw new DomainException('Invalid user level identifier');
        }

        return intval($level);
    }

}