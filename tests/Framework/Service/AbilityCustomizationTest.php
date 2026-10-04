<?php

declare(strict_types=1);

namespace AAM\UnitTest\Framework\Service;

use AAM;
use AAM\UnitTest\Utility\TestCase;

/**
 * Explicit Ability and MCP rule ownership
 *
 * @package AAM
 * @version 8.0.0
 */
final class AbilityCustomizationTest extends TestCase
{
    /**
     * A rule for one ability must not mark its neighbors as customized
     *
     * @return void
     * @access public
     *
     * @version 8.0.0
     */
    public function testAbilityCustomizationIsPerResource(): void
    {
        $level = AAM::api()->user($this->createUser());
        $service = AAM::api()->abilities($level);

        $this->assertFalse($service->is_customized('unit/read'));
        $this->assertTrue($service->deny('unit/read'));
        $this->assertTrue($service->is_customized('unit/read'));
        $this->assertFalse($service->is_customized('unit/write'));

        $this->assertTrue($service->reset('unit/read'));
        $this->assertFalse($service->is_customized('unit/read'));
    }

    /**
     * A rule for one MCP server must not mark another server as customized
     *
     * @return void
     * @access public
     *
     * @version 8.0.0
     */
    public function testServerCustomizationIsPerResource(): void
    {
        $level = AAM::api()->user($this->createUser());
        $service = AAM::api()->mcp_servers($level);

        $this->assertTrue($service->deny('unit-server-a'));
        $this->assertTrue($service->is_customized('unit-server-a'));
        $this->assertFalse($service->is_customized('unit-server-b'));
    }

    /**
     * Tool ownership is keyed by both tool and server IDs
     *
     * @return void
     * @access public
     *
     * @version 8.0.0
     */
    public function testToolCustomizationUsesBothIds(): void
    {
        $level = AAM::api()->user($this->createUser());
        $service = AAM::api()->mcp_tools($level);

        $this->assertTrue($service->deny('unit-tool', 'unit-server-a'));
        $this->assertTrue($service->is_customized('unit-tool', 'unit-server-a'));
        $this->assertFalse($service->is_customized('unit-tool', 'unit-server-b'));
        $this->assertFalse($service->is_customized('other-tool', 'unit-server-a'));
    }
}
