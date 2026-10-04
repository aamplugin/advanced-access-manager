import test from "node:test";
import assert from "node:assert/strict";
import {
  accessLevelStorageKey,
  readAccessLevel,
  rememberAccessLevel,
  forgetAccessLevel,
  shouldRestoreAccessLevel,
} from "../src/access-level-memory.mjs";

function storage() {
  const values = new Map();
  return {
    getItem: (key) => values.get(key) ?? null,
    setItem: (key, value) => values.set(key, value),
    removeItem: (key) => values.delete(key),
  };
}

test("remembers a validated selection for one site and admin", () => {
  const store = storage();
  const key = accessLevelStorageKey({ blogId: 2, viewerId: 8 });
  rememberAccessLevel(store, key, { type: "user", id: 42, name: "Editor" });
  assert.deepEqual(readAccessLevel(store, key), { type: "user", id: 42 });
  assert.equal(readAccessLevel(store, accessLevelStorageKey({ blogId: 2, viewerId: 9 })), null);
  assert.equal(readAccessLevel(store, accessLevelStorageKey({ blogId: 3, viewerId: 8 })), null);
  forgetAccessLevel(store, key);
  assert.equal(readAccessLevel(store, key), null);
});

test("an explicit deep link wins over the remembered selection", () => {
  const saved = { type: "role", id: "editor" };
  assert.equal(shouldRestoreAccessLevel("?page=aam", saved, { type: "default", id: null }), true);
  assert.equal(shouldRestoreAccessLevel("?page=aam&aam_level=user&aam_subject=7", saved, { type: "user", id: 7 }), false);
  assert.equal(shouldRestoreAccessLevel("?page=aam", saved, { type: "role", id: "editor" }), false);
});

test("invalid or inaccessible storage safely falls back to the server selection", () => {
  const key = accessLevelStorageKey({ blogId: 1, viewerId: 2 });
  const store = storage();
  store.setItem(key, '{"type":"user","id":0}');
  assert.equal(readAccessLevel(store, key), null);
  store.setItem(key, "invalid json");
  assert.equal(readAccessLevel(store, key), null);
  const unavailable = { getItem() { throw Error("blocked"); }, setItem() { throw Error("blocked"); } };
  assert.equal(readAccessLevel(unavailable, key), null);
  assert.doesNotThrow(() => rememberAccessLevel(unavailable, key, { type: "visitor", id: null }));
});
