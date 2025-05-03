<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Interface for all resources that hold permissions
 *
 * Resource that is classified as such that contains permissions, have slightly
 * different way to handle its data, especially when it comes to merge permissions.
 *
 * @property string $type
 *
 * @package AAM
 * @version 7.0.0
 */
interface AAM_Framework_Resource_Interface
{

    /**
     * Get access level this resource is tight to
     *
     * @return AAM_Framework_AccessLevel_Interface
     * @access public
     *
     * @version 7.0.0
     */
    public function get_access_level();

    /**
     * Get raw permissions
     *
     * If $resource_identifier is not provided, this method returns the collection of
     * all explicitly defined permissions
     *
     * @param mixed $resource_identifier [Optional]
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function get_permissions($resource_identifier = null);

    /**
     * Set raw resource permissions
     *
     * @param array $permissions
     * @param mixed $resource_identifier [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function set_permissions(array $permissions, $resource_identifier = null);

    /**
     * Set raw resource permission
     *
     * @param mixed  $resource_identifier
     * @param string $permission_key
     * @param mixed  $permission
     * @param mixed  ...$args             [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function set_permission(
        $resource_identifier,
        $permission_key,
        $permission,
        ...$args
    );

    /**
     * Get raw resource permission
     *
     * @param mixed  $resource_identifier
     * @param string $permission_key
     *
     * @return array|null
     * @access public
     *
     * @version 7.0.0
     */
    public function get_permission($resource_identifier, $permission_key);

    /**
     * Remove a single permission
     *
     * @param mixed  $resource_identifier
     * @param string $permission_key
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function remove_permission(
        $resource_identifier,
        $permission_key
    );

    /**
     * Check if resource settings are overwritten
     *
     * @param mixed $resource_identifier [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function is_customized($resource_identifier = null);

    /**
     * Reset all explicitly defined settings to default
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function reset($resource_identifier = null);

}