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
    const TYPE = AAM_Framework_Type_AccessLevel::VISITOR;

    /**
     * @inheritDoc
     */
    public function get_parent()
    {
        $levels = AAM::api()->access_levels();

        return apply_filters(
            'aam_get_parent_access_level_filter',
            $levels->get(AAM_Framework_Type_AccessLevel::ALL),
            $this
        );
    }

    /**
     * @inheritDoc
     */
    public function get_display_name()
    {
        return __('Visitors', AAM_KEY);
    }

}