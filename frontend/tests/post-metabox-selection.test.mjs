import test from "node:test";
import assert from "node:assert/strict";
import { availableEditorAccessLevel } from "../src/editor-access-selection.mjs";

const boot = {
  levels: { role: true, user: true, visitor: true, default: false },
  roles: [{ value: "editor", label: "Editor" }],
};

test("post access restores a permitted role, user, or visitor", () => {
  assert.deepEqual(availableEditorAccessLevel(boot, { type: "role", id: "editor" }), {
    type: "role",
    id: "editor",
  });
  assert.deepEqual(availableEditorAccessLevel(boot, { type: "user", id: 42 }), {
    type: "user",
    id: 42,
  });
  assert.deepEqual(availableEditorAccessLevel(boot, { type: "visitor", id: null }), {
    type: "visitor",
    id: null,
  });
});

test("post access skips unavailable or removed levels", () => {
  assert.equal(availableEditorAccessLevel(boot, { type: "role", id: "old_role" }), null);
  assert.equal(availableEditorAccessLevel(boot, { type: "default", id: null }), null);
  assert.equal(availableEditorAccessLevel(boot, { type: "user", id: 0 }), null);
});
