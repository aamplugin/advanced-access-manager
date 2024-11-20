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
     * Add a single permission
     *
     * @param string $permission_key
     * @param mixed  $permission      [optional]
     * @param bool   $exclude_authors [Premium Feature!]
     *
     * @return bool
     *
     * @access public
     * @version 7.0.0
     */
    public function add_permission($permission_key, ...$args)
    {
        $permissions = $this->_explicit_permissions;

        // Determine if $permission argument was provided
        $permission = array_shift($args);
        $permission = !is_null($permission) ? $permission : 'deny';

        $permissions[$permission_key] = apply_filters(
            'aam_framework_resource_add_permission_filter',
            $this->_sanitize_permission($permission, $permission_key),
            $args
        );

        return $this->set_permissions($permissions, true);
    }

    /**
     * Add multiple permissions
     *
     * @param array $permissions
     * @param bool  $exclude_authors [Premium Feature!]
     *
     * @return bool
     *
     * @access public
     * @version 7.0.0
     */
    public function add_permissions($permissions, ...$args)
    {
        $normalized = [];

        foreach($permissions as $key => $value) {
            if (is_numeric($key) && is_string($value)) {
                $normalized[$value] = apply_filters(
                    'aam_framework_resource_add_permission_filter',
                    $this->_sanitize_permission(true, $value),
                    $args
                );
            } elseif (is_string($key)) {
                $normalized[$key] = apply_filters(
                    'aam_framework_resource_add_permission_filter',
                    $this->_sanitize_permission($value, $key),
                    $args
                );
            }
        }

        return $this->set_permissions($normalized, true);
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