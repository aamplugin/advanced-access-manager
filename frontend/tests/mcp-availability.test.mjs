import test from "node:test";
import assert from "node:assert/strict";
import { mcpUnavailable } from "../src/mcp-availability.mjs";

const collection = (data, loaded = true, error = null) => ({
  data,
  loaded,
  error,
});

test("MCP is unavailable without the adapter or a registered server", () => {
  const empty = collection([]);
  assert.equal(mcpUnavailable(false, empty, empty, empty, empty), true);
  assert.equal(mcpUnavailable(true, empty, empty, empty, empty), true);
});

test("MCP is unavailable when registered servers have no primitives", () => {
  const server = collection([{ id: "site" }]);
  const empty = collection([]);
  assert.equal(mcpUnavailable(true, server, empty, empty, empty), true);
  assert.equal(
    mcpUnavailable(true, server, collection([], false), empty, empty),
    false,
  );
  assert.equal(
    mcpUnavailable(
      true,
      server,
      collection([], true, Error("failed")),
      empty,
      empty,
    ),
    false,
  );
  assert.equal(
    mcpUnavailable(true, server, collection([{ name: "read" }]), empty, empty),
    false,
  );
});
