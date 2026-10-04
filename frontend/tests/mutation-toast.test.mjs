import test from "node:test";
import assert from "node:assert/strict";
import { mutationToast } from "../src/mutation-toast.mjs";

const subject = { name: "Editor" };
const describe = (path, method, data) =>
  mutationToast(path, method, data, subject);

test("an Ability rule toast identifies only the changed ability and action", () => {
  const path =
    "/aam/v2/ability?resource=core%2Fread&access_level=role&role_id=editor";
  assert.deepEqual(describe(path, "PATCH", { effect: "deny" }), {
    template: "%1$s %2$s restricted for %3$s.",
    args: ["Ability", "core/read", "Editor"],
  });
  assert.deepEqual(describe(path, "DELETE"), {
    template: "%1$s %2$s now inherits access for %3$s.",
    args: ["Ability", "core/read", "Editor"],
  });
});

test("MCP tool toasts include both required resource IDs", () => {
  assert.deepEqual(
    describe(
      "/aam/v2/mcp-server/primitive/tool?resource=read&server_id=site",
      "PATCH",
      {
        effect: "allow",
      },
    ),
    {
      template: "%1$s %2$s allowed for %3$s.",
      args: ["MCP tool", "read (site)", "Editor"],
    },
  );
});

test("settings and user toasts describe the concrete change", () => {
  assert.deepEqual(
    describe("/aam/v2/config/service.geo_lookup.enabled", "POST", {
      value: true,
    }),
    {
      template: "%1$s service %2$s.",
      args: ["geo lookup", "enabled"],
    },
  );
  assert.deepEqual(
    describe("/aam/v2/user/12", "PATCH", { status: "inactive" }),
    {
      template: "User #%1$s %2$s.",
      args: ["12", "deactivated"],
    },
  );
});

test("capability deletion toast states whether all roles were affected", () => {
  const path = "/aam/v2/capability/edit_posts";
  assert.deepEqual(describe(path, "DELETE", { globally: true }), {
    template: "Capability %1$s removed from all registered roles.",
    args: ["edit_posts"],
  });
  assert.deepEqual(
    mutationToast(
      path,
      "DELETE",
      { globally: true },
      {
        type: "user",
        name: "Alice",
      },
    ),
    {
      template: "Capability %1$s removed from all roles and %2$s.",
      args: ["edit_posts", "Alice"],
    },
  );
});

test("content and reset toasts name the affected service", () => {
  assert.deepEqual(
    describe("/aam/v2/content/term/5", "PATCH", {
      permissions: [{ permission: "read", effect: "deny" }],
    }),
    {
      template: "%1$s permissions saved (%2$s) for %3$s.",
      args: ["Term", "read deny", "Editor"],
    },
  );
  assert.deepEqual(describe("/aam/v2/mcp-servers", "DELETE"), {
    template: "%1$s rules reset for %2$s.",
    args: ["MCP server", "Editor"],
  });
  assert.deepEqual(describe("/aam/v2/jwts", "DELETE"), {
    template: "All JWT tokens revoked.",
    args: [],
  });
});

test("MCP primitive mode toast names the server and primitive", () => {
  assert.deepEqual(
    describe(
      "/aam/v2/mcp-server/primitive/prompt/mode?server_id=site",
      "PATCH",
      { effect: "deny" },
    ),
    {
      template: "%1$s on %2$s restricted by default for %3$s.",
      args: ["MCP prompts", "site", "Editor"],
    },
  );
});

test("access denied toasts identify the selected area", () => {
  assert.deepEqual(
    describe("/aam/v2/redirect/access-denied?area=api", "POST"),
    {
      template: "API denial response saved for %s.",
      args: ["Editor"],
    },
  );
  assert.deepEqual(
    describe("/aam/v2/redirect/access-denied?area=frontend", "DELETE"),
    {
      template: "%1$s access denied redirect reset for %2$s.",
      args: ["Frontend", "Editor"],
    },
  );
  assert.deepEqual(
    describe("/aam/v2/redirect/access-denied?area=backend", "POST"),
    {
      template: "%1$s access denied redirect saved for %2$s.",
      args: ["Backend", "Editor"],
    },
  );
});
