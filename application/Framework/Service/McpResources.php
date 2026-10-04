<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Access rules for resource URIs within registered MCP servers.
 *
 * @package AAM
 * @version 8.0.0
 */
class AAM_Framework_Service_McpResources
{
    
    use AAM_Framework_Service_BaseTrait;

    /**
     * Deny access to the MCP resource
     * 
     * @param string|\WP\MCP\Domain\Resources\McpResource $resource
     * @param string                                      $server_id
     * 
     * @return bool|WP_Error
     * @access public
     *
     * @version 8.0.0
     */
    public function deny($resource, $server_id)
    {
        try {
            $result = $this->_update_item_permission(
                $this->_normalize_resource_identifier($resource, $server_id), 
                true
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Allow access to the MCP resource
     * 
     * @param string|\WP\MCP\Domain\Resources\McpResource $resource
     * @param string                                      $server_id
     * 
     * @return bool|WP_Error
     * @access public
     *
     * @version 8.0.0
     */
    public function allow($resource, $server_id)
    {
        try {
            $result = $this->_update_item_permission(
                $this->_normalize_resource_identifier($resource, $server_id), 
                false
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Reset access controls
     * 
     * @param string|\WP\MCP\Domain\Resources\McpResource $resource
     * @param string                                      $server_id
     * 
     * @return bool
     * @access public
     * 
     * @version 8.0.0
     */
    public function reset($resource = null, $server_id = null)
    {
        try {
            $res = $this->_get_resource();

            if (is_null($resource)) {
                $result = $res->reset();
            } elseif (!is_null($server_id)) {
                $result = $res->remove_permission(
                    $this->_normalize_resource_identifier($resource, $server_id), 
                    'access'
                );
            } else {
                throw new InvalidArgumentException('MCP server ID is required');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check whether this resource has an explicit rule for the access level
     *
     * @param string|\WP\MCP\Domain\Resources\McpResource $resource
     * @param string                                      $server_id
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 8.0.0
     */
    public function is_customized($resource = null, $server_id = null)
    {
        try {
            $identifier = is_null($resource) ? null
                : $this->_normalize_resource_identifier($resource, $server_id);
            $result = $this->_get_resource()->is_customized($identifier);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if MCP resource is restricted
     *
     * @param string|\WP\MCP\Domain\Resources\McpResource $resource
     * @param string                                      $server_id
     *
     * @return boolean
     * @access public
     *
     * @version 8.0.0
     */
    public function is_denied($resource, $server_id)
    {
        try {
            $result     = null;
            $res        = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier(
                $resource, $server_id
            );

            $permission = $res->get_permission($identifier, 'access');

            // Step #1. Determine if MCP resource is explicitly restricted
            if (!empty($permission)) {
                $result = $permission['effect'] !== 'allow';
            }

            // Step #2. Allow third-party implementation to influence the decision
            $result = apply_filters(
                'aam_mcp_resource_is_denied_filter',
                $result,
                $identifier,
                $res
            );

            // Step #3. Deremine if MCP server is explicitly restricted
            if (is_null($result)) {
                $result = AAM::api()->mcp_servers(
                    $res->get_access_level()
                )->is_denied($server_id);
            }

            // Prepare the final answer
            $result = is_bool($result) ? $result : false;
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if MCP resource is allowed
     *
     * @param string|\WP\MCP\Domain\Resources\McpResource $resource
     * @param string                                      $server_id
     *
     * @return boolean
     * @access public
     *
     * @version 8.0.0
     */
    public function is_allowed($resource, $server_id)
    {
        $result = $this->is_denied($resource, $server_id);

        return is_bool($result) ? !$result : $result;
    }

    /**
     * Update existing item permission
     *
     * @param object $identifier
     * @param bool   $is_denied [Optional]
     *
     * @return array
     * @access private
     *
     * @version 8.0.0
     */
    private function _update_item_permission($identifier, $is_denied = true)
    {
        return $this->_get_resource()->set_permission(
            $identifier,
            'access',
            $is_denied
        );
    }

    /**
     * Convert a resource URI and server ID into a resource identifier
     *
     * @param string|\WP\MCP\Domain\Resources\McpResource $resource
     * @param string|null                                 $server_id
     *
     * @return object
     * @access private
     *
     * @version 8.0.0
     */
    private function _normalize_resource_identifier($resource, $server_id)
    {
        if (is_object($resource) && method_exists($resource, 'getUri')) {
            $uri = $resource->getUri();
        } elseif (is_string($resource)) {
            $uri = $resource;
        } else {
            $uri = '';
        }

       return (object) [
            'uri'       => $uri,
            'server_id' => $server_id
       ];
    }

    /**
     * Get resource
     *
     * @return AAM_Framework_Resource_McpResource
     * @access private
     *
     * @version 8.0.0
     */
    private function _get_resource()
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::MCP_RESOURCE
        );
    }

}