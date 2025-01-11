<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Taxonomy Resource class
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Taxonomy implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::TAXONOMY;

    /**
     * @inheritDoc
     */
    private function _get_resource_instance($resource_identifier)
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

    /**
     * Determine correct resource identifier based on provided data
     *
     * @param WP_Taxonomy $resource_identifier
     *
     * @return mixed
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource_id($resource_identifier)
    {
        return $resource_identifier->name;
    }

}