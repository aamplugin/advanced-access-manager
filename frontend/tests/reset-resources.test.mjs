import assert from "node:assert/strict";
import test from "node:test";
import { resetResources } from "../src/reset-resources.mjs";

test("MCP reset clears servers and all primitives in sequence", async () => {
  const requests = [];
  const paths = [
    "/mcp-servers",
    "/mcp-tools",
    "/mcp-resources",
    "/mcp-prompts",
  ];
  const saved = await resetResources(paths, async (path) => {
    requests.push(path);
    return true;
  });

  assert.equal(saved, true);
  assert.deepEqual(requests, paths);
});

test("MCP reset attempts every primitive and reports a partial failure", async () => {
  const requests = [];
  const paths = [
    "/mcp-servers",
    "/mcp-tools",
    "/mcp-resources",
    "/mcp-prompts",
  ];
  const saved = await resetResources(paths, async (path) => {
    requests.push(path);
    return path !== "/mcp-servers";
  });

  assert.equal(saved, false);
  assert.deepEqual(requests, paths);
});
