import test from "node:test";
import assert from "node:assert/strict";
import { eligibleParentRoles, parentRoleCounts } from "../src/role-parent.mjs";

const roles = [
  { slug: "author", name: "Author" },
  { slug: "editor", name: "Editor", parent: { slug: "author" } },
  { slug: "reviewer", name: "Reviewer", parent: { slug: "editor" } },
  { slug: "subscriber", name: "Subscriber" },
];

test("parent choices exclude the role and its descendants", () => {
  assert.deepEqual(
    eligibleParentRoles(roles, "editor").map((role) => role.slug),
    ["author", "subscriber"],
  );
  assert.equal(eligibleParentRoles(roles, null).length, roles.length);
});

test("parent badges count direct children", () => {
  assert.deepEqual(
    [...parentRoleCounts(roles)],
    [
      ["author", 1],
      ["editor", 1],
    ],
  );
});
