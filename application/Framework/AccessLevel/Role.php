<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Role access level
 *
 * @package AAM
 *
 * @version 7.0.0
 */
class AAM_Framework_AccessLevel_Role implements AAM_Framework_AccessLevel_Interface
{

    use AAM_Framework_AccessLevel_BaseTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_AccessLevel::ROLE;

    /**
     * @inheritDoc
     */
    public function get_parent()
    {
        $levels = AAM_Framework_Manager::access_levels();

        return apply_filters(
            'aam_get_parent_access_level_filter',
            $levels->get(AAM_Framework_Type_AccessLevel::ALL),
            $this
        );
    }

}