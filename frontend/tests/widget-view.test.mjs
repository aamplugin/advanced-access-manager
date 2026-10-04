import test from "node:test";
import assert from "node:assert/strict";
import { widgetViewForSubject } from "../src/widget-view.mjs";

test("visitor widget view only requests and sets defaults for website widgets", () => {
  assert.deepEqual(widgetViewForSubject("visitor"), {
    endpoint: "/widgets?area=frontend",
    modeKey: "*|frontend",
    modeArea: "frontend",
    modeScopes: [],
  });
  assert.deepEqual(widgetViewForSubject("role"), {
    endpoint: "/widgets",
    modeKey: "*",
    modeArea: null,
    modeScopes: ["frontend", "dashboard"],
  });
});
