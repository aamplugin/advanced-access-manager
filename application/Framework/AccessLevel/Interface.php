<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Access level interface
 *
 * All access levels should implement this interface. Though, it is not enforced, it
 * is strongly recommended to adhere to this interface and periodically check if it
 * changed.
 *
 * @package AAM
 * @version 7.0.0
 */
interface AAM_Framework_AccessLevel_Interface
{

    /**
     * Access level type
     *
     * @version 7.0.0
     */
    const TYPE = null;

    /**
     * Proxy methods to WordPress core instance
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     *
     * @access public
     * @since 7.0.0
     */
    public function __call($name, $arguments);

    /**
     * Get resource by its type and internal ID
     *
     * @param string     $resource_type
     * @param string|int $resource_id
     * @param boolean    $skip_inheritance
     *
     * @return AAM_Framework_Resource_Interface|null
     *
     * @access public
     * @version 7.0.0
     */
    public function get_resource(
        $resource_type,
        $resource_id = null,
        $skip_inheritance = false
    );

    /**
     * Get preference container
     *
     * @param string  $ns
     * @param boolean $skip_inheritance
     *
     * @return AAM_Framework_Preference_Interface|null
     *
     * @access public
     * @version 7.0.0
     */
    public function get_preference($ns, $skip_inheritance = false);

    /**
     * Get AAM proxy instance to the WP core instance (if applicable)
     *
     * @return mixed
     *
     * @access public
     * @version 7.0.0
     */
    public function get_proxy_instance();

    /**
     * Get access level ID
     *
     * @return string|int|null
     *
     * @access public
     * @version 7.0.0
     */
    public function get_id();

    /**
     * Get access level display name
     *
     * @return string
     *
     * @access public
     * @version 7.0.0
     */
    public function get_display_name();

    /**
     * Add new siblings to the collection
     *
     * @param AAM_Framework_AccessLevel_Role $role
     *
     * @return void
     *
     * @access public
     * @version 7.0.0
     */
    public function add_sibling(AAM_Framework_AccessLevel_Interface $sibling);

    /**
     * Check if there are any siblings
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function has_siblings();

    /**
     * Get all siblings
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_siblings();

    /**
     * Retrieve parent access level
     *
     * Returns null, if there is no parent access level
     *
     * @return AAM_Framework_AccessLevel_Interface|null
     *
     * @access public
     * @version 7.0.0
     */
    public function get_parent();

}