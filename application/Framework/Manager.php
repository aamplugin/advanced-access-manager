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
 * @since 6.9.6
 */
class AAM_Framework_Manager
{

    /**
     * Get roles service
     *
     * @return AAM_Framework_Service_Roles
     *
     * @access public
     * @since 6.9.6
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
     * @since 6.9.9
     */
    public static function urls($runtime_context = null)
    {
        return AAM_Framework_Service_Urls::get_instance($runtime_context);
    }

    /**
     * Get the subject service
     *
     * @return AAM_Framework_Service_Subject
     *
     * @access public
     * @since 6.9.9
     */
    public static function subject()
    {
        return AAM_Framework_Service_Subject::bootstrap();
    }

}