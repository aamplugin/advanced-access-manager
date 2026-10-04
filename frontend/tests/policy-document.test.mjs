import test from "node:test";
import assert from "node:assert/strict";
import {
  validatePolicyJson,
  formatPolicyJson,
} from "../src/policy-document.mjs";

test("valid policy JSON reports statements and formats without changing rules", () => {
  const source =
    '{"Statement":{"Effect":"deny","Resource":"Post:page:members-only","Action":"Read"}}';
  const result = validatePolicyJson(source);
  assert.equal(result.valid, true);
  assert.equal(result.count, 1);
  assert.equal(result.incomplete, false);
  assert.deepEqual(JSON.parse(formatPolicyJson(source)), JSON.parse(source));
});

test("malformed JSON identifies an error location and cannot be formatted", () => {
  const source = '{\n  "Statement": {},\n}';
  const result = validatePolicyJson(source);
  assert.equal(result.valid, false);
  assert.equal(result.line, 3);
  assert.throws(() => formatPolicyJson(source));
});

test("the policy document must be an object and an empty resource gets guidance", () => {
  assert.equal(validatePolicyJson("[]").valid, false);
  assert.equal(
    validatePolicyJson('{"Statement":[{"Effect":"deny","Resource":[]}]}')
      .incomplete,
    true,
  );
});

test("ability and MCP resources warn about unregistered names and unavailable wildcards", () => {
  const known = {
    abilities: ["acme/read"],
    servers: ["acme:operations"],
    tools: [{ server: "acme:operations", name: "read-order" }],
    wildcards: false,
  };
  const source = JSON.stringify({
    Statement: [
      { Effect: "allow", Resource: "Ability:acme/read" },
      { Effect: "allow", Resource: "MCPServer:acme%3Aoperations" },
      { Effect: "allow", Resource: "MCPTool:acme%3Aoperations:read-order" },
      { Effect: "allow", Resource: "Ability:acme/missing" },
      { Effect: "deny", Resource: "Ability:*" },
    ],
  });

  assert.deepEqual(validatePolicyJson(source, known).resourceWarnings, [
    "Ability:acme/missing",
    "Ability:*",
  ]);
  assert.deepEqual(
    validatePolicyJson(source, { ...known, wildcards: true }).resourceWarnings,
    ["Ability:acme/missing"],
  );
});

test("ability wildcard patterns are recognized only with premium support", () => {
  const resources = [
    "Ability:core/get-site-info",
    "Ability:core/*",
    "Ability:*/update-record",
    "Ability:core/update-*",
    "Ability:co*/update-record",
    "Ability:*/*",
    "Ability:*",
  ];
  const source = JSON.stringify({
    Statement: resources.map((Resource) => ({ Resource, Effect: "deny" })),
  });
  const known = { abilities: ["core/get-site-info"], wildcards: false };

  assert.deepEqual(
    validatePolicyJson(source, known).resourceWarnings,
    resources.slice(1),
  );
  assert.deepEqual(
    validatePolicyJson(source, { ...known, wildcards: true }).resourceWarnings,
    [],
  );
});

test("MCP server patterns and global resource require premium support", () => {
  const resources = [
    "MCPServer:mcp-adapter-default-server",
    "MCPServer:mcp-adapter-default-*",
    "*",
  ];
  const source = JSON.stringify({
    Statement: resources.map((Resource) => ({ Resource, Effect: "deny" })),
  });
  const known = {
    servers: ["mcp-adapter-default-server"],
    wildcards: false,
  };

  assert.deepEqual(
    validatePolicyJson(source, known).resourceWarnings,
    resources.slice(1),
  );
  assert.deepEqual(
    validatePolicyJson(source, { ...known, wildcards: true }).resourceWarnings,
    [],
  );
});

test("MCP tool policies require both IDs and premium for either wildcard", () => {
  const known = {
    tools: [
      { server: "server-a", name: "tool-a" },
      { server: "vendor/site", name: "read:item" },
    ],
    wildcards: false,
  };
  const statements = [
    { Resource: "MCPTool:server-a/tool-a" },
    { Resource: "MCPTool:tool-a", Server: "server-a" },
    { Resource: "MCPTool:vendor%2Fsite/read%3Aitem" },
    { Resource: "MCPTool:*/tool-a" },
    { Resource: "MCPTool:server-a/*" },
    { Resource: "MCPTool:*/*" },
    { Resource: "MCPTool:tool-*", Server: "server-a" },
    { Resource: "MCPTool:tool-a", Server: "serv*" },
    { Resource: "MCPTool:tool-a" },
    { Resource: "MCPTool:server-a/" },
  ];
  const source = JSON.stringify({ Statement: statements });

  assert.deepEqual(validatePolicyJson(source, known).resourceWarnings, [
    "MCPTool:*/tool-a",
    "MCPTool:server-a/*",
    "MCPTool:*/*",
    "MCPTool:tool-*",
    "MCPTool:tool-a",
    "MCPTool:server-a/",
  ]);
  assert.deepEqual(
    validatePolicyJson(source, { ...known, wildcards: true }).resourceWarnings,
    statements.slice(8).map((item) => item.Resource),
  );
});

test("unsupported ability actions and malformed tool names get an editor warning", () => {
  const source = JSON.stringify({
    Statement: [
      { Resource: "Ability:acme/read", Action: "Execute" },
      { Resource: "MCPTool:missing-server" },
    ],
  });

  assert.deepEqual(validatePolicyJson(source).resourceWarnings, [
    "Ability:acme/read",
    "MCPTool:missing-server",
  ]);
});

test("MCP prompt and resource policies require server IDs and recognize URI resources", () => {
  const known = {
    prompts: [{ server: "server-a", name: "summarize" }],
    resources: [{ server: "server-a", name: "https://example.com/item" }],
    wildcards: false,
  };
  const source = JSON.stringify({ Statement: [
    { Resource: "MCPPrompt:server-a/summarize" },
    { Resource: "MCPResource:https%3A%2F%2Fexample.com%2Fitem", Server: "server-a" },
    { Resource: "MCPPrompt:summarize" },
    { Resource: "MCPResource:https%3A%2F%2Fexample.com%2F*", Server: "server-a" },
  ] });
  assert.deepEqual(validatePolicyJson(source, known).resourceWarnings, [
    "MCPPrompt:summarize",
    "MCPResource:https%3A%2F%2Fexample.com%2F*",
  ]);
  assert.deepEqual(validatePolicyJson(source, { ...known, wildcards: true }).resourceWarnings, [
    "MCPPrompt:summarize",
  ]);
});
