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

    use AAM_Framework_Resource_ContentTrait, AAM_Framework_Resource_BaseTrait {
        AAM_Framework_Resource_ContentTrait::_get_settings_ns insteadof AAM_Framework_Resource_BaseTrait;
        AAM_Framework_Resource_ContentTrait::_normalize_permission insteadof AAM_Framework_Resource_BaseTrait;
    }

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::POST_TYPE;

    /**
     * @inheritDoc
     */
    const AGGREGATABLE = true;

    /**
     * Initialize the core instance
     *
     * @param mixed $resource_identifier
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function pre_init_hook($resource_identifier)
    {
        $post_type = get_post_type_object($resource_identifier);

        if (is_a($post_type, WP_Post_Type::class)) {
            $this->_core_instance = $post_type;
            $this->_internal_id   = $resource_identifier;
        } else {
            throw new OutOfRangeException(
                'The post type resource identifier is invalid'
            );
        }
    }

}