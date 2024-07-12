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
     * Resource alias
     *
     * @version 7.0.0
     */
    const TYPE = AAM_Framework_Type_Resource::API_ROUTE;

    /**
     * Check whether the RESTful API route is restricted
     *
     * This method check checks if the RESTful API route by HTTP method is
     * restricted. Additionally, it uses the "aam_api_route_is_restricted_filter" to
     * find a possible match.
     *
     * @param string $route
     * @param string $method
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_restricted($route, $method = 'POST')
    {
        $id       = "restful|{$route}|{$method}";
        $lower_id = strtolower($id);

        if (array_key_exists($id, $this->_settings)) {
            $result = $this->_settings[$id];
        } elseif (array_key_exists($lower_id, $this->_settings)) {
            $result = $this->_settings[$lower_id];
        } else {
            $result = null;
        }

        return apply_filters(
            'aam_api_route_is_restricted_filter',
            $result,
            $route,
            $method,
            $this
        );
    }

    /**
     * Get parent menu item
     *
     * @param string $item
     *
     * @return null|string
     *
     * @access private
     * @version 7.0.0
     */
    private function get_parent_item($item)
    {
        $parent = null;
        $cache  = AAM_Service_Toolbar::getInstance()->getToolbarCache();

        if (is_array($cache)) {
            foreach($cache as $branch) {
                foreach($branch['children'] as $child) {
                    if ($child['id'] === $item) {
                        $parent = $branch['id'];
                    }

                    if ($parent !== null) {
                        break;
                    }
                }
            }
        }

        return $parent;
    }

}