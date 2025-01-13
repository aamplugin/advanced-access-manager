<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service for RESTful API routes
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Service_ApiRoutes
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Restrict API route
     *
     * @param mixed $api_route
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function deny($api_route)
    {
        try {
            $result = $this->_update_route_permission(
                $this->_normalize_resource_identifier($api_route),
                true
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Allow API route
     *
     * @param mixed $api_route
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function allow($api_route)
    {
        try {
            $result = $this->_update_route_permission(
                $this->_normalize_resource_identifier($api_route),
                false
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Reset a specific route permissions or all routes
     *
     * @param mixed $api_route [Optional]
     *
     * @return bool
     * @access public
     *
     * @version 7.0.0
     */
    public function reset($api_route = null)
    {
        try {
            $resource = $this->_get_resource();

            if (empty($api_route)) {
                $result = $resource->reset();
            } else {
                $result = $resource->remove_permission(
                    $this->_normalize_resource_identifier($api_route),
                    'access'
                );
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if API route is restricted
     *
     * @param mixed $api_route
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function is_denied($api_route)
    {
        try {
            $result     = null;
            $resource   = $this->_get_resource();
            $permission = $resource->get_permission(
                $this->_normalize_resource_identifier($api_route),
                'access'
            );

            // Step #1. Determine if route is explicitly restricted
            if (!empty($permission)) {
                $result = $permission['effect'] !== 'allow';
            }

            // Step #2. Allow third-party implementation to influence the decision
            $result = apply_filters(
                'aam_api_route_is_denied_filter',
                $result,
                $api_route,
                $resource
            );

            // Prepare the final answer
            $result = is_bool($result) ? $result : false;
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if API route is allowed
     *
     * @param mixed $api_route
     *
     * @return boolean
     * @access public
     *
     * @version 7.0.0
     */
    public function is_allowed($api_route)
    {
        $result = $this->is_denied($api_route);

        return is_bool($result) ? !$result : $result;
    }

     /**
     * Get resource
     *
     * @return AAM_Framework_Resource_ApiRoute
     * @access private
     *
     * @version 7.0.0
     */
    private function _get_resource()
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::API_ROUTE
        );
    }

    /**
     * @inheritDoc
     */
    private function _normalize_resource_identifier($identifier)
    {
        if (is_a($identifier, WP_REST_Request::class)) {
            $endpoint = $identifier->get_route();
            $method   = $identifier->get_method();
        } elseif (is_string($identifier)) {
            if (strpos($identifier, ' ') !== false) {
                list($method, $endpoint) = explode(' ', $identifier, 2);
            } else {
                $method   = 'GET';
                $endpoint = trim($identifier);
            }
        } elseif (is_array($identifier)) {
            extract($identifier);
        }

        if (empty($endpoint) || empty($method)) {
            throw new InvalidArgumentException('Invalid API Route identifier');
        }

        return trim(strtolower("{$method} {$endpoint}"));
    }

    /**
     * Update existing route
     *
     * @param string $route
     * @param bool   $is_restricted
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _update_route_permission($route, $is_restricted)
    {
        return $this->_get_resource()->set_permission(
            $route, 'access', $is_restricted
        );
    }

}