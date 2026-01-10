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
    protected $type = AAM_Framework_Type_Resource::POST_TYPE;

    /**
     * Determine correct resource identifier based on provided data
     *
     * @param WP_Post_Type $identifier
     *
     * @return string
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource_id($identifier)
    {
        return $identifier->name;
    }

    /**
     * @inheritDoc
     *
     * @version 7.0.11
     */
    private function _get_resource_identifier($id)
    {
        return get_post_type_object($id);
    }

}