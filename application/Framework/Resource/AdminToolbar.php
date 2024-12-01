<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Admin Toolbar (aka Toolbar) resource class
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_AdminToolbar implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::TOOLBAR;

    /**
     * Check whether the toolbar item is restricted/hidden or not
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_restricted()
    {
        $result = null;

        if (empty($this->_internal_id)) {
            throw new InvalidArgumentException(
                'The Admin Toolbar resource has to be initialized with valid item id'
            );
        }

        // If there is a direct setting for given item, use it and ignore everything
        // else
        if (array_key_exists($this->_internal_id, $this->_permissions)) {
            $result = $this->_permissions[$this->_internal_id]['effect'] !== 'allow';
        }

        return $result;
    }

}