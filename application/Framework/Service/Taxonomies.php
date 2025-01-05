<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Taxonomies framework service
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Service_Taxonomies
{

    use AAM_Framework_Service_BaseTrait;

     /**
     * Get taxonomy resource
     *
     * @param mixed $identifier
     *
     * @return AAM_Framework_Resource_Taxonomy
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource($identifier)
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::TAXONOMY, $identifier
        );
    }

}