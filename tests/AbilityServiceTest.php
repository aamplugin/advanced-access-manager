<?php

/**
 * Standalone security contract test: php tests/AbilityServiceTest.php
 * No WordPress installation or database is required.
 */

function apply_filters($name, $value, ...$args)
{
    foreach ($GLOBALS['test_filters'][$name] ?? [] as $callback) {
        $value = $callback($value, ...$args);
    }

    return $value;
}
function __($text, $domain = null) { return $text; }
function is_wp_error($value) { return $value instanceof WP_Error; }

class WP_Error
{
    public $code;
    public function __construct($code) { $this->code = $code; }
}

class AAM_Framework_Type_Resource
{
    const ABILITIES = 'abilities';
    const MCP_SERVERS = 'mcp_server';
    const MCP_TOOLS = 'mcp_tools';
}

class TestResource
{
    public $rules = [];

    public function set_permission($key, $name, $effect)
    {
        $this->rules[$key] = [ $name => [ 'effect' => $effect ] ];
        return true;
    }

    public function get_permission($key, $name)
    {
        return isset($this->rules[$key][$name]) ? $this->rules[$key][$name] : null;
    }

    public function remove_permission($key, $name)
    {
        unset($this->rules[$key][$name]);
        return true;
    }

    public function reset() { $this->rules = []; return true; }
    public function is_customized($key = null)
    {
        return is_null($key) ? !empty($this->rules) : !empty($this->rules[$key]);
    }
}

class TestAccessLevel
{
    public $resources = [];
    public function get_resource($type)
    {
        if (!isset($this->resources[$type])) {
            $this->resources[$type] = new TestResource();
        }
        return $this->resources[$type];
    }
}

class TestGateway
{
    public $services = [];
    public function abilities() { return $this->services['abilities']; }
    public function mcp_servers() { return $this->services['servers']; }
    public function mcp_tools() { return $this->services['tools']; }
}

class AAM
{
    public static $gateway;
    public static function api() { return self::$gateway; }
}

require __DIR__ . '/../application/Framework/Service/BaseTrait.php';
require __DIR__ . '/../application/Framework/Service/AccessRuleTrait.php';
require __DIR__ . '/../application/Framework/Service/Ability.php';
require __DIR__ . '/../application/Framework/Service/MCPServer.php';
require __DIR__ . '/../application/Framework/Service/MCPTool.php';
require __DIR__ . '/../application/Service/BaseTrait.php';
require __DIR__ . '/../application/Service/Ability.php';
require __DIR__ . '/../aam-complete-package-v8/application/Framework/Resource/MCPServer.php';

function verify($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$access_level = new TestAccessLevel();
AAM::$gateway = new TestGateway();
foreach ([
    'abilities' => 'AAM_Framework_Service_Abilities',
    'servers' => 'AAM_Framework_Service_McpServers',
    'tools' => 'AAM_Framework_Service_McpTools'
] as $kind => $class) {
    $framework = new ReflectionClass($class);
    $rules = $framework->newInstanceWithoutConstructor();
    $constructor = $framework->getConstructor();
    $constructor->setAccessible(true);
    $constructor->invoke($rules, $access_level, []);
    AAM::$gateway->services[$kind] = $rules;
}
$ability_rules = AAM::$gateway->abilities();
$server_rules = AAM::$gateway->mcp_servers();
$tool_rules = AAM::$gateway->mcp_tools();

$service = (new ReflectionClass('AAM_Service_Ability'))->newInstanceWithoutConstructor();
$args = [ 'permission_callback' => function() { return true; } ];
$wrapped = $service->filter_registration($args, 'example/delete-post')['permission_callback'];

verify($wrapped() === true, 'Unrestricted ability must retain native permission');
$ability_rules->set_access('example/delete-post', 'deny');
verify($wrapped() instanceof WP_Error, 'AAM denial must block an allowed ability');
$ability_rules->set_access('example/delete-post', 'allow');
verify($wrapped() === true, 'AAM allow must remove its own denial');
$server_rules->set_access('vendor/site:admin', 'deny');
verify($server_rules->is_denied('vendor/site:admin'),
    'Namespaced custom MCP server IDs must be supported');
verify(!$ability_rules->is_customized('example/read-post')
    && !$tool_rules->is_customized('example-delete-post', 'example-server'),
    'MCP server rules must stay in their own resource');
$server_rules->reset('vendor/site:admin');

$native_denial = $service->filter_registration([
    'permission_callback' => function() { return false; }
], 'example/delete-post')['permission_callback'];
verify($native_denial() === false, 'AAM allow must not override native denial');
$input_check = $service->filter_registration([
    'input_schema' => [ 'type' => 'object' ],
    'permission_callback' => function($input) { return $input['post_id'] === 42; }
], 'example/read-post')['permission_callback'];
verify($input_check([ 'post_id' => 42 ]) === true,
    'Ability input must reach its native permission callback');

$server = new class {
    public function get_server_id() { return 'example-server'; }
};
$tool = new class {
    public function getName() { return 'example-delete-post'; }
};

$tool_rules->set_access('example-delete-post', 'deny', 'example-server');
verify(!$server_rules->is_customized('example-server'),
    'MCP tool rules must stay separate from server rules');
verify($service->check_tool([], 'example-delete-post', $tool, $server) instanceof WP_Error,
    'Denied MCP tool must not execute');
verify($service->filter_tools([ $tool ], $server) === [],
    'Denied MCP tool must not appear in tool list');

$tool_rules->reset('example-delete-post', 'example-server');
$service->index_tool_ability('example-delete-post', new class {
    public function get_name() { return 'example/delete-post'; }
});
$ability_rules->set_access('example/delete-post', 'deny');
verify($service->filter_tools([ $tool ], $server) === [],
    'Direct ability-backed tools must be hidden when the ability is denied');
$ability_rules->reset('example/delete-post');
$server_rules->set_access('example-server', 'deny');
verify($service->filter_tools([ $tool ], $server) === [],
    'Denied server must not list tools');
verify($service->check_server_operation([], '', null, $server) instanceof WP_Error,
    'Denied server must not serve resources or prompts');

$server_rules->reset('example-server');
$ability_rules->set_access('example/delete-post', 'deny');
$result = $service->filter_tool_result([
    'abilities' => [ [ 'name' => 'example/delete-post' ], [ 'name' => 'example/read-post' ] ]
], [], 'mcp-adapter-discover-abilities', $tool, $server);
verify(count($result['abilities']) === 1
    && $result['abilities'][0]['name'] === 'example/read-post',
    'Denied ability must not appear in MCP discovery');

$GLOBALS['test_filters']['aam_mcp_server_access_effect_filter'][] = [
    \AAM\AddOn\CompletePackage\Framework\Resource\MCPServer::bootstrap(),
    'resolve_effect'
];
$server_resource = $access_level->get_resource(AAM_Framework_Type_Resource::MCP_SERVER);
$server_resource->set_permission('server:*', 'access', 'deny');
verify($server_rules->is_denied('unconfigured-server'),
    'Premium server default must reach the framework service decision');
$server_rules->set_access('unconfigured-server', 'allow');
verify(!$server_rules->is_denied('unconfigured-server'),
    'A server allowance must override the premium default');

echo "Ability access security contract passed\n";
