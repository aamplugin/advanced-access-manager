import assert from "node:assert/strict";
import test from "node:test";
import {
  apiFailure,
  installErrorHandling,
  isAamPromiseError,
  isAamScript,
  javascriptFailure,
} from "../src/error-report.mjs";

test("API failures retain troubleshooting details without URL secrets", () => {
  const failure = apiFailure("/aam/v2/users?nonce=private&user_id=42", "post", {
    status: 403,
    code: "rest_forbidden",
    message: "Access denied",
  });
  assert.deepEqual(failure, {
    type: "http",
    method: "POST",
    endpoint: "/aam/v2/users",
    status: 403,
    code: "rest_forbidden",
    message: "Access denied",
  });
  assert.equal(
    apiFailure("/aam/v2/users", "GET", { name: "AbortError" }),
    null,
  );
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

test("global errors are attributed to AAM script frames only", () => {
  const script =
    "https://site.test/wp-content/plugins/advanced-access-manager/media/js/react-admin.js?ver=1";
  assert.equal(
    isAamScript(
      "https://site.test/wp-content/plugins/advanced-access-manager/media/js/term-access.js:3:9",
      script,
    ),
    true,
  );
  assert.equal(
    isAamScript("https://site.test/wp-includes/js/wp-util.js", script),
    false,
  );
  assert.equal(
    isAamScript(
      "https://site.test/wp-content/plugins/other/media/js/app.js",
      script,
    ),
    false,
  );
  assert.equal(
    isAamScript(
      "https://other.test/wp-content/plugins/advanced-access-manager/media/js/app.js",
      script,
    ),
    false,
  );
  assert.equal(
    isAamPromiseError(
      {
        stack:
          "Error: failed\n at run (https://site.test/wp-content/plugins/other/app.js:2:3)\n at aam (https://site.test/wp-content/plugins/advanced-access-manager/media/js/react-admin.js:5:6)",
      },
      script,
    ),
    false,
  );
  assert.equal(
    isAamPromiseError(
      {
        stack:
          "Error: failed\n at run (https://site.test/wp-content/plugins/advanced-access-manager/media/js/react-admin.js:5:6)",
      },
      script,
    ),
    true,
  );
  assert.equal(isAamPromiseError({ message: "failed" }, script), false);
});

test("disabled popup still records AAM errors and shows inline recovery", () => {
  const originalWindow = globalThis.window;
  const originalDocument = globalThis.document;
  const originalWp = globalThis.wp;
  const listeners = {};
  const notices = [];
  const root = { after: (node) => notices.push(node) };
  globalThis.window = {
    addEventListener: (name, callback) => {
      listeners[name] = callback;
    },
  };
  globalThis.document = {
    querySelector: () => root,
    getElementById: () => null,
    createElement: (tag) => ({
      tag,
      setAttribute() {},
      addEventListener() {},
      append() {},
    }),
  };
  globalThis.wp = { i18n: { __: (message) => message } };
  try {
    installErrorHandling({
      scriptUrl:
        "https://site.test/wp-content/plugins/advanced-access-manager/media/js/react-admin.js",
      showNotification: false,
    });
    listeners.error({
      filename: "https://site.test/wp-includes/js/wp-util.js",
      message: "core",
    });
    assert.equal(window.__aamFrontendErrors.entries.length, 0);
    listeners.error({
      filename:
        "https://site.test/wp-content/plugins/advanced-access-manager/media/js/react-admin.js",
      message: "aam",
    });
    assert.equal(window.__aamFrontendErrors.entries.length, 1);
    assert.equal(notices.length, 1);
    listeners.unhandledrejection({
      reason: {
        message: "other",
        stack:
          "Error\n at https://site.test/wp-content/plugins/other/app.js:1:2",
      },
    });
    assert.equal(window.__aamFrontendErrors.entries.length, 1);
  } finally {
    globalThis.window = originalWindow;
    globalThis.document = originalDocument;
    globalThis.wp = originalWp;
  }
});
