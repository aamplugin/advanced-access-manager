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
 * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/401
 * @since 6.9.28 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.35
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
        try {
            $result  = array();
            $subject = $this->_get_subject($inline_context);
            $object  = $subject->reloadObject(
                AAM_Core_Object_IdentityGovernance::OBJECT_TYPE
            );

            $options  = $object->getOption();
            $explicit = $object->getExplicitOption();

            if (is_array($options) && count($options)) {
                foreach($options as $target => $permissions) {
                    array_push(
                        $result,
                        $this->_prepare_rule(
                            $target,
                            $permissions,
                            !array_key_exists($target, $explicit)
                        )
                    );
                }
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
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
        try {
            // Validating that incoming data is correct and normalize is for storage
            $subject  = $this->_get_subject($inline_context);
            $object   = $subject->reloadObject(
                AAM_Core_Object_IdentityGovernance::OBJECT_TYPE
            );

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
                throw new OutOfRangeException('Rule does not exist');
            }

            $result = $this->_prepare_rule(
                $rule['target'],
                $rule['permissions'],
                !array_key_exists($rule['target'], $explicit)
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
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
     * @throws RuntimeException If fails to persist the rule
     */
    public function create_rule(array $rule, $inline_context = null)
    {
        try {
            // Validating that incoming data is correct and normalize is for storage
            $result  = $this->_validate_rule($rule);
            $subject = $this->_get_subject($inline_context);
            $object  = $subject->getObject(
                AAM_Core_Object_IdentityGovernance::OBJECT_TYPE
            );

            $success = $object->store($result['target'], $result['permissions']);

            if (!$success) {
                throw new RuntimeException('Failed to persist settings');
            }

            $result = $this->_prepare_rule($result['target'], $result['permissions']);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
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
     * @throws OutOfRangeException If rule does not exist
     * @throws RuntimeException If fails to persist a rule
     */
    public function update_rule($id, array $rule, $inline_context = null)
    {
        try {
            // Validating that incoming data is correct and normalize is for storage
            $result  = $this->_validate_rule($rule);
            $subject = $this->_get_subject($inline_context);
            $object  = $subject->getObject(
                AAM_Core_Object_IdentityGovernance::OBJECT_TYPE
            );

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
                throw new OutOfRangeException('Rule does not exist');
            }

            if (!$success) {
                throw new RuntimeException('Failed to update the rule');
            }

            $result = $this->_prepare_rule($result['target'], $result['permissions']);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
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
     * @throws OutOfRangeException If rule does not exist
     * @throws RuntimeException If fails to persist a rule
     */
    public function delete_rule($id, $inline_context = null)
    {
        try {
            $subject = $this->_get_subject($inline_context);
            $object  = $subject->getObject(
                AAM_Core_Object_IdentityGovernance::OBJECT_TYPE
            );

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
                $result = $object->setExplicitOption($new_options)->save();
            } else {
                throw new OutOfRangeException('Rule does not exist');
            }

            if (!$result) {
                throw new RuntimeException('Failed to persist the rule');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Reset all rules
     *
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/401
     * @since 6.9.9 Initial implementation of the method
     *
     * @access public
     * @version 6.9.35
     */
    public function reset($inline_context = null)
    {
        try {
            // Reset the object
            $subject = $this->_get_subject($inline_context);
            $object  = $subject->getObject(
                AAM_Core_Object_IdentityGovernance::OBJECT_TYPE
            );

            // Reset settings to default
            $object->reset();

            $result = $this->get_rule_list($inline_context);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
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
            throw new InvalidArgumentException(
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
                throw new InvalidArgumentException(
                    "Permission type {$item['permission']} is invalid"
                );
            }

            if (in_array($item['effect'], self::EFFECT_TYPES, true)) {
                $effect = $item['effect'];
            } else {
                throw new InvalidArgumentException(
                    "Effect {$item['effect']} is invalid"
                );
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
            throw new OutOfRangeException(
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
            throw new OutOfRangeException(
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
            throw new InvalidArgumentException('Invalid user level identifier');
        }

        return intval($level);
    }

}