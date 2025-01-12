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
     * @return AAM_Framework_Resource_PostType
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource()
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::POST_TYPE
        );
    }

    /**
     * @inheritDoc
     *
     * @return WP_Post_Type
     */
    private function _normalize_resource_identifier($resource_identifier)
    {
        if (is_string($resource_identifier)) {
            $result = get_post_type_object($resource_identifier);
        } elseif (is_a($resource_identifier, WP_Post_Type::class)) {
            $result = $resource_identifier;
        }

        if (!is_a($result, WP_Post_Type::class)) {
            throw new OutOfRangeException('Invalid post type resource identifier');
        }

        return $result;
    }

}