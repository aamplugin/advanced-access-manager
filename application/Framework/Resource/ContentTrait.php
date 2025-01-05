<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Content Resource class
 *
 * @package AAM
 * @version 7.0.0
 */
trait AAM_Framework_Resource_ContentTrait
{

    /**
     * Restrict one or more permissions
     *
     * @param string|array $permission
     * @param array        $permission_settings [Optional]
     * @param bool         $exclude_authors     [Premium Feature]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function restrict($permission, ...$args)
    {
        $permissions = $this->_explicit_permissions;
        $settings    = $args[0];

        if (is_string($permission)) {
            if (is_array($settings)) { // Do we have settings for permission?
                $data = array_merge([ 'effect' => 'deny' ], $settings);
            } else {
                $data = 'deny';
            }

            $permissions[$permission] = apply_filters(
                'aam_framework_resource_restrict_filter',
                $this->_sanitize_permission($data, $permission),
                $args
            );
        } elseif (is_array($permission)) {
            foreach($permission as $perm) {
                $permissions[$perm] = apply_filters(
                    'aam_framework_resource_restrict_filter',
                    $this->_sanitize_permission('deny', $perm),
                    $args
                );
            }
        }

        return $this->set_permissions($permissions, true);
    }

    /**
     * Normalize permission model further
     *
     * @param array  $permission
     * @param string $permission_key
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _normalize_permission($permission, $permission_key)
    {
        if ($permission_key === 'list'
            && (!array_key_exists('on', $permission) || !is_array($permission['on']))
        ) {
            $permission['on'] = [
                'frontend',
                'backend',
                'api'
            ];
        }

        return $permission;
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