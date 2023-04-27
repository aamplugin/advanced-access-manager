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
 * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/273
 * @since 6.9.10 https://github.com/aamplugin/advanced-access-manager/issues/274
 * @since 6.9.9  https://github.com/aamplugin/advanced-access-manager/issues/266
 * @since 6.9.6  Initial implementation of the class
 *
 * @version 6.9.10
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