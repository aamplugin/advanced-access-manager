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
     * @return AAM_Framework_Resource_Taxonomy
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource()
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::TAXONOMY
        );
    }

    /**
     * @inheritDoc
     *
     * @return WP_Taxonomy
     */
    private function _normalize_resource_identifier($resource_identifier)
    {
        $result = null;

        if (is_a($resource_identifier, WP_Taxonomy::class)) {
            $result = $resource_identifier;
        } elseif (is_string($resource_identifier)) {
            $result = get_taxonomy($resource_identifier);
        }

        if (!is_a($result, WP_Taxonomy::class)) {
            throw new OutOfRangeException('The resource identifier is invalid');
        }

        return $result;
    }

}