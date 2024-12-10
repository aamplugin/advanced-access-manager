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

    use AAM_Framework_Resource_ContentTrait, AAM_Framework_Resource_BaseTrait{
        AAM_Framework_Resource_ContentTrait::_get_settings_ns insteadof AAM_Framework_Resource_BaseTrait;
        AAM_Framework_Resource_ContentTrait::_normalize_permission insteadof AAM_Framework_Resource_BaseTrait;
    }

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::TAXONOMY;

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
        $taxonomy = get_taxonomy($resource_identifier);

        if (is_a($taxonomy, WP_Taxonomy::class)) {
            $this->_core_instance = $taxonomy;
            $this->_internal_id   = $resource_identifier;
        } else {
            throw new OutOfRangeException(
                'The taxonomy resource identifier is invalid'
            );
        }
    }

}