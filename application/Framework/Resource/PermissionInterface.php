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
interface AAM_Framework_Resource_PermissionInterface
{

    /**
     * Get resource internal ID
     *
     * The internal ID represents unique resource identify AAM Framework users to
     * distinguish between collection of initialize resources
     *
     * @param bool $serialize
     *
     * @return string|int|null
     *
     * @access public
     * @version 7.0.0
     */
    public function get_internal_id($serialize = true);

    /**
     * Get the collection of resource permissions
     *
     * @param boolean $explicit_only
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_permissions($explicit_only = false);

    /**
     * Set resource permissions
     *
     * @param array $permissions
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public function set_permissions(array $permissions);

    /**
     * Get an individual permission
     *
     * @param string $permission
     *
     * @return array|null
     *
     * @access public
     * @version 7.0.0
     */
    public function get_permission($permission);

    /**
     * Set explicitly a permission
     *
     * @param string $permission_key
     * @param mixed  $permission
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function set_permission($permission_key, $permission);

    /**
     * Merge incoming permissions
     *
     * @param array $incoming_permissions
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function merge_permissions($incoming_permissions);

}