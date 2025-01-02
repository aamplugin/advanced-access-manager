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
     * @return bool|null
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

    /**
     * Check whether the RESTful API route is allowed
     *
     * @param string $route [Optional]
     *
     * @return bool|null
     *
     * @access public
     * @version 7.0.0
     */
    public function is_allowed($route = null)
    {
        $result = $this->is_restricted($route);

        return is_bool($result) ? !$result : $result;
    }

    /**
     * @inheritDoc
     */
    private function _apply_policy($permissions)
    {
        // Fetch list of statements for the resource Route
        $list = AAM_Framework_Manager::_()->policies(
            $this->get_access_level()
        )->statements('Route:*');

        foreach($list as $stm) {
            $effect = isset($stm['Effect']) ? strtolower($stm['Effect']) : null;

            // If effect is defined, move forward with the rest
            if (!empty($effect)) {
                // Extracting route attributes
                $parsed = explode(':', $stm['Resource']);
                $route  = !empty($parsed[1]) ? $parsed[1] : null;
                $verb   = !empty($parsed[2]) ? $parsed[2] : null;

                if (!empty($route) && !empty($verb)) {
                    $key         = strtolower($verb . ' ' . $route);
                    $permissions = array_merge([
                        $key => [ 'effect' => $effect ]
                    ], $permissions);
                }
            }
        }

        return apply_filters('aam_apply_policy_filter', $permissions, $this);
    }

}