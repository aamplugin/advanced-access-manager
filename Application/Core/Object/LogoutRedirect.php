<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 *
 * @version 6.0.0
 */

/**
 * Logout redirect object
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Core_Object_LogoutRedirect extends AAM_Core_Object
{

    /**
     * Type of object
     *
     * @version 6.0.0
     */
    const OBJECT_TYPE = 'logoutRedirect';

    /**
     * @inheritdoc
     * @version 6.0.0
     */
    protected function initialize()
    {
        // Initialize the settings
        $option = $this->getSubject()->readOption(self::OBJECT_TYPE);

        // If options are defined, set the overwritten flag
        $this->determineOverwritten($option);

        $this->setOption(is_array($option) ? $option : array());
    }

}