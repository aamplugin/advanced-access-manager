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
     * Get resource by its type and internal ID
     *
     * @param string     $resource_type
     * @param string|int $resource_id
     * @param boolean    $reload
     *
     * @return AAM_Framework_Resource_Interface|null
     *
     * @access public
     * @version 7.0.0
     */
    public function get_resource(
        $resource_type, $resource_id = null, $reload = false
    );

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

    /**
     * Get URL resource
     *
     * @param string  $url
     * @param boolean $reload
     *
     * @return AAM_Framework_Resource_Url
     *
     * @access public
     * @version 7.0.0
     */
    public function url($url = null, $reload = false);

    /**
     * Get URLs service
     *
     * @return AAM_Framework_Service_Urls
     *
     * @access public
     * @version 7.0.0
     */
    public function urls();

    /**
     * Get Login Redirect service
     *
     * @return AAM_Framework_Service_LoginRedirect
     *
     * @access public
     * @version 7.0.0
     */
    public function login_redirect();

    /**
     * Get Logout Redirect service
     *
     * @return AAM_Framework_Service_LogoutRedirect
     *
     * @access public
     * @version 7.0.0
     */
    public function logout_redirect();

}