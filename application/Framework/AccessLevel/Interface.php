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
     * @param boolean    $skip_inheritance
     *
     * @return AAM_Framework_Resource_PermissionInterface|AAM_Framework_Resource_PreferenceInterface|null
     *
     * @access public
     * @version 7.0.0
     */
    public function get_resource(
        $resource_type,
        $resource_id = null,
        $reload = false,
        $skip_inheritance = false
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

    /**
     * Get Access Denied Redirect service
     *
     * @return AAM_Framework_Service_AccessDeniedRedirect
     *
     * @access public
     * @version 7.0.0
     */
    public function access_denied_redirect();

    /**
     * Get Not Found Redirect service
     *
     * @return AAM_Framework_Service_NotFoundRedirect
     *
     * @access public
     * @version 7.0.0
     */
    public function not_found_redirect();

    /**
     * Get Content service
     *
     * @return AAM_Framework_Service_Content
     *
     * @access public
     * @version 7.0.0
     */
    public function content();

    /**
     * Get Backend Menu service
     *
     * @return AAM_Framework_Service_BackendMenu
     *
     * @access public
     * @version 7.0.0
     */
    public function backend_menu();

}