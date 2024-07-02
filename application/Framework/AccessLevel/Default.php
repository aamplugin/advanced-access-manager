<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Default access level
 *
 * The default access level is top layer and all other levels inherit settings from
 * it.
 *
 * @package AAM
 *
 * @version 6.9.34
 */
class AAM_Framework_AccessLevel_Default extends AAM_Framework_AccessLevel_Abstract
{

    /**
     * @inheritDoc
     */
    public function get_parent()
    {
        return apply_filters('aam_get_parent_access_level_filter', null, $this);
    }

}