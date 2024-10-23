<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Login redirect
 *
 * @package AAM
 * @version 6.0.0
 */
interface AAM_Backend_Feature_Main_ResourceInterface
{

    /**
     * Get proper resource for currently managed access level
     *
     * @param string|int $resource_id
     *
     * @return AAM_Framework_Resource_PermissionInterface|AAM_Framework_Resource_PreferenceInterface
     *
     * @access public
     * @version 7.0.0
     */
    public function get_resource($resource_id = null);

}