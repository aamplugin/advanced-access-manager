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
 * @version 6.9.10
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
     * @access public
     * @version 6.9.10
     */
    public function get_route_list($inline_context = null)
    {
        $response = array();
        $subject  = $this->_get_subject($inline_context);
        $object   = $subject->getObject(AAM_Core_Object_Route::OBJECT_TYPE);

        $options  = $object->getOption();
        $explicit = $object->getExplicitOption();

        // Iterating over the list of all registered API routes and compile the
        // list
        foreach (rest_get_server()->get_routes() as $route => $handlers) {
            $methods = array();

            foreach ($handlers as $handler) {
                $methods = array_merge($methods, array_keys($handler['methods']));
            }

            foreach (array_unique($methods) as $method) {
                $mask = strtolower("restful|{$route}|{$method}");

                if (isset($options[$mask])) { // Do we have permission defined
                    array_push(
                        $response,
                        $this->_prepare_route(
                            $mask,
                            $options[$mask],
                            !array_key_exists($mask, $explicit)
                        )
                    );
                } else {
                    array_push($response, $this->_prepare_route($mask));
                }
            }
        }

        // if (is_array($options) && count($options)) {
        //     foreach($options as $route => $is_restricted) {
        //         array_push(
        //             $response,
        //             $this->_prepare_route(
        //                 $route,
        //                 $is_restricted,
        //                 !array_key_exists($route, $explicit)
        //             )
        //         );
        //     }
        // }

        return $response;
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
     * @throws UnderflowException If rule does not exist
     */
    public function get_route_by_id($id, $inline_context = null)
    {
        $found = false;

        foreach($this->get_route_list($inline_context) as $route) {
            if ($route['id'] === $id) {
                $found = $route;
            }
        }

        if ($found === false) {
            throw new UnderflowException('Route does not exist');
        }

        return $found;
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
     * @throws UnderflowException If rule does not exist
     * @throws Exception If fails to persist a rule
     */
    public function update_route_permission(
        $id, $is_restricted = true, $inline_context = null
    ) {
        $route   = $this->get_route_by_id($id);
        $subject = $this->_get_subject($inline_context);
        $object  = $subject->getObject(AAM_Core_Object_Route::OBJECT_TYPE);
        $mask    = strtolower("restful|{$route['route']}|{$route['method']}");

        $object->store($mask, $is_restricted);

        if ($object->store($mask, $is_restricted) === false) {
            throw new Exception('Failed to persist the route permission');
        }

        return $this->_prepare_route($mask, $is_restricted);
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
     * @throws UnderflowException If rule does not exist
     * @throws Exception If fails to persist a rule
     */
    public function delete_route_permission($id, $inline_context = null)
    {
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
            throw new UnderflowException('Route does not exist');
        }

        if (!$success) {
            throw new Exception('Failed to persist the rule');
        }

        return $this->_prepare_route($found['mask'], $found['restricted']);
    }

    /**
     * Reset all routes
     *
     * @param array $inline_context Runtime context
     *
     * @return array
     *
     * @access public
     * @version 6.9.10
     */
    public function reset_routes($inline_context = null)
    {
        $response = array();

        // Reset the object
        $subject = $this->_get_subject($inline_context);
        $object  = $subject->getObject(AAM_Core_Object_Route::OBJECT_TYPE);

        // Communicate about number of rules that were deleted
        $response['deleted_routes_count'] = count($object->getExplicitOption());

        // Reset
        $response['success'] = $object->reset();

        return $response;
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
        $route, $is_restricted = null, $is_inherited = false
    ) {
        $parts    = explode('|', $route);
        $response = array(
            'id'     => abs(crc32($parts[1].$parts[2])),
            'route'  => $parts[1],
            'method' => strtoupper($parts[2])
        );

        if (is_bool($is_restricted)) { // If restriction explicitly defined
            $response['is_restricted'] = $is_restricted;
            $response['is_inherited']  = $is_inherited;
        }

        return $response;
    }

}