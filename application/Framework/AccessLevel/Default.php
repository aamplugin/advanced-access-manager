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
 * @version 7.0.0
 */
class AAM_Framework_AccessLevel_Default implements AAM_Framework_AccessLevel_Interface
{

    use AAM_Framework_AccessLevel_BaseTrait;

    /**
     * @inheritDoc
     */
    protected $type = AAM_Framework_Type_AccessLevel::ALL;

    /**
     * @inheritDoc
     */
    public function get_parent()
    {
        return apply_filters('aam_get_parent_access_level_filter', null, $this);
    }

    /**
     * @inheritDoc
     */
    public function get_display_name()
    {
        return __('Default Access Level', AAM_KEY);
    }

}