import test from "node:test";
import assert from "node:assert/strict";
import { copyText } from "../src/clipboard.mjs";

test("copyText copies a credential with the Clipboard API", async () => {
  let copied = "";
  await copyText("signed-url", {
    writeText: async (value) => {
      copied = value;
    },
  });
  assert.equal(copied, "signed-url");
});

test("copyText falls back to selection when clipboard access is unavailable", async () => {
  const state = { selected: false, removed: false, copied: false };
  const document = {
    body: { append: () => {} },
    createElement: () => ({
      style: {},
      select: () => {
        state.selected = true;
      },
      remove: () => {
        state.removed = true;
      },
    }),
    execCommand: (command) => {
      state.copied = command === "copy";
      return true;
    },
  };
  await copyText("jwt-token", null, document);
  assert.deepEqual(state, { selected: true, removed: true, copied: true });
  await assert.rejects(copyText("", null, document), /Nothing to copy/);
});
