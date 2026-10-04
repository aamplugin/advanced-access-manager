<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * MCP resource URI access rules.
 *
 * @package AAM
 * @version 8.0.0
 */
class AAM_Framework_Resource_McpResource implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    protected $type = AAM_Framework_Type_Resource::MCP_RESOURCE;

    /**
     * @inheritDoc
     */
    private function _apply_policy()
    {
        $result = [];

        foreach ($this->policies()->statements('MCPResource:*') as $stm) {
            $resource = substr($stm['Resource'], 12);
            $effect   = isset($stm['Effect']) ? strtolower($stm['Effect']) : 'deny';

            if (isset($stm['Server'])) {
                $server   = rawurldecode($stm['Server']);
                $res_name = rawurldecode($resource);
            } else {
                $parts    = explode('/', $resource);
                $server   = isset($parts[0]) ? $parts[0] : null;
                $res_name = isset($parts[1]) ? $parts[1] : null;
            }

            if (!empty($server) && !empty($res_name)) {
                $result[$res_name . '|' . $server] = [
                    'access' => [
                        'effect' => $effect === 'allow' ? 'allow' : 'deny'
                    ]
                ];
            }
        }

        return apply_filters('aam_apply_policy_filter', $result, $this);
    }

    /**
     * @inheritDoc
     */
    private function _get_resource_id($identifier)
    {
        // Preserve the wildcard token in stored IDs for premium pattern matching.
        $result = str_replace('%2A', '*', rawurlencode($identifier->uri));

        if (!empty($identifier->server_id)) {
            $result .= '|' . $identifier->server_id;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    private function _get_resource_identifier($id)
    {
        $parts = explode('|', $id);

        return (object) [
            'uri'       => rawurldecode($parts[0]),
            'server_id' => !empty($parts[1]) ? $parts[1] : null
        ];
    }

}
