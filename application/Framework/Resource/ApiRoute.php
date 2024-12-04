<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * API Route resource class
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Resource_ApiRoute implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    const TYPE = AAM_Framework_Type_Resource::API_ROUTE;

    /**
     * Check whether the RESTful API route is restricted
     *
     * @param string $route [Optional]
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_restricted($route = null)
    {
        $result = null;
        $route  = empty($route) ? $this->_internal_id : $route;

        if (empty($route)) {
            throw new InvalidArgumentException('Non-empty route has to be provided');
        }

        if (array_key_exists($route, $this->_permissions)) {
            $result = $this->_permissions[$route]['effect'] !== 'allow';
        }

        return $result;
    }

}