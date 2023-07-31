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
 * @since 6.9.14 https://github.com/aamplugin/advanced-access-manager/issues/309
 * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/302
 *               https://github.com/aamplugin/advanced-access-manager/issues/301
 *               https://github.com/aamplugin/advanced-access-manager/issues/293
 * @since 6.9.12 https://github.com/aamplugin/advanced-access-manager/issues/285
 * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/273
 *               https://github.com/aamplugin/advanced-access-manager/issues/274
 * @since 6.9.9  https://github.com/aamplugin/advanced-access-manager/issues/266
 * @since 6.9.6  Initial implementation of the class
 *
 * @version 6.9.14
 */
class AAM_Framework_Manager
{

    /**
     * Get roles service
     *
     * @return AAM_Framework_Service_Roles
     *
     * @access public
     * @version 6.9.6
     */
    public static function roles()
    {
        return AAM_Framework_Service_Roles::bootstrap();
    }

    /**
     * Get the URL Access service
     *
     * @param array $runtime_context
     *
     * @return AAM_Framework_Service_Urls
     *
     * @access public
     * @version 6.9.9
     */
    public static function urls($runtime_context = null)
    {
        return AAM_Framework_Service_Urls::get_instance($runtime_context);
    }

    /**
     * Get the API Routes service
     *
     * @param array $runtime_context
     *
     * @return AAM_Framework_Service_ApiRoutes
     *
     * @access public
     * @version 6.9.10
     */
    public static function api_routes($runtime_context = null)
    {
        return AAM_Framework_Service_ApiRoutes::get_instance($runtime_context);
    }

    /**
     * Get the JWT Token service
     *
     * @param array $runtime_context
     *
     * @return AAM_Framework_Service_Jwts
     *
     * @access public
     * @version 6.9.10
     */
    public static function jwts($runtime_context = null)
    {
        return AAM_Framework_Service_Jwts::get_instance($runtime_context);
    }

    /**
     * Get the Login Redirect service
     *
     * @param array $runtime_context
     *
     * @return AAM_Framework_Service_LoginRedirect
     *
     * @access public
     * @version 6.9.12
     */
    public static function login_redirect($runtime_context = null)
    {
        return AAM_Framework_Service_LoginRedirect::get_instance($runtime_context);
    }

    /**
     * Get the Logout Redirect service
     *
     * @param array $runtime_context
     *
     * @return AAM_Framework_Service_LogoutRedirect
     *
     * @access public
     * @version 6.9.12
     */
    public static function logout_redirect($runtime_context = null)
    {
        return AAM_Framework_Service_LogoutRedirect::get_instance($runtime_context);
    }

    /**
     * Get the 404 Redirect service
     *
     * @param array $runtime_context
     *
     * @return AAM_Framework_Service_NotFoundRedirect
     *
     * @access public
     * @version 6.9.12
     */
    public static function not_found_redirect($runtime_context = null)
    {
        return AAM_Framework_Service_NotFoundRedirect::get_instance($runtime_context);
    }

    /**
     * Get the Backend Menu service
     *
     * @param array $runtime_context
     *
     * @return AAM_Framework_Service_BackendMenu
     *
     * @access public
     * @version 6.9.13
     */
    public static function backend_menu($runtime_context = null)
    {
        return AAM_Framework_Service_BackendMenu::get_instance($runtime_context);
    }

    /**
     * Get the Admin Toolbar service
     *
     * @param array $runtime_context
     *
     * @return AAM_Framework_Service_AdminToolbar
     *
     * @access public
     * @version 6.9.13
     */
    public static function admin_toolbar($runtime_context = null)
    {
        return AAM_Framework_Service_AdminToolbar::get_instance($runtime_context);
    }

    /**
     * Get Metaboxes & Widgets (aka Components) service
     *
     * @param array $runtime_context
     *
     * @return AAM_Framework_Service_Components
     *
     * @access public
     * @version 6.9.13
     */
    public static function components($runtime_context = null)
    {
        return AAM_Framework_Service_Components::get_instance($runtime_context);
    }

    /**
     * Get the Access Denied Redirect service
     *
     * @param array $runtime_context
     *
     * @return AAM_Framework_Service_AccessDeniedRedirect
     *
     * @access public
     * @version 6.9.14
     */
    public static function access_denied_redirect($runtime_context = null)
    {
        return AAM_Framework_Service_AccessDeniedRedirect::get_instance(
            $runtime_context
        );
    }

    /**
     * Get the subject service
     *
     * @return AAM_Framework_Service_Subject
     *
     * @access public
     * @version 6.9.9
     */
    public static function subject()
    {
        return AAM_Framework_Service_Subject::bootstrap();
    }

}