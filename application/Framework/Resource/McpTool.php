<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * MCP tool access resource
 *
 * @package AAM
 * @version 8.0.0
 */
class AAM_Framework_Resource_McpTool implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    protected $type = AAM_Framework_Type_Resource::MCP_TOOL;

    /**
     * @inheritDoc
     */
    private function _apply_policy()
    {
        $result = [];

        foreach ($this->policies()->statements('MCPTool:*') as $stm) {
            $tool   = substr($stm['Resource'], 8);
            $effect = isset($stm['Effect']) ? strtolower($stm['Effect']) : 'deny';

            if (isset($stm['Server'])) {
                $server    = rawurldecode($stm['Server']);
                $tool_name = rawurldecode($tool);
            } else {
                $parts     = explode('/', $tool);
                $server    = isset($parts[0]) ? $parts[0] : null;
                $tool_name = isset($parts[1]) ? $parts[1] : null;
            }

            if (!empty($server) && !empty($tool_name)) {
                $result[$tool_name . '|' . $server] = [
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
        $result = $identifier->name;

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
            'name'      => $parts[0],
            'server_id' => !empty($parts[1]) ? $parts[1] : null
        ];
    }

}
