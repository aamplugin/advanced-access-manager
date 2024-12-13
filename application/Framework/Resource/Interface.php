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
     * Check if resource settings are overwritten
     *
     * @return boolean
     *
     * @access public
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