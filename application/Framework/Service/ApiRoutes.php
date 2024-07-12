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
            $result   = array();
            $subject  = $this->_get_subject($inline_context);
            $object   = $subject->reloadObject(AAM_Core_Object_Route::OBJECT_TYPE);
            $explicit = $object->getExplicitOption();

            // Iterating over the list of all registered API routes and compile the
            // list
            foreach (rest_get_server()->get_routes() as $route => $handlers) {
                $methods = array();

                foreach ($handlers as $handler) {
                    $methods = array_merge(
                        $methods, array_keys($handler['methods'])
                    );
                }

                foreach (array_unique($methods) as $method) {
                    $mask = strtolower("restful|{$route}|{$method}");

                    array_push(
                        $result,
                        $this->_prepare_route(
                            $mask,
                            $object->isRestricted('restful', $route, $method),
                            !array_key_exists($mask, $explicit)
                        )
                    );
                }
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Get existing route by ID
     *
     * @param int   $id             Sudo-id for the rule
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 6.9.10
     * @throws OutOfRangeException If rule does not exist
     */
    public function get_route_by_id($id, $inline_context = null)
    {
        try {
            $result = false;

            foreach($this->get_route_list($inline_context) as $route) {
                if ($route['id'] === $id) {
                    $result = $route;
                }
            }

            if ($result === false) {
                throw new OutOfRangeException(__(
                    'Route does not exist', AAM_KEY
                ));
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Update existing route
     *
     * @param int   $id             Sudo-id for the rule
     * @param bool  $is_restricted  Is restricted or not
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 6.9.10
     * @throws RuntimeException If fails to persist a rule
     */
    public function update_route_permission(
        $id, $is_restricted = true, $inline_context = null
    ) {
        try {
            $route   = $this->get_route_by_id($id);
            $subject = $this->_get_subject($inline_context);
            $object  = $subject->getObject(AAM_Core_Object_Route::OBJECT_TYPE);
            $mask    = strtolower("restful|{$route['route']}|{$route['method']}");

            $object->store($mask, $is_restricted);

            if ($object->store($mask, $is_restricted) === false) {
                throw new RuntimeException('Failed to persist settings');
            }

            $result = $this->get_route_by_id($id);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Delete route
     *
     * @param int   $id             Sudo-id for the rule
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 6.9.10
     * @throws OutOfRangeException If rule does not exist
     * @throws RuntimeException If fails to persist a rule
     */
    public function delete_route_permission($id, $inline_context = null)
    {
        try {
            $subject = $this->_get_subject($inline_context);
            $object  = $subject->getObject(AAM_Core_Object_Route::OBJECT_TYPE);

            // Find the rule that we are updating
            $found = null;

            // Note! User can delete only explicitly set rule (overwritten rule)
            $original_options = $object->getExplicitOption();
            $new_options      = array();

            foreach($original_options as $route => $is_restricted) {
                $parts = explode('|', $route);

                if (abs(crc32($parts[1] . $parts[2])) === $id) {
                    $found = array(
                        'mask'       => $route,
                        'restricted' => $is_restricted
                    );
                } else {
                    $new_options[$route] = $is_restricted;
                }
            }

            if ($found) {
                $object->setExplicitOption($new_options);
                $success = $object->save();
            } else {
                throw new OutOfRangeException('Route does not exist');
            }

            if (!$success) {
                throw new RuntimeException('Failed to persist settings');
            }

            $result = $this->get_route_by_id($id);
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
            // Reset the object
            $subject = $this->_get_subject($inline_context);
            $object  = $subject->getObject(AAM_Core_Object_Route::OBJECT_TYPE);

            // Reset settings to default
            $object->reset();

            $result = $this->get_route_list($inline_context);
        } catch (Exception $e) {
            $result = $this->_handle_error($e, $inline_context);
        }

        return $result;
    }

    /**
     * Normalize and prepare the route model
     *
     * @param string $route
     * @param bool   $is_restricted
     * @param bool   $is_inherited
     *
     * @return array
     *
     * @access private
     * @version 6.9.10
     */
    private function _prepare_route(
        $route, $is_restricted = false, $is_inherited = false
    ) {
        $parts = explode('|', $route);

        return array(
            'id'            => abs(crc32($parts[1].$parts[2])),
            'route'         => $parts[1],
            'method'        => strtoupper($parts[2]),
            'is_restricted' => $is_restricted,
            'is_inherited'  => $is_inherited
        );
    }

}