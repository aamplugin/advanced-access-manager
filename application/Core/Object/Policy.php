<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Policy object
 *
 * @package AAM
 *
 * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/385
 * @since 6.0.0  Initial implementation of the class
 *
 * @version 6.9.31
 */
class AAM_Core_Object_Policy extends AAM_Core_Object
{

    /**
     * Type of object
     *
     * @version 6.0.0
     */
    const OBJECT_TYPE = 'policy';

    /**
     * Initialize the policy rules for current subject
     *
     * @return void
     *
     * @access protected
     *
     * @since 6.9.31 https://github.com/aamplugin/advanced-access-manager/issues/385
     * @since 6.0.0  Initial implementation of the method
     *
     * @version 6.9.31
     */
    protected function initialize()
    {
        $option = $this->getSubject()->readOption(self::OBJECT_TYPE);

        $this->setExplicitOption($option);

        $this->setOption(is_array($option) ? $option : array());
    }

    /**
     * Check if policy attached
     *
     * @param int $id
     *
     * @return boolean
     *
     * @access public
     * @version 6.0.0
     */
    public function has($id)
    {
        $option = $this->getOption();

        return !empty($option[$id]);
    }

}