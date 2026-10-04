<?php

/**
 * Standalone policy integration test: php tests/AbilityPolicyTest.php
 */

function verify($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$GLOBALS['filters'] = [];
function add_filter($name, $callback)
{
    $GLOBALS['filters'][$name][] = $callback;
}
function apply_filters($name, $value, ...$args)
{
    foreach ($GLOBALS['filters'][$name] ?? [] as $callback) {
        $value = $callback($value, ...$args);
    }

    return $value;
}

class AAM_Framework_Type_Resource
{
    const ABILITIES = 'abilities';
    const MCP_SERVERS = 'mcp_servers';
    const MCP_TOOLS = 'mcp_tools';
}

interface AAM_Framework_Resource_Interface {}

class PolicyStatements
{
    public $items = [];

    public function statements($pattern)
    {
        $prefix = substr($pattern, 0, -1);

        return array_values(array_filter($this->items, function($item) use ($prefix) {
            return stripos($item['Resource'], $prefix) === 0;
        }));
    }
}

trait AAM_Framework_Resource_BaseTrait
{
    public $rules = [];
    public $explicit = [];

    public function policies() { return $GLOBALS['policies']; }
    public function get_permissions() { return $this->rules; }
    public function get_permission($key, $name)
    {
        return $this->rules[$key][$name] ?? null;
    }
    public function is_customized($key) { return !empty($this->explicit[$key]); }
}

require __DIR__ . '/../application/Framework/Resource/Ability.php';
require __DIR__ . '/../application/Framework/Resource/MCPServer.php';
require __DIR__ . '/../application/Framework/Resource/MCPTool.php';
require __DIR__ . '/../aam-complete-package-v8/application/Framework/Resource/Ability.php';
require __DIR__ . '/../aam-complete-package-v8/application/Framework/Resource/MCPServer.php';

$GLOBALS['policies'] = new PolicyStatements();
$resource = new AAM_Framework_Resource_Ability();
$method = new ReflectionMethod($resource, '_apply_policy');
$method->setAccessible(true);
$server_resource = new AAM_Framework_Resource_MCPServer();
$server_method = new ReflectionMethod($server_resource, '_apply_policy');
$server_method->setAccessible(true);
$tool_resource = new AAM_Framework_Resource_MCPTool();
$tool_method = new ReflectionMethod($tool_resource, '_apply_policy');
$tool_method->setAccessible(true);

$GLOBALS['policies']->items = [
    [ 'Resource' => 'Ability:acme/read-order', 'Effect' => 'deny' ],
    [ 'Resource' => 'Ability:acme/allowed', 'Effect' => 'allow' ],
    [ 'Resource' => 'ability:acme/lowercase', 'Effect' => 'allow' ],
    [ 'Resource' => 'MCPServer:vendor%2Fsite%3Aadmin', 'Effect' => 'allow' ],
    [ 'Resource' => 'MCPTool:vendor%2Fsite%3Aadmin:delete%3Aorder', 'Effect' => 'deny' ],
    [ 'Resource' => 'Ability:acme/write-order', 'Action' => 'Execute', 'Effect' => 'deny' ],
    [ 'Resource' => 'Ability:*', 'Effect' => 'deny' ],
    [ 'Resource' => 'MCPServer:*', 'Effect' => 'deny' ],
    [ 'Resource' => 'Ability:acme/*', 'Effect' => 'deny' ]
];

$rules = $method->invoke($resource);
verify($rules['ability:acme/read-order']['access']['effect'] === 'deny',
    'Exact ability rules must map to the service key');
verify($rules['ability:acme/lowercase']['access']['effect'] === 'allow',
    'Policy resource prefixes must match without regard to case');
$server_rules = $server_method->invoke($server_resource);
$tool_rules = $tool_method->invoke($tool_resource);
verify(!isset($rules['server:vendor%2Fsite%3Aadmin'])
    && !isset($rules['tool:vendor%2Fsite%3Aadmin:delete%3Aorder'])
    && !isset($server_rules['ability:acme/read-order'])
    && !isset($tool_rules['server:vendor%2Fsite%3Aadmin']),
    'Policies must stay within their own resources');
verify($server_rules['server:vendor%2Fsite%3Aadmin']['access']['effect'] === 'allow',
    'MCP server IDs must use the service encoding');
verify($tool_rules['tool:vendor%2Fsite%3Aadmin:delete%3Aorder']['access']['effect'] === 'deny',
    'MCP tool IDs must use the service encoding');
verify(!isset($rules['ability:acme/write-order']),
    'Unsupported policy actions must not silently apply');
verify(!isset($rules['ability:*']) && !isset($server_rules['server:*']),
    'Core must not interpret premium wildcard resources');

$premium = \AAM\AddOn\CompletePackage\Framework\Resource\Ability::bootstrap();
$premium_server = \AAM\AddOn\CompletePackage\Framework\Resource\MCPServer::bootstrap();
add_filter('aam_ability_policy_resource_key_filter', [ $premium, 'map_policy_resource' ]);
add_filter('aam_mcp_server_policy_resource_key_filter', [ $premium_server, 'map_policy_resource' ]);
$rules = $method->invoke($resource);
$server_rules = $server_method->invoke($server_resource);
$resource->rules = $rules;
$server_resource->rules = $server_rules;

verify($rules['ability:*']['access']['effect'] === 'deny'
    && $server_rules['server:*']['access']['effect'] === 'deny',
    'Premium filter must map the two default access resources');
verify(!isset($rules['ability:acme/*']),
    'Unimplemented partial wildcards must remain unsupported');
verify($premium->trigger_inheritance($resource, 'ability:acme/other')
    === [ 'access' => [ 'effect' => 'deny' ] ],
    'An unlisted ability must inherit a policy default');
verify($premium->resolve_permissions(
    [ 'access' => [ 'effect' => 'allow' ] ], 'ability:acme/other', $resource
)['access']['effect'] === 'deny',
    'A policy default at this level must override an inherited item rule');
verify($premium->resolve_permissions(
    [ 'access' => [ 'effect' => 'allow' ] ], 'ability:acme/allowed', $resource
)['access']['effect'] === 'allow',
    'A specific policy rule must stay ahead of a wildcard default');
verify($premium->trigger_inheritance($resource, 'tool:vendor:delete') === [],
    'A server default must not become a tool rule');
verify($premium_server->trigger_inheritance($server_resource, 'server:example')
    === [ 'access' => [ 'effect' => 'deny' ] ],
    'MCP server rules must inherit their own default');
verify($premium_server->trigger_inheritance($server_resource, 'tool:example:read') === [],
    'MCP server defaults must not affect tools');

echo "Ability policy mapping passed\n";
