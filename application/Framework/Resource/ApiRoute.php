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
    protected $type = AAM_Framework_Type_Resource::API_ROUTE;

    /**
     * @inheritDoc
     */
    private function _apply_policy()
    {
        $result = [];

        // Fetch list of statements for the resource Route
        $list = $this->policies()->statements('Route:*');

        foreach($list as $stm) {
            $effect = isset($stm['Effect']) ? strtolower($stm['Effect']) : 'deny';

            // Extracting route attributes
            $parsed = explode(':', $stm['Resource']);
            $route  = !empty($parsed[1]) ? $parsed[1] : null;
            $verb   = !empty($parsed[2]) ? $parsed[2] : null;

            if (!empty($route) && !empty($verb)) {
                $key         = strtolower($verb . ' ' . $route);
                $result = array_merge([
                    $key => [
                        'access' => [
                            'effect' => $effect !== 'allow' ? 'deny' : 'allow'
                        ]
                    ]
                ], $result);
            }
        }

        return apply_filters('aam_apply_policy_filter', $result, $this);
    }

}