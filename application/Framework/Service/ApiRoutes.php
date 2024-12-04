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
     * Return list of permissions
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_items()
    {
        try {
            $result = [];

            // Iterating over the list of all registered API routes and compile the
            // list
            foreach (rest_get_server()->get_routes() as $endpoint => $handlers) {
                $methods = [];

                foreach ($handlers as $handler) {
                    $methods = array_merge(
                        $methods, array_keys($handler['methods'])
                    );
                }

                foreach (array_unique($methods) as $method) {
                    array_push($result, $this->_prepare_route($endpoint, $method));
                }
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alias for the get_items method
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function items()
    {
        return $this->get_items();
    }

    /**
     * Get existing route by combination of endpoint and HTTP method
     *
     * @param string      $endpoint Route endpoint
     * @param string|null $method   HTTP method
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_item($endpoint, $method = 'GET')
    {
        try {
            $routes = $this->get_items();
            $found  = array_filter($routes, function($r) use ($endpoint, $method) {
                return $r['endpoint'] === strtolower($endpoint)
                            && $r['method'] === strtoupper($method);
            });

            if (empty($found)) {
                throw new OutOfRangeException('Route does not exist');
            } else{
                $result = array_shift($found);
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Alias of the get_item method
     *
     * @param string $endpoint
     * @param string $method
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function item($endpoint, $method = 'GET')
    {
        return $this->get_item($endpoint, $method);
    }

    /**
     * Restrict API route
     *
     * @param mixed  $route
     * @param string $method [Optional]
     *
     * @return bool
     *
     * @access public
     * @version 7.0.0
     */
    public function restrict($route, $method = 'GET')
    {
        try {
            $route  = $this->_determine_route_key($route, $method);
            $result = $this->_update_route_permission($route, true);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Allow API route
     *
     * @param mixed  $route
     * @param string $method [Optional]
     *
     * @return bool
     *
     * @access public
     * @version 7.0.0
     */
    public function allow($route, $method = 'GET')
    {
        try {
            $route  = $this->_determine_route_key($route, $method);
            $result = $this->_update_route_permission($route, false);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Reset all routes
     *
     * @param mixed  $route  [Optional]
     * @param string $method [Optional]
     *
     * @return bool
     *
     * @access public
     * @version 7.0.0
     */
    public function reset($route = null, $method = null)
    {
        try {
            $resource = $this->_get_resource();

            if (empty($route)) {
                $result = $resource->reset();
            } else {
                $route = $this->_determine_route_key($route, $method);
                $perms = $resource->get_permissions(true);

                if (array_key_exists($route, $perms)) {
                    unset($perms[$route]);
                }

                $result = $resource->set_permissions($perms, true);
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if API route is restricted
     *
     * @param string|WP_REST_Request $route
     * @param string                 $method [Optional]
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_restricted($route, $method = 'GET')
    {
        try {
            $resource = $this->_get_resource();
            $key      = $this->_determine_route_key($route, $method);

            // Step #1. Determine if route is explicitly restricted
            $result = $resource->is_restricted($key);

            // Step #2. Allow third-party implementation to influence the decision
            $result = apply_filters(
                'aam_api_route_is_restricted_filter',
                $result,
                $resource,
                $route,
                $method
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if API route is allowed
     *
     * @param string|WP_REST_Request $route
     * @param string                 $method [Optional]
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_allowed($route, $method = 'GET')
    {
        $result = $this->is_restricted($route, $method);

        return is_bool($result) ? !$result : $result;
    }

     /**
     * Get resource
     *
     * @return AAM_Framework_Resource_ApiRoute
     *
     * @access private
     * @version 7.0.0
     */
    private function _get_resource()
    {
        try {
            $result = $this->_get_access_level()->get_resource(
                AAM_Framework_Type_Resource::API_ROUTE
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Determine route key
     *
     * @param mixed  $route
     * @param string $method
     *
     * @return string
     *
     * @access private
     * @version 7.0.0
     */
    private function _determine_route_key($route, $method)
    {
        if (is_a($route, WP_REST_Request::class)) {
            $endpoint = $route->get_route();
            $method   = $route->get_method();
        } elseif (is_string($route)) {
            $endpoint = $route;
        } else {
            throw new InvalidArgumentException('Invalid route');
        }

        return trim(strtolower("{$method} $endpoint"));
    }

    /**
     * Update existing route
     *
     * @param string $route
     * @param bool   $is_restricted
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _update_route_permission($route, $is_restricted)
    {
        $resource = $this->_get_resource();

        // Prepare array of new permissions
        return $resource->set_permissions(array_merge(
            $resource->get_permissions(true),
            [ $route => [ 'effect' => $is_restricted ? 'deny' : 'allow' ] ]
        ));
    }

    /**
     * Normalize and prepare the route model
     *
     * @param string $endpoint
     * @param string $method
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_route($endpoint, $method)
    {
        return [
            'endpoint'      => strtolower($endpoint),
            'method'        => strtoupper($method),
            'is_restricted' => $this->is_restricted($endpoint, $method),
            'id'            => base64_encode(strtolower("{$method} {$endpoint}"))
        ];
    }

}