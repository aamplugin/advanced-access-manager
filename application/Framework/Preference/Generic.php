<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Generic preferences container
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Preference_Generic implements AAM_Framework_Preference_Interface
{

    use AAM_Framework_Preference_BaseTrait;

    /**
     * @inheritDoc
     */
    protected $type = '';

    /**
     * Constructor
     *
     * Initialize the preference container
     *
     * @param AAM_Framework_AccessLevel_Interface $access_level
     * @param string                              $preference_type
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function __construct($access_level, $preference_type)
    {
        $this->_access_level = $access_level;
        $this->type          = $preference_type;

        // Do not allow extending generic preferences container

        // Initialize preferences
        $this->_init_preferences();
    }

}