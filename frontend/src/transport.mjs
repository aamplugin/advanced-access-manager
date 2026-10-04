/** Immutable subject paths and a queue that advances only on its own promise. */
export function contextPath(endpoint, subject) {
  const params = new URLSearchParams({ access_level: subject.type });
  if (subject.type === "role") params.set("role_id", String(subject.id));
  if (subject.type === "user") params.set("user_id", String(subject.id));
  return (
    "/aam/v2" +
    endpoint +
    (endpoint.includes("?") ? "&" : "?") +
    params.toString().replace(/\+/g, "%20")
  );
}
export function preloadPath(subject, screen) {
  const params = new URLSearchParams({ access_level: subject.type, screen });
  if (subject.type === "role") params.set("role_id", String(subject.id));
  if (subject.type === "user") params.set("user_id", String(subject.id));
  return "/aam/v2/preload?" + params.toString().replace(/\+/g, "%20");
}
export function createMutationQueue(send) {
  let tail = Promise.resolve();
  return function mutate(path, method, data) {
    // Copy before enqueuing: callers may subsequently edit forms or navigate.
    const request = {
      path,
      method,
      ...(data === undefined ? {} : { data: structuredClone(data) }),
    };
    const result = tail.then(() => send(request));
    tail = result.catch(() => {});
    return result;
  };
}
export function createGenerationGuard() {
  let generation = 0;
  return {
    next: () => ++generation,
    isCurrent: (token) => token === generation,
  };
}
