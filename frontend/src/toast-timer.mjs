export function createToastTimer(
  onExpire,
  duration,
  clock = {
    now: () => Date.now(),
    set: (callback, delay) => setTimeout(callback, delay),
    clear: (id) => clearTimeout(id),
  },
) {
  let remaining = duration;
  let started = 0;
  let timeout = null;
  let cancelled = false;
  const holds = new Set();

  const stop = () => {
    if (timeout !== null) {
      clock.clear(timeout);
      timeout = null;
      remaining = Math.max(0, remaining - (clock.now() - started));
    }
  };
  const start = () => {
    if (cancelled || holds.size || timeout !== null) return;
    started = clock.now();
    timeout = clock.set(() => {
      timeout = null;
      cancelled = true;
      onExpire();
    }, remaining);
  };

  start();
  return {
    pause(reason) {
      holds.add(reason);
      stop();
    },
    resume(reason) {
      holds.delete(reason);
      start();
    },
    cancel() {
      cancelled = true;
      stop();
    },
  };
}
