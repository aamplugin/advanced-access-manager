import test from "node:test";
import assert from "node:assert/strict";
import { indexScreens, screenIndexUrl } from "../src/screen-index.mjs";

const admin = "https://example.test/site/wp-admin/";

test("metabox indexing keeps the editor context and adds the init flag", () => {
  assert.equal(
    screenIndexUrl(
      "https://example.test/site/wp-admin/post.php?post=42&action=edit#editor",
      admin,
      "metabox",
    ),
    "https://example.test/site/wp-admin/post.php?post=42&action=edit&init=metabox",
  );
  assert.throws(() =>
    screenIndexUrl("https://other.test/wp-admin/post.php", admin, "metabox"),
  );
  assert.throws(() =>
    screenIndexUrl("https://example.test/site/public/page", admin, "metabox"),
  );
});

test("refresh visits each screen and reports failures without stopping", async () => {
  const visited = [];
  const progress = [];
  const result = await indexScreens(
    ["post-new.php?post_type=post", "post-new.php?post_type=page"],
    admin,
    "metabox",
    (done, total) => progress.push([done, total]),
    async (url, options) => {
      visited.push([url, options.credentials]);
      return { ok: visited.length === 2, url };
    },
  );
  assert.equal(visited.length, 2);
  assert.match(visited[0][0], /post_type=post&init=metabox/);
  assert.deepEqual(
    visited.map((item) => item[1]),
    ["same-origin", "same-origin"],
  );
  assert.deepEqual(progress, [
    [1, 2],
    [2, 2],
  ]);
  assert.equal(result.failures.length, 1);
});
