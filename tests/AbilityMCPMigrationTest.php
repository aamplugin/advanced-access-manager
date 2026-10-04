<?php

/** Standalone migration contract: php tests/AbilityMCPMigrationTest.php */
class MigrationDb
{
    public $settings = [];

    public function read($name, $default = null)
    {
        return $name === 'aam_settings' ? $this->settings : $default;
    }

    public function write($name, $value)
    {
        if ($name !== 'aam_settings') {
            throw new RuntimeException('Unexpected option');
        }
        $this->settings = $value;
        return true;
    }
}

class AAM
{
    public static $gateway;
    public static function api() { return self::$gateway; }
}

function verify($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

AAM::$gateway = (object) [ 'db' => new MigrationDb() ];
$db = AAM::api()->db;
$deny = [ 'access' => [ 'effect' => 'deny' ] ];
$allow = [ 'access' => [ 'effect' => 'allow' ] ];
$db->settings = [
    'role' => [ 'editor' => [
        'ability' => [
            'ability:acme/read' => $allow,
            'server:*' => $deny,
            'server:example' => $allow,
            'tool:example:delete' => $deny
        ],
        'mcp_server' => [ 'server:example' => $deny ]
    ] ],
    'user' => [ 42 => [
        'ability' => [ 'tool:example:read' => $allow ]
    ] ],
    'visitor' => [
        'ability' => [ 'server:public' => $allow ]
    ]
];

include __DIR__ . '/../application/Migration/Migration_AbilityMCPResources.php';
verify($db->settings['role']['editor']['abilities'] === [
    'ability:acme/read' => $allow
], 'Ability rules must remain in the ability resource');
verify($db->settings['role']['editor']['mcp_server'] === [
    'server:example' => $deny,
    'server:*' => $deny
], 'MCP server migration must preserve existing newer rules');
verify($db->settings['role']['editor']['mcp_tools']['tool:example:delete'] === $deny,
    'Role tool rules must move to the tool resource');
verify($db->settings['user'][42]['mcp_tools']['tool:example:read'] === $allow,
    'User tool rules must move to the tool resource');
verify($db->settings['visitor']['mcp_server']['server:public'] === $allow,
    'Visitor server rules must move to the server resource');

$migrated = $db->settings;
include __DIR__ . '/../application/Migration/Migration_AbilityMCPResources.php';
verify($db->settings === $migrated, 'Migration must be idempotent');

echo "Ability and MCP settings migration passed\n";
