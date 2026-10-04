import test from "node:test";
import assert from "node:assert/strict";
import { pageSearchPath, needsPageSelection } from "../src/page-picker.mjs";

test("page search uses the WordPress pages endpoint and encodes titles", () => {
  const url = new URL(pageSearchPath(" About & Contact "), "https://site.test");
  assert.equal(url.pathname, "/wp/v2/pages");
  assert.equal(url.searchParams.get("search"), "About & Contact");
  assert.equal(url.searchParams.get("orderby"), "title");
});

test("a page redirect requires a selected page ID", () => {
  assert.equal(needsPageSelection({ type: "page_redirect" }), true);
  assert.equal(
    needsPageSelection({ type: "page_redirect", redirect_page_id: 42 }),
    false,
  );
  assert.equal(needsPageSelection({ type: "url_redirect" }), false);
});
