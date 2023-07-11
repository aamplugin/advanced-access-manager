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
 * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/300
 * @since 6.0.0  Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.13
 */
class AAM_Core_Subject_Visitor extends AAM_Core_Subject
{

    /**
     * Subject UID: VISITOR
     *
     * @version 6.0.0
     */
    const UID = 'visitor';

    /**
     * @inheritDoc
     * @version 6.0.0
     */
    public function getParent()
    {
        return AAM_Core_Subject_Default::getInstance();
    }

    /**
     * @inheritDoc
     * @version 6.0.0
     */
    public function getName()
    {
        return __('Anonymous', AAM_KEY);
    }

    /**
     * Initialize user subject
     *
     * @return AAM_Core_Subject_User
     *
     * @access public
     * @version 6.9.13
     */
    public function initialize()
    {
        return $this;
    }

}