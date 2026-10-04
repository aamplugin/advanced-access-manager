import test from "node:test";
import assert from "node:assert/strict";
import { changedPermissions } from "../src/content-permissions.mjs";

test("a content save batches only changed permissions with their settings", () => {
  assert.deepEqual(
    changedPermissions(
      {
        read: {
          effect: "deny",
          restriction_type: "redirect",
          redirect: { type: "page_redirect", page_id: 42 },
        },
        edit: { effect: "allow" },
        delete: { effect: "deny" },
      },
      { read: true, delete: true },
    ),
    [
      {
        permission: "read",
        effect: "deny",
        restriction_type: "redirect",
        redirect: { type: "page_redirect", page_id: 42 },
      },
      { permission: "delete", effect: "deny" },
    ],
  );
});
