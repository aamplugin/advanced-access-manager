import test from "node:test";
import assert from "node:assert/strict";
import { visibleContentControls } from "../src/content-control-visibility.mjs";

test("visitors see content and term controls that apply to them", () => {
  const post = ["list", "read", "comment", "create", "edit", "publish", "delete"];
  const term = ["list", "browse", "create", "edit", "delete", "assign"];

  assert.deepEqual(
    visibleContentControls(post, "visitor", (key) => key),
    ["list", "read", "comment"],
  );
  assert.deepEqual(
    visibleContentControls(term, "visitor", (key) => key),
    ["list", "browse"],
  );
  assert.deepEqual(visibleContentControls(term, "role", (key) => key), term);
});
