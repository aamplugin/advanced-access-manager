import test from "node:test";
import assert from "node:assert/strict";
import {
  contextPath,
  preloadPath,
  createMutationQueue,
  createGenerationGuard,
} from "../src/transport.mjs";
test("encodes role IDs and keeps query arguments", () => {
  assert.equal(
    contextPath("/capabilities?fields=is_granted", {
      type: "role",
      id: "a&b + c",
    }),
    "/aam/v2/capabilities?fields=is_granted&access_level=role&role_id=a%26b%20%2B%20c",
  );
});
test("visitor/default requests contain no stale subject ID", () => {
  assert.equal(
    contextPath("/urls", { type: "visitor", id: 42 }),
    "/aam/v2/urls?access_level=visitor",
  );
  assert.equal(
    contextPath("/urls", { type: "default", id: 42 }),
    "/aam/v2/urls?access_level=default",
  );
});
test("workspace preload identifies the selected role, user, and screen", () => {
  assert.equal(
    preloadPath({ type: "role", id: "a&b + c" }, "admin_menu"),
    "/aam/v2/preload?access_level=role&screen=admin_menu&role_id=a%26b%20%2B%20c",
  );
  assert.equal(
    preloadPath({ type: "user", id: 42 }, "jwt"),
    "/aam/v2/preload?access_level=user&screen=jwt&user_id=42",
  );
  assert.equal(
    preloadPath({ type: "visitor", id: 42 }, "url"),
    "/aam/v2/preload?access_level=visitor&screen=url",
  );
});
test("mutation target and payload are captured before queue execution", async () => {
  let release;
  const held = new Promise((r) => (release = r));
  const seen = [];
  const queue = createMutationQueue(async (req) => {
    seen.push(req);
    if (seen.length === 1) await held;
  });
  const subject = { type: "role", id: "editor" };
  const payload = { effect: "deny" };
  const first = queue("/first", "POST", {});
  const second = queue(
    contextPath("/backend-menu/example", subject),
    "PATCH",
    payload,
  );
  subject.type = "user";
  subject.id = 9;
  payload.effect = "allow";
  await Promise.resolve();
  assert.equal(seen.length, 1);
  release();
  await Promise.all([first, second]);
  assert.match(seen[1].path, /role_id=editor/);
  assert.equal(seen[1].data.effect, "deny");
});
test("rejected mutations do not poison subsequent queue operations", async () => {
  const seen = [];
  const queue = createMutationQueue(async (req) => {
    seen.push(req.path);
    if (req.path === "/bad") throw Error("failure");
    return true;
  });
  await assert.rejects(queue("/bad", "POST", {}));
  assert.equal(await queue("/good", "POST", {}), true);
  assert.deepEqual(seen, ["/bad", "/good"]);
});
test("older navigation responses are identifiable as stale", () => {
  const guard = createGenerationGuard();
  const old = guard.next();
  const current = guard.next();
  assert.equal(guard.isCurrent(old), false);
  assert.equal(guard.isCurrent(current), true);
});
