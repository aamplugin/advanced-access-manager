<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Post Resource class
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Post
implements
    AAM_Framework_Resource_Interface,
    AAM_Framework_Resource_PermissionInterface
{

    use AAM_Framework_Resource_PermissionTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::POST;

    /**
     * Determine if post is hidden on given area
     *
     * @param string $area Can be either frontend, backend or api
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_hidden_on($area)
    {
        $list = isset($this->_settings['list']) ? $this->_settings['list'] : null;

        return !empty($list)
            && in_array($area, $list['on'], true)
            && $list['effect'] == 'deny';
    }

    /**
     * Initialize additional properties
     *
     * @return void
     *
     * @access protected
     * @version 7.0.0
     */
    protected function initialize_hook()
    {
        $post = get_post($this->_internal_id);

        if (is_a($post, 'WP_Post')) {
            $this->_core_instance = $post;
        } else {
            throw new OutOfRangeException(
                "Post with ID {$this->_internal_id} does not exist"
            );
        }
    }

}