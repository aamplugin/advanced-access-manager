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
 * @package AAM
 * @version 7.0.0
 */
interface AAM_Framework_Resource_Interface
{

    /**
     * Resource type (aka alias)
     *
     * @var string
     * @version 7.0.0
     */
    const TYPE = null;

    /**
     * Convert resource identifier into internal ID
     *
     * The internal ID represents unique resource identify AAM Framework users to
     * distinguish between collection of resources
     *
     * @param mixed $resource_identifier
     * @param bool  $serialize           [Optional]
     *
     * @return mixed
     * @access public
     *
     * @version 7.0.0
     */
    public function get_resource_id($resource_identifier, $serialize = true);

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
     * @param bool  $explicit            [Optional]
     *
     * @return array
     * @access public
     *
     * @version 7.0.0
     */
    public function get_permissions($resource_identifier = null, $explicit = false);

    /**
     * Set raw resource permissions
     *
     * @param array $permissions
     * @param mixed $resource_identifier [Optional]
     * @param bool  $explicit            [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function set_permissions(
        array $permissions,
        $resource_identifier = null,
        $explicit = true
    );

    /**
     * Set raw resource permission
     *
     * @param mixed  $resource_identifier
     * @param string $permission_key
     * @param mixed  $permission
     * @param bool   $explicit            [Optional]
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
        $explicit = true,
        ...$args
    );

    /**
     * Get raw resource permission
     *
     * @param mixed  $resource_identifier
     * @param string $permission_key
     * @param bool   $explicit            [Optional]
     *
     * @return array|null
     * @access public
     *
     * @version 7.0.0
     */
    public function get_permission(
        $resource_identifier,
        $permission_key,
        $explicit = false
    );

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