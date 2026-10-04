import test from "node:test";
import assert from "node:assert/strict";
import {
  contentItemsPath,
  contentResourcePath,
} from "../src/content-query.mjs";

test("post type browsing requests posts for the selected type", () => {
  assert.equal(
    contentItemsPath({ kind: "posts", postType: "page" }, 1, "about us"),
    "/posts?post_type=page&per_page=20&offset=20&search=about+us",
  );
});

test("taxonomy browsing is independent of the selected post type", () => {
  assert.equal(
    contentItemsPath({ kind: "terms", taxonomy: "category" }, 0, ""),
    "/terms?taxonomy=category&per_page=20&offset=0&search=",
  );
  assert.equal(
    contentItemsPath(
      { kind: "terms", taxonomy: "category", postType: "post" },
      0,
      "",
    ),
    "/terms?taxonomy=category&post_type=post&per_page=20&offset=0&search=",
  );
});

test("term access routes preserve taxonomy and optional post type scope", () => {
  const term = { kind: "term", row: { id: 42, taxonomy: "category" } };
  assert.equal(contentResourcePath(term), "/content/term/42?taxonomy=category");
  assert.equal(
    contentResourcePath({ ...term, postType: "post" }),
    "/content/term/42?taxonomy=category&post_type=post",
  );
});

test("post type and taxonomy access routes identify their resource", () => {
  assert.equal(
    contentResourcePath({ kind: "post_type", row: { slug: "page" } }),
    "/content/post_type/page",
  );
  assert.equal(
    contentResourcePath({ kind: "taxonomy", row: { slug: "post_tag" } }),
    "/content/taxonomy/post_tag",
  );
});
