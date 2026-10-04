<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * AAM service MCP server manager
 *
 * @package AAM
 * @version 7.0.0
 */
class AAM_Framework_Service_McpServers
{

    use AAM_Framework_Service_BaseTrait;

    /**
     * Deny access to the MCP server
     * 
     * @param string $name
     * 
     * @return bool|WP_Error
     * @access public
     *
     * @version 8.0.0
     */
    public function deny($name)
    {
        try {
            $result = $this->_update_item_permission($name, true);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Allow access to the MCP server
     * 
     * @param string $name
     * 
     * @return bool|WP_Error
     * @access public
     *
     * @version 8.0.0
     */
    public function allow($name)
    {
        try {
            $result = $this->_update_item_permission($name, false);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Reset access controls
     * 
     * @param string $name
     * 
     * @return bool
     * @access public
     * 
     * @version 8.0.0
     */
    public function reset($name = null)
    {
        try {
            $resource = $this->_get_resource();

            if (is_null($name)) {
                $result = $resource->reset();
            } else {
                $result = $resource->remove_permission($name, 'access');
            }
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if MCP server is restricted
     *
     * @param string $name
     *
     * @return boolean
     * @access public
     *
     * @version 8.0.0
     */
    public function is_denied($name)
    {
        try {
            $result     = null;
            $resource   = $this->_get_resource();
            $identifer  = $this->_normalize_resource_identifier($name);
            $permission = $resource->get_permission($identifer, 'access');

            // Step #1. Determine if MCP servier is explicitly restricted
            if (!empty($permission)) {
                $result = $permission['effect'] !== 'allow';
            }

            // Step #2. Allow third-party implementation to influence the decision
            $result = apply_filters(
                'aam_mcp_server_is_denied_filter',
                $result,
                $identifer,
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
     * Check if MCP server is allowed
     *
     * @param mixed $name
     *
     * @return boolean
     * @access public
     *
     * @version 8.0.0
     */
    public function is_allowed($name)
    {
        $result = $this->is_denied($name);

        return is_bool($result) ? !$result : $result;
    }

    /**
     * Update existing item permission
     *
     * @param string $id
     * @param bool   $is_denied [Optional]
     *
     * @return array
     * @access private
     *
     * @version 7.0.0
     */
    private function _update_item_permission($id, $is_denied = true)
    {
        return $this->_get_resource()->set_permission(
            $id,
            'access',
            $is_denied
        );
    }

    /**
     * Get resource
     *
     * @return AAM_Framework_Resource_McpServer
     * @access private
     *
     * @version 8.0.0
     */
    private function _get_resource()
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::MCP_SERVER
        );
    }

}