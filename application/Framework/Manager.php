<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM framework manager
 *
 * @package AAM
 *
 * @version 7.0.0
 */
class AAM_Framework_Manager
{

    /**
     * Default context shared by all services
     *
     * @var array
     *
     * @access private
     * @static
     * @version 7.0.0
     */
    private static $_default_context = [];

    /**
     * Get roles service
     *
     * @return AAM_Framework_Service_Roles
     *
     * @access public
     * @version 7.0.0
     */
    public static function roles(array $runtime_context = [])
    {
        return AAM_Framework_Service_Roles::get_instance(array_merge(
            self::$_default_context, $runtime_context
        ));
    }

    /**
     * Get the URL Access service
     *
     * @param array $runtime_context
     *
     * @return AAM_Framework_Service_Urls
     *
     * @access public
     * @version 7.0.0
     */
    public static function urls(array $runtime_context = [])
    {
        return AAM_Framework_Service_Urls::get_instance(array_merge(
            self::$_default_context, $runtime_context
        ));
    }

    /**
     * Get the API Routes service
     *
     * @param array $runtime_context
     *
     * @return AAM_Framework_Service_ApiRoutes
     *
     * @access public
     * @version 7.0.0
     */
    public static function api_routes(array $runtime_context = [])
    {
        return AAM_Framework_Service_ApiRoutes::get_instance(array_merge(
            self::$_default_context, $runtime_context
        ));
    }

    /**
     * Get the JWT Token service
     *
     * @param array $runtime_context
     *
     * @return AAM_Framework_Service_Jwts
     *
     * @access public
     * @version 7.0.0
     */
    public static function jwts(array $runtime_context = [])
    {
        return AAM_Framework_Service_Jwts::get_instance(array_merge(
            self::$_default_context, $runtime_context
        ));
    }

    /**
     * Get the Login Redirect service
     *
     * @param array $runtime_context
     *
     * @return AAM_Framework_Service_LoginRedirect
     *
     * @access public
     * @version 7.0.0
     */
    public static function login_redirect(array $runtime_context = [])
    {
        return AAM_Framework_Service_LoginRedirect::get_instance(array_merge(
            self::$_default_context, $runtime_context
        ));
    }

    /**
     * Get the Logout Redirect service
     *
     * @param array $runtime_context
     *
     * @return AAM_Framework_Service_LogoutRedirect
     *
     * @access public
     * @version 7.0.0
     */
    public static function logout_redirect(array $runtime_context = [])
    {
        return AAM_Framework_Service_LogoutRedirect::get_instance(array_merge(
            self::$_default_context, $runtime_context
        ));
    }

    /**
     * Get the 404 Redirect service
     *
     * @param array $runtime_context
     *
     * @return AAM_Framework_Service_NotFoundRedirect
     *
     * @access public
     * @version 7.0.0
     */
    public static function not_found_redirect(array $runtime_context = [])
    {
        return AAM_Framework_Service_NotFoundRedirect::get_instance(array_merge(
            self::$_default_context, $runtime_context
        ));
    }

    /**
     * Get the Backend Menu service
     *
     * @param array $runtime_context
     *
     * @return AAM_Framework_Service_BackendMenu
     *
     * @access public
     * @version 7.0.0
     */
    public static function backend_menu(array $runtime_context = [])
    {
        return AAM_Framework_Service_BackendMenu::get_instance(array_merge(
            self::$_default_context, $runtime_context
        ));
    }

    /**
     * Get the Admin Toolbar service
     *
     * @param array $runtime_context
     *
     * @return AAM_Framework_Service_AdminToolbar
     *
     * @access public
     * @version 7.0.0
     */
    public static function admin_toolbar(array $runtime_context = [])
    {
        return AAM_Framework_Service_AdminToolbar::get_instance(array_merge(
            self::$_default_context, $runtime_context
        ));
    }

    /**
     * Get Metaboxes service
     *
     * @param array $runtime_context
     *
     * @return AAM_Framework_Service_Metaboxes
     *
     * @access public
     * @version 7.0.0
     */
    public static function metaboxes(array $runtime_context = [])
    {
        return AAM_Framework_Service_Metaboxes::get_instance(array_merge(
            self::$_default_context, $runtime_context
        ));
    }

    /**
     * Get Widgets service
     *
     * @param array $runtime_context
     *
     * @return AAM_Framework_Service_Widgets
     *
     * @access public
     * @version 7.0.0
     */
    public static function widgets(array $runtime_context = [])
    {
        return AAM_Framework_Service_Widgets::get_instance(array_merge(
            self::$_default_context, $runtime_context
        ));
    }

    /**
     * Get the Access Denied Redirect service
     *
     * @param array $runtime_context
     *
     * @return AAM_Framework_Service_AccessDeniedRedirect
     *
     * @access public
     * @version 7.0.0
     */
    public static function access_denied_redirect(array $runtime_context = [])
    {
        return AAM_Framework_Service_AccessDeniedRedirect::get_instance(array_merge(
            self::$_default_context, $runtime_context
        ));
    }

    /**
     * Get the User Governance service
     *
     * @param array $runtime_context
     *
     * @return AAM_Framework_Service_Identities
     *
     * @access public
     * @version 7.0.0
     */
    public static function identities(array $runtime_context = [])
    {
        return AAM_Framework_Service_Identities::get_instance(array_merge(
            self::$_default_context, $runtime_context
        ));
    }

    /**
     * Get the Content service
     *
     * @param array $runtime_context
     *
     * @return AAM_Framework_Service_Content
     *
     * @access public
     * @version 6.9.31
     */
    public static function content(array $runtime_context = [])
    {
        return AAM_Framework_Service_Content::get_instance(array_merge(
            self::$_default_context, $runtime_context
        ));
    }

    /**
     * Get the Users service
     *
     * @return AAM_Framework_Service_Users
     *
     * @access public
     * @version 6.9.32
     */
    public static function users(array $runtime_context = [])
    {
        return AAM_Framework_Service_Users::get_instance(array_merge(
            self::$_default_context, $runtime_context
        ));
    }

    /**
     * Get the capabilities service
     *
     * @return AAM_Framework_Service_Capabilities
     *
     * @access public
     * @version 6.9.33
     */
    public static function capabilities(array $runtime_context = [])
    {
        return AAM_Framework_Service_Capabilities::get_instance(array_merge(
            self::$_default_context, $runtime_context
        ));
    }

    /**
     * Get the configuration service
     *
     * @return AAM_Framework_Service_Configs
     *
     * @access public
     * @version 6.9.34
     */
    public static function configs(array $runtime_context = [])
    {
        return AAM_Framework_Service_Configs::get_instance(array_merge(
            self::$_default_context, $runtime_context
        ));
    }

    /**
     * Get the settings service
     *
     * @return AAM_Framework_Service_Settings
     *
     * @access public
     * @version 6.9.34
     */
    public static function settings(array $runtime_context = [])
    {
        return AAM_Framework_Service_Settings::get_instance(array_merge(
            self::$_default_context, $runtime_context
        ));
    }

    /**
     * Get the subject service
     *
     * @return AAM_Framework_Service_AccessLevels
     *
     * @access public
     * @version 6.9.34
     */
    public static function access_levels(array $runtime_context = [])
    {
        return AAM_Framework_Service_AccessLevels::get_instance(array_merge(
            self::$_default_context, $runtime_context
        ));
    }

    /**
     * Setup the framework manager
     *
     * @param array $default_context
     *
     * @return void
     *
     * @access public
     * @static
     * @version 7.0.0
     */
    public static function setup(array $default_context = [])
    {
        if (is_array($default_context)) {
            self::$_default_context = $default_context;
        }
    }

}