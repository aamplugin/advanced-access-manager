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

}