import test from "node:test";
import assert from "node:assert/strict";
import {
  shouldRefreshResource,
  subscribeResourceRefresh,
  refreshAfterMutation,
} from "../src/resource-refresh.mjs";

const subject = "access_level=role&role_id=editor";

test("a post permission save refreshes only the current post list", () => {
  const mutation = `/aam/v2/post/42?${subject}`;
  assert.equal(
    shouldRefreshResource(
      `/aam/v2/posts?post_type=post&offset=20&${subject}`,
      mutation,
    ),
    true,
  );
  assert.equal(
    shouldRefreshResource(`/aam/v2/post_types?${subject}`, mutation),
    false,
  );
  assert.equal(
    shouldRefreshResource(`/aam/v2/taxonomies?${subject}`, mutation),
    false,
  );
  assert.equal(
    shouldRefreshResource(
      "/aam/v2/posts?post_type=post&access_level=user&user_id=7",
      mutation,
    ),
    false,
  );
});

test("post type and term saves refresh their own lists", () => {
  assert.equal(
    shouldRefreshResource(
      `/aam/v2/post_types?${subject}`,
      `/aam/v2/content/post_type/post?${subject}`,
    ),
    true,
  );
  assert.equal(
    shouldRefreshResource(
      `/aam/v2/terms?taxonomy=category&${subject}`,
      `/aam/v2/content/term/5?taxonomy=category&${subject}`,
    ),
    true,
  );
});

test("an MCP rule change refreshes its own resource list", () => {
  assert.equal(
    shouldRefreshResource(
      `/aam/v2/mcp-servers?${subject}`,
      `/aam/v2/mcp-server?resource=example&${subject}`,
    ),
    true,
  );
  assert.equal(
    shouldRefreshResource(
      `/aam/v2/mcp-tools?${subject}`,
      `/aam/v2/mcp-server/primitive/tool?resource=read&server_id=example&${subject}`,
    ),
    true,
  );
  assert.equal(
    shouldRefreshResource(
      `/aam/v2/mcp-resources?${subject}`,
      `/aam/v2/mcp-server/primitive/resource/mode?server_id=example&${subject}`,
    ),
    true,
  );
  assert.equal(
    shouldRefreshResource(
      `/aam/v2/abilities?${subject}`,
      `/aam/v2/ability?resource=example%2Fread&${subject}`,
    ),
    true,
  );
});

test("mutation notifications reach active subscribers", () => {
  const received = [];
  const unsubscribe = subscribeResourceRefresh((path) => received.push(path));
  refreshAfterMutation("/aam/v2/role/editor");
  unsubscribe();
  refreshAfterMutation("/aam/v2/role/author");
  assert.deepEqual(received, ["/aam/v2/role/editor"]);
});
