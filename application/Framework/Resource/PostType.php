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
class AAM_Framework_Resource_PostType
implements
    AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_PermissionTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::POST_TYPE;

    /**
     * Initialize the core instance
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function initialize_hook()
    {
        $post_type = get_post_type_object($this->_internal_id);

        if (is_a($post_type, 'WP_Post_Type')) {
            $this->_core_instance = $post_type;
        } else {
            throw new OutOfRangeException(
                "Post Type {$this->_internal_id} does not exist"
            );
        }
    }

}