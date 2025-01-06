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
     * Get access level this resource is tight to
     *
     * @return AAM_Framework_AccessLevel_Interface
     *
     * @access public
     * @version 7.0.0
     */
    public function get_access_level();

    /**
     * Get the collection of raw resource permissions
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
     * Set raw resource permissions
     *
     * @param array   $permissions
     * @param boolean $explicit_only
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public function set_permissions(array $permissions, $explicit_only = true);

    /**
     * Add a single permission
     *
     * This method normalizes added permission to ensure it is consistent with
     * internal permission data model
     *
     * @param string $permission_key
     * @param mixed  $permission     [Optional]
     * @param bool   ...$args        [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function add_permission($permission_key, $permission = 'deny', ...$args);

    /**
     * Add multiple permissions
     *
     * This method normalizes added permissions to ensure their consistency with
     * internal permission data model.
     *
     * Keep in mind that this method assumes that you are passing only array of
     * permission keys. Examples of this method call:
     *  ->add_permissions([ 'edit', 'comment', 'delete' ], 'deny');
     *  ->add_permissions([ 'list', 'read' ], 'allow');
     *
     * @param array $permission_keys
     * @param mixed $effect          [Optional]
     * @param bool  ...$args         [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function add_permissions($permission_keys, $effect = 'deny', ...$args);

    /**
     * Remove a single permission
     *
     * @param string $permission_key
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function remove_permission($permission_key);

    /**
     * Remove multiple permissions
     *
     * @param array $permission_keys
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function remove_permissions($permission_keys);

    /**
     * Check if resource settings are overwritten
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function is_customized();

    /**
     * Reset all explicitly defined settings to default
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function reset();

}