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
 * Default subject
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Core_Subject_Default extends AAM_Core_Subject
{

    use AAM_Core_Contract_SingletonTrait;

    /**
     * Subject UID: DEFAULT
     *
     * @version 6.0.0
     */
    const UID = 'default';

    /**
     * @inheritDoc
     * @version 6.0.0
     */
    public function getName()
    {
        return __('All Users, Roles and Visitor', AAM_KEY);
    }

    /**
     * @inheritDoc
     * @version 6.0.0
     */
    public function getParent()
    {
        return null; // Default subject is the highest subject that can even be
    }

    /**
     * Get max subject user level
     *
     * @return int
     *
     * @access public
     * @version 6.0.0
     */
    public function getMaxLevel()
    {
        return AAM_Core_API::maxLevel(AAM_Core_API::getAllCapabilities());
    }

}