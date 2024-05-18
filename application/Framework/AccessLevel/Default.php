<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Default subject
 *
 * @package AAM
 *
 * @version 6.9.28
 */
class AAM_Framework_AccessLevel_Default extends AAM_Framework_AccessLevel_Abstract
{

    /**
     * @inheritDoc
     */
    const TYPE = 'default';

    /**
     * @inheritDoc
     */
    public function get_parent()
    {
        return apply_filters('aam_get_parent_access_level_filter', null, $this);
    }

}