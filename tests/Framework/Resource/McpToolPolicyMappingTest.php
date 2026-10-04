<?php

declare(strict_types=1);

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'license.txt', which is part of this source code package.       *
 * ======================================================================
 */

namespace AAM\UnitTest\Framework\Resource;

use AAM;
use AAM\UnitTest\Utility\TestCase;

/**
 * Core MCP tool policy mapping tests
 *
 * @package AAM
 * @version 8.0.0
 */
final class McpToolPolicyMappingTest extends TestCase
{
    /**
     * Verify exact tool rules require both IDs and wildcards stay premium
     *
     * @return void
     * @access public
     *
     * @version 8.0.0
     */
    public function testCorePolicyMappingRequiresServerAndTool(): void
    {
        $user_id = $this->createUser();
        $level = AAM::api()->user($user_id);
        $policy = wp_json_encode([
            'Statement' => [
                [ 'Resource' => 'MCPTool:server-a/tool-a', 'Effect' => 'deny' ],
                [ 'Resource' => 'MCPTool:tool-b', 'Server' => 'server-b', 'Effect' => 'allow' ],
                [ 'Resource' => 'MCPTool:tool-c', 'Effect' => 'deny' ],
                [ 'Resource' => 'MCPTool:server-d/', 'Effect' => 'deny' ]
            ]
        ]);
        $this->assertIsInt(AAM::api()->policies($level)->create($policy));

        $core_permissions = null;
        $capture = function($permissions, $resource) use (&$core_permissions) {
            if ($resource->type === 'mcp_tool') {
                $core_permissions = $permissions;
            }
            return $permissions;
        };
        add_filter('aam_apply_policy_filter', $capture, 1, 2);
        try {
            $level->get_resource('mcp_tool');
        } finally {
            remove_filter('aam_apply_policy_filter', $capture, 1);
        }

        $this->assertSame('deny', $core_permissions['tool-a|server-a']['access']['effect']);
        $this->assertSame('allow', $core_permissions['tool-b|server-b']['access']['effect']);
        $this->assertCount(2, $core_permissions);
    }
}
