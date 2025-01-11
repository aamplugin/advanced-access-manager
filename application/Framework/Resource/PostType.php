<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Post Type Resource class
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_PostType implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::POST_TYPE;

    /**
     * @inheritDoc
     */
    private function _get_resource_instance($resource_identifier)
    {
        if (is_string($resource_identifier)) {
            $result = get_post_type_object($resource_identifier);
        } elseif (is_a($resource_identifier, WP_Post_Type::class)) {
            $result = $resource_identifier;
        }

        if (!is_a($result, WP_Post_Type::class)) {
            throw new OutOfRangeException('The resource identifier is invalid');
        }

        return $result;
    }

    /**
     * Determine correct resource identifier based on provided data
     *
     * @param WP_Post_Type $resource_identifier
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