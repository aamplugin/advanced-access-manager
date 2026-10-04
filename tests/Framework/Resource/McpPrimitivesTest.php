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
 * MCP prompt and resource rules are independent and scoped to servers.
 *
 * @package AAM
 * @version 8.0.0
 */
final class McpPrimitivesTest extends TestCase
{
    /**
     * Verify exact policy IDs and per-server customization.
     *
     * @return void
     * @access public
     * @version 8.0.0
     */
    public function testExactPromptAndResourceRules(): void
    {
        $level = AAM::api()->user($this->createUser());
        $policy = wp_json_encode([ 'Statement' => [
            [ 'Resource' => 'MCPPrompt:server-a/prompt-a', 'Effect' => 'deny' ],
            [ 'Resource' => 'MCPPrompt:prompt-b', "Server" => "server-a", 'Effect' => 'deny' ],
            [ 'Resource' => 'MCPResource:server-a/resource-a', 'Effect' => 'deny' ],
            [ 'Resource' => 'MCPResource:resource-b', 'Server' => 'server-a', 'Effect' => 'deny' ]
        ] ]);
        $this->assertIsInt(AAM::api()->policies($level)->create($policy));

        $prompts   = AAM::api()->mcp_prompts($level);
        $resources = AAM::api()->mcp_resources($level);

        $this->assertTrue($prompts->is_denied('prompt-a', 'server-a'));
        $this->assertTrue($prompts->is_denied('prompt-b', 'server-a'));
        $this->assertFalse($prompts->is_customized('prompt-a', 'server-a'));
        $this->assertFalse($prompts->is_customized('prompt-b', 'server-a'));

        $this->assertTrue($resources->is_denied('resource-a', 'server-a'));
        $this->assertTrue($resources->is_denied('resource-b', 'server-a'));
        $this->assertFalse($resources->is_customized('resource-a', 'server-a'));
        $this->assertFalse($resources->is_customized('resource-b', 'server-a'));
    }
}
