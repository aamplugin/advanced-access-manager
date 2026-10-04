import test from "node:test";
import assert from "node:assert/strict";
import { createToastTimer } from "../src/toast-timer.mjs";

function fakeClock() {
  let now = 0;
  let nextId = 0;
  const tasks = new Map();
  return {
    now: () => now,
    set(callback, delay) {
      const id = ++nextId;
      tasks.set(id, { callback, at: now + delay });
      return id;
    },
    clear(id) {
      tasks.delete(id);
    },
    advance(ms) {
      const end = now + ms;
      while (true) {
        const due = [...tasks.entries()]
          .filter(([, task]) => task.at <= end)
          .sort((a, b) => a[1].at - b[1].at)[0];
        if (!due) break;
        now = due[1].at;
        tasks.delete(due[0]);
        due[1].callback();
      }
      now = end;
    },
  };
}

test("toast waits for the remaining time after hover ends", () => {
  const clock = fakeClock();
  let closed = 0;
  const timer = createToastTimer(() => closed++, 5000, clock);
  clock.advance(2000);
  timer.pause("hover");
  clock.advance(10000);
  assert.equal(closed, 0);
  timer.resume("hover");
  clock.advance(2999);
  assert.equal(closed, 0);
  clock.advance(1);
  assert.equal(closed, 1);
});

test("hover and focus both hold the toast until each is released", () => {
  const clock = fakeClock();
  let closed = 0;
  const timer = createToastTimer(() => closed++, 1000, clock);
  timer.pause("hover");
  timer.pause("focus");
  timer.resume("hover");
  clock.advance(2000);
  assert.equal(closed, 0);
  timer.resume("focus");
  clock.advance(1000);
  assert.equal(closed, 1);
});
