import test from "node:test";
import assert from "node:assert/strict";
import { registerIniMode } from "../src/ini-mode.mjs";

function highlight(line) {
  let mode;
  registerIniMode({
    defineMode(name, create) {
      assert.equal(name, "aam-ini");
      mode = create();
    },
  });
  const stream = {
    pos: 0,
    sol() {
      return this.pos === 0;
    },
    match(pattern) {
      const match = pattern.exec(line.slice(this.pos));
      if (!match || match.index !== 0) return false;
      this.pos += match[0].length;
      return match;
    },
    eatSpace() {
      return !!this.match(/^\s+/);
    },
    next() {
      return line[this.pos++];
    },
  };
  const result = [];
  const state = mode.startState();
  while (stream.pos < line.length) {
    const start = stream.pos;
    const style = mode.token(stream, state);
    assert.ok(stream.pos > start, "each token must advance the stream");
    if (style) result.push([line.slice(start, stream.pos), style]);
  }
  return result;
}

test("ConfigPress INI mode highlights sections, comments, keys and values", () => {
  assert.deepEqual(highlight("[aam]"), [["[aam]", "header"]]);
  assert.deepEqual(highlight("; note"), [["; note", "comment"]]);
  assert.deepEqual(highlight("core.enabled = true"), [
    ["core.enabled ", "property"],
    ["=", "operator"],
    ["true", "atom"],
  ]);
  assert.deepEqual(highlight("timeout = 30"), [
    ["timeout ", "property"],
    ["=", "operator"],
    ["30", "number"],
  ]);
});
