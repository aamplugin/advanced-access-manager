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
 * Access denied redirect object
 *
 * @package AAM
 * @version 6.0.0
 */
class AAM_Core_Object_Redirect extends AAM_Core_Object
{

    /**
     * Type of object
     *
     * @version 6.0.0
     */
    const OBJECT_TYPE = 'redirect';

    /**
     * @inheritdoc
     * @version 6.0.0
     */
    protected function initialize()
    {
        $option = $this->getSubject()->readOption(self::OBJECT_TYPE);

        $this->determineOverwritten($option);

        $this->setOption(is_array($option) ? $option : array());
    }

    /**
     * Get access option
     *
     * @param string $param
     * @param mixed  $default
     *
     * @return mixed
     *
     * @access public
     * @version 6.0.0
     */
    public function get($param, $default = null)
    {
        $option = $this->getOption();

        return isset($option[$param]) ? $option[$param] : $default;
    }

}