<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

/**
 * WordPress MCP server resource.
 *
 * @package AAM
 * @version 8.0.0
 */
class AAM_Framework_Resource_McpServer implements AAM_Framework_Resource_Interface
{

    use AAM_Framework_Resource_BaseTrait;

    /**
     * @inheritDoc
     */
    protected $type = AAM_Framework_Type_Resource::MCP_SERVER;

    /**
     * @inheritDoc
     */
    private function _apply_policy()
    {
        $result = [];

        foreach ($this->policies()->statements('MCPServer:*') as $stm) {
            $server = rawurldecode(substr($stm['Resource'], 10));
            $effect = isset($stm['Effect']) ? strtolower($stm['Effect']) : 'deny';

            $result[$server] = [
                'access' => [
                    'effect' => $effect === 'allow' ? 'allow' : 'deny'
                ]
            ];
        }

        return apply_filters('aam_apply_policy_filter', $result, $this);
    }

}
