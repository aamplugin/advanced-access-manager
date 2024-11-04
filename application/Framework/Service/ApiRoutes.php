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
 * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/401
 * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/304
 * @since 6.9.10 Initial implementation of the class
 *
 * @package AAM
 * @version 6.9.35
 */
class AAM_Framework_Service_ApiRoutes
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Return list of permissions
     *
     * @param array $inline_context Context
     *
     * @return array
     *
     * @since 6.9.13 https://github.com/aamplugin/advanced-access-manager/issues/304
     * @since 6.9.10 Initial implementation of the method
     *
     * @access public
     * @version 6.9.13
     */
    public function get_route_list($inline_context = null)
    {
        try {
            $result   = [];
            $resource = $this->get_resource($inline_context);

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
                    array_push($result, $this->_prepare_route(
                        $endpoint, $method, $resource
                    ));
                }
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get existing route by combination of endpoint and HTTP method
     *
     * @param string      $endpoint       Route endpoint
     * @param string|null $method         HTTP method
     * @param array|null  $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function get_route($endpoint, $method = 'GET', $inline_context = null)
    {
        try {
            $routes = $this->get_route_list($inline_context);
            $found  = array_filter($routes, function($r) use ($endpoint, $method) {
                return $r['endpoint'] === strtolower($endpoint)
                            && $r['method'] === strtoupper($method);
            });

            if (empty($found)) {
                throw new OutOfRangeException(__(
                    'Route does not exist', AAM_KEY
                ));
            } else{
                $result = array_shift($found);
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Update existing route
     *
     * @param bool        $is_restricted  Is restricted or not
     * @param string      $endpoint       API endpoint
     * @param string|null $method         HTTP method
     * @param array|null  $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function update_route_permission(
        $is_restricted, $endpoint, $method = 'GET', $inline_context = null
    ) {
        try {
            $resource = $this->get_resource($inline_context);

            // Prepare array of new permissions
            $perms = array_merge($resource->get_permissions(true), [
                strtolower("{$method} {$endpoint}") => [
                    'effect' => $is_restricted ? 'deny' : 'allow'
                ]
            ]);

            if (!$resource->set_permissions($perms)) {
                throw new RuntimeException('Failed to persist settings');
            }

            $result = $this->get_route($endpoint, $method);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Delete route
     *
     * @param string      $endpoint       API endpoint
     * @param string|null $method         HTTP method
     * @param array|null  $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 7.0.0
     */
    public function delete_route_permission(
        $endpoint, $method = 'GET', $inline_context = null
    ) {
        try {
            $access_level = $this->_get_access_level($inline_context);
            $resource  = $access_level->get_resource(
                AAM_Framework_Type_Resource::API_ROUTE
            );

            // Compile the rule's key
            $key = strtolower("{$method} {$endpoint}");

            // Note! User can delete only explicitly set rule (overwritten rule)
            $permissions = $resource->get_permissions(true);

            if (array_key_exists($key, $permissions)) {
                unset($permissions[$key]);
            } else {
                throw new OutOfRangeException('Route does not exist');
            }

            if (!$resource->set_permissions($permissions)) {
                throw new RuntimeException('Failed to persist settings');
            }

            $result = true;
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Reset all routes
     *
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @since 6.9.35 https://github.com/aamplugin/advanced-access-manager/issues/401
     * @since 6.9.10 Initial implementation of the method
     *
     * @access public
     * @version 6.9.35
     */
    public function reset($inline_context = null)
    {
        try {
            $resource = $this->get_resource($inline_context);

            // Reset settings to default
            $resource->reset();

            $result = $this->get_route_list($inline_context);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get resource
     *
     * @param array$inline_context
     *
     * @return AAM_Framework_Resource_ApiRoute
     *
     * @access public
     * @version 7.0.0
     */
    public function get_resource($inline_context = null)
    {
        try {
            $access_level = $this->_get_access_level($inline_context);
            $result       = $access_level->get_resource(
                AAM_Framework_Type_Resource::API_ROUTE
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Check if API route is restricted or not
     *
     * @param string|WP_REST_Request $route
     * @param string|null            $method
     * @param array|null             $inline_context
     *
     * @return boolean
     *
     * @access public
     * @version 7.0.0
     */
    public function is_restricted($route, $method = 'GET', $inline_context = null)
    {
        try {
            if (is_a($route, WP_REST_Request::class)) {
                $endpoint = $route->get_route();
                $method   = $route->get_method();
            } elseif (is_string($route)) {
                $endpoint = $route;
            } else {
                throw new InvalidArgumentException('Invalid route endpoint');
            }

            $result = $this->get_resource($inline_context)->is_restricted(
                $endpoint, $method
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Normalize and prepare the route model
     *
     * @param string                          $endpoint
     * @param string                          $method
     * @param AAM_Framework_Resource_ApiRoute $resource
     *
     * @return array
     *
     * @access private
     * @version 7.0.0
     */
    private function _prepare_route($endpoint, $method, $resource)
    {
        $explicit = $resource->get_permissions(true);
        $key      = strtolower("{$method} {$endpoint}");

        return [
            'endpoint'      => strtolower($endpoint),
            'method'        => strtoupper($method),
            'is_restricted' => $resource->is_restricted($endpoint, $method),
            'is_inherited'  => !array_key_exists($key, $explicit),
            'id'            => base64_encode($key)
        ];
    }

}