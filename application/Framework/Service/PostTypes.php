<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Post Types framework service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Service_PostTypes
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Get post type resource
     *
     * @param mixed $identifier
     *
     * @return AAM_Framework_Resource_PostType
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource($identifier)
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::POST_TYPE, $identifier
        );
    }

}