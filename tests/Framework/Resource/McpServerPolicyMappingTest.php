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
 * Core MCP server policy mapping tests
 *
 * @package AAM
 * @version 8.0.0
 */
final class McpServerPolicyMappingTest extends TestCase
{
    /**
     * Verify core maps exact IDs and leaves wildcard rules to the add-on
     *
     * @return void
     * @access public
     *
     * @version 8.0.0
     */
    public function testCorePolicyMappingIsExactOnly(): void
    {
        $user_id = $this->createUser();
        $level = AAM::api()->user($user_id);
        $policy = wp_json_encode([
            'Statement' => [
                [ 'Resource' => 'MCPServer:mcp-adapter-default-server', 'Effect' => 'deny' ]
            ]
        ]);
        $this->assertIsInt(AAM::api()->policies($level)->create($policy));

        $core_permissions = null;
        $capture = function($permissions, $resource) use (&$core_permissions) {
            if ($resource->type === 'mcp_server') {
                $core_permissions = $permissions;
            }
            return $permissions;
        };
        add_filter('aam_apply_policy_filter', $capture, 1, 2);
        try {
            $level->get_resource('mcp_server');
        } finally {
            remove_filter('aam_apply_policy_filter', $capture, 1);
        }

        $this->assertSame('deny', $core_permissions['mcp-adapter-default-server']['access']['effect']);
    }
}
