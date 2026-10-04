<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * Access rules for prompts within registered MCP servers.
 *
 * @package AAM
 * @version 8.0.0
 */
class AAM_Framework_Service_McpPrompts
{
    
    use AAM_Framework_Service_BaseTrait;

    /**
     * Deny access to the MCP prompt
     * 
     * @param string|\WP\MCP\Domain\Prompts\McpPrompt $prompt
     * @param string                                  $server_id
     * 
     * @return bool|WP_Error
     * @access public
     *
     * @version 8.0.0
     */
    public function deny($prompt, $server_id)
    {
        try {
            $result = $this->_update_item_permission(
                $this->_normalize_resource_identifier($prompt, $server_id), 
                true
            );
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Allow access to the MCP prompt
     * 
     * @param string|\WP\MCP\Domain\Prompts\McpPrompt $prompt
     * @param string                                  $server_id
     * 
     * @return bool|WP_Error
     * @access public
     *
     * @version 8.0.0
     */
    public function allow($prompt, $server_id)
    {
        try {
            $result = $this->_update_item_permission(
                $this->_normalize_resource_identifier($prompt, $server_id), 
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
     * @param string|\WP\MCP\Domain\Prompts\McpPrompt $prompt
     * @param string                                  $server_id
     * 
     * @return bool
     * @access public
     * 
     * @version 8.0.0
     */
    public function reset($prompt = null, $server_id = null)
    {
        try {
            $resource = $this->_get_resource();

            if (is_null($prompt)) {
                $result = $resource->reset();
            } elseif (!is_null($server_id)) {
                $result = $resource->remove_permission(
                    $this->_normalize_resource_identifier($prompt, $server_id), 
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
     * Check whether this prompt has an explicit rule for the access level
     *
     * @param string|\WP\MCP\Domain\Prompts\McpPrompt $prompt    Prompt ID
     * @param string                                  $server_id MCP server ID
     *
     * @return bool|WP_Error
     * @access public
     *
     * @version 8.0.0
     */
    public function is_customized($prompt = null, $server_id = null)
    {
        try {
            $identifier = is_null($prompt) 
                ? null
                : $this->_normalize_resource_identifier($prompt, $server_id);

            $result = $this->_get_resource()->is_customized($identifier);
        } catch (Exception $e) {
            $result = $this->_handle_error($e);
        }

        return $result;
    }

    /**
     * Check if MCP prompt is restricted
     *
     * @param string|\WP\MCP\Domain\Prompts\McpPrompt $prompt
     * @param string                                  $server_id
     *
     * @return boolean
     * @access public
     *
     * @version 8.0.0
     */
    public function is_denied($prompt, $server_id)
    {
        try {
            $result     = null;
            $resource   = $this->_get_resource();
            $identifier = $this->_normalize_resource_identifier(
                $prompt, $server_id
            );

            $permission = $resource->get_permission($identifier, 'access');

            // Step #1. Determine if MCP prompt is explicitly restricted
            if (!empty($permission)) {
                $result = $permission['effect'] !== 'allow';
            }

            // Step #2. Allow third-party implementation to influence the decision
            $result = apply_filters(
                'aam_mcp_prompt_is_denied_filter',
                $result,
                $identifier,
                $resource
            );

            // Step #3. Deremine if MCP server is explicitly restricted
            if (is_null($result)) {
                $result = AAM::api()->mcp_servers(
                    $resource->get_access_level()
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
     * Check if MCP prompt is allowed
     *
     * @param string|\WP\MCP\Domain\Prompts\McpPrompt $name
     * @param string $server_id
     *
     * @return boolean
     * @access public
     *
     * @version 8.0.0
     */
    public function is_allowed($name, $server_id)
    {
        $result = $this->is_denied($name, $server_id);

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
     * Convert prompt and server IDs into a resource identifier
     *
     * @param string|\WP\MCP\Domain\Prompts\McpPrompt $prompt
     * @param string|null                             $server_id
     *
     * @return object
     * @access private
     *
     * @version 8.0.0
     */
    private function _normalize_resource_identifier($prompt, $server_id)
    {
        if (is_object($prompt) && method_exists($prompt, 'getName')) {
            $name = $prompt->getName();
        } elseif (is_string($prompt)) {
            $name = $prompt;
        } else {
            $name = '';
        }

        return (object) [
            'name'      => $name,
            'server_id' => $server_id
       ];
    }

    /**
     * Get resource
     *
     * @return AAM_Framework_Resource_McpPrompt
     * @access private
     *
     * @version 8.0.0
     */
    private function _get_resource()
    {
        return $this->_get_access_level()->get_resource(
            AAM_Framework_Type_Resource::MCP_PROMPT
        );
    }

}