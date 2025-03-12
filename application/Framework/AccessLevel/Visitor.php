<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Visitor access level
 *
 * @package AAM
 *
 * @version 6.9.34
 */
class AAM_Framework_AccessLevel_Visitor implements AAM_Framework_AccessLevel_Interface
{

    use AAM_Framework_AccessLevel_BaseTrait;

    /**
     * @inheritDoc
     */
    protected $type = AAM_Framework_Type_AccessLevel::VISITOR;

    /**
     * @inheritDoc
     */
    public function get_parent()
    {
        return apply_filters(
            'aam_get_parent_access_level_filter',
            AAM_Framework_Manager::_()->access_levels->get(
                AAM_Framework_Type_AccessLevel::ALL
            ),
            $this
        );
    }

    /**
     * Check if access level has specific capability
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function has_cap()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function get_display_name()
    {
        return __('Visitors', 'advanced-access-manager');
    }

}