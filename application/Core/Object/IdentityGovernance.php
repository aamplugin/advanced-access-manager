<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Users & Roles Governance object
 *
 * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/385
 * @since 6.9.30 https://github.com/aamplugin/advanced-access-manager/issues/378
 * @since 6.9.28 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.31
 */
class AAM_Core_Object_IdentityGovernance extends AAM_Core_Object
{

    /**
     * Type of object
     *
     * @version 6.9.28
     */
    const OBJECT_TYPE = 'identityGovernance';

    /**
     * @inheritdoc
     *
     * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/385
     * @since 6.9.28 Initial implementation of the method
     *
     * @version 6.9.31
     */
    protected function initialize()
    {
        $option = $this->getSubject()->readOption(self::OBJECT_TYPE);

        $this->setExplicitOption($option);

        // Trigger custom functionality that may populate the options. For
        // example, this hooks is used by Access Policy service
        $option = apply_filters(
            'aam_user_governance_object_option_filter',
            $option,
            $this
        );

        $this->setOption(is_array($option) ? $option : array());
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
     * @access public
     * @version 6.9.28
     */
    public function is_allowed_to($rule_type, $identifier, $action)
    {
        $allowed = null;
        $options = $this->getOption();
        $target  = $rule_type . ($identifier ? "|{$identifier}" : '');

        if (isset($options[$target][$action])) {
            $allowed = ($options[$target][$action] === 'allow');
        }

        return apply_filters(
            'aam_user_governance_is_allowed_to',
            $allowed,
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
     * @since 6.9.30 https://github.com/aamplugin/advanced-access-manager/issues/378
     * @since 6.9.28 Initial implementation of the method
     *
     * @access public
     * @version 6.9.30
     */
    public function mergeOption($incoming)
    {
        $current = $this->getOption();

        // Determine the array of unique targets
        $targets = array_keys($incoming);
        foreach (array_keys($current) as $key) {
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
            }, AAM::api()->mergeSettings(
                array_map($convert, isset($incoming[$target]) ? $incoming[$target] : []),
                array_map($convert, isset($current[$target]) ? $current[$target] : []),
                self::OBJECT_TYPE
            ));
        }

        return $merged;
    }

}