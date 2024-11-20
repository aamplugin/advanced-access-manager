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
     * This method check checks if the RESTful API route by HTTP method is
     * restricted. Additionally, it uses the "aam_api_route_is_restricted_filter" to
     * find a possible match.
     *
     * @param string $endpoint API endpoint
     * @param string $method   HTTP method
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_restricted($endpoint, $method)
    {
        $id = strtolower("{$method} {$endpoint}");

        if (array_key_exists($id, $this->_permissions)) {
            $result = $this->_permissions[$id]['effect'] !== 'allow';
        } else {
            $result = null;
        }

        return apply_filters(
            'aam_api_route_is_restricted_filter',
            $result,
            $endpoint,
            $method,
            $this
        );
    }

}