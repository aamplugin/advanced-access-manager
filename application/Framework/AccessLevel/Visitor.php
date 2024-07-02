<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Visitor subject
 *
 * @package AAM
 *
 * @version 6.9.34
 */
class AAM_Framework_AccessLevel_Visitor extends AAM_Framework_AccessLevel_Abstract
{

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