import test from "node:test";
import assert from "node:assert/strict";
import { userListPath, nextSelectableRole } from "../src/people-query.mjs";

test("user filters travel with pagination and encode role and search values", () => {
  const path = userListPath({
    page: 2,
    search: "Jane Doe",
    role: "content editor",
    status: "inactive",
  });
  const url = new URL(path, "https://aam.invalid");
  assert.equal(url.searchParams.get("offset"), "40");
  assert.equal(url.searchParams.get("search"), "Jane Doe");
  assert.equal(url.searchParams.get("role"), "content editor");
  assert.equal(url.searchParams.get("status"), "inactive");
  assert.equal(
    new URL(userListPath({}), "https://aam.invalid").searchParams.has("status"),
    false,
  );
});

test("deleting the selected role chooses the next manageable role", () => {
  const roles = [
    { slug: "editor", name: "Editor", permissions: ["allow_manage"] },
    { slug: "author", name: "Author", permissions: ["allow_manage"] },
    { slug: "locked", name: "Locked", permissions: [] },
  ];
  assert.deepEqual(nextSelectableRole(roles, "editor"), {
    type: "role",
    id: "author",
    name: "Author",
  });
  assert.equal(nextSelectableRole([roles[0]], "editor"), null);
  assert.deepEqual(
    nextSelectableRole(
      [
        { value: "editor", label: "Editor" },
        { value: "author", label: "Author" },
      ],
      "author",
    ),
    { type: "role", id: "editor", name: "Editor" },
  );
});
