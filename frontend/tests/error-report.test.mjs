import assert from "node:assert/strict";
import test from "node:test";
import { apiFailure, javascriptFailure } from "../src/error-report.mjs";

test("API failures retain troubleshooting details without URL secrets", () => {
  const failure = apiFailure(
    "/aam/v2/users?nonce=private&user_id=42",
    "post",
    { status: 403, code: "rest_forbidden", message: "Access denied" },
  );
  assert.deepEqual(failure, {
    type: "http",
    method: "POST",
    endpoint: "/aam/v2/users",
    status: 403,
    code: "rest_forbidden",
    message: "Access denied",
  });
  assert.equal(apiFailure("/aam/v2/users", "GET", { name: "AbortError" }), null);
});

test("JavaScript failures keep stack locations but strip URL queries", () => {
  const failure = javascriptFailure({
    name: "TypeError",
    message: "Failed at https://example.test/admin.php?token=secret",
    stack: "TypeError: failed\n at https://example.test/app.js?ver=8:12:30",
  });
  assert.equal(failure.message, "Failed at https://example.test/admin.php");
  assert.match(failure.stack, /app\.js:12:30/);
  assert.doesNotMatch(JSON.stringify(failure), /secret|ver=8/);
});
