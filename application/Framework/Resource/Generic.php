<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Generic resource class
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_Generic implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    protected $type = '';

    /**
     * Constructor
     *
     * Initialize the resource container
     *
     * @param AAM_Framework_AccessLevel_Interface $access_level
     * @param string                              $resource_type
     *
     * @return void
     * @access public
     *
     * @version 7.0.0
     */
    public function __construct($access_level, $resource_type)
    {
        $this->_access_level = $access_level;
        $this->type         = $resource_type;

        // Do not allow extending generic resource

        // Initialize permissions
        $this->_init_permissions();
    }

}