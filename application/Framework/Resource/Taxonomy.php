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
class AAM_Framework_Resource_Taxonomy
implements
    AAM_Framework_Resource_PermissionInterface
{

    use AAM_Framework_Resource_ContentTrait, AAM_Framework_Resource_PermissionTrait{
        AAM_Framework_Resource_ContentTrait::_get_settings_ns insteadof AAM_Framework_Resource_PermissionTrait;
        AAM_Framework_Resource_ContentTrait::_normalize_permission insteadof AAM_Framework_Resource_PermissionTrait;
    }

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::TAXONOMY;

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
        $taxonomy = get_taxonomy($this->_internal_id);

        if (is_a($taxonomy, 'WP_Taxonomy')) {
            $this->_core_instance = $taxonomy;
        } else {
            throw new OutOfRangeException(
                "Taxonomy {$this->_internal_id} does not exist"
            );
        }
    }

}