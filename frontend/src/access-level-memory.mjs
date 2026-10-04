const PREFIX = "aam:last-access-level";

export function accessLevelStorageKey(boot) {
  const blogId = Number(boot?.blogId);
  const viewerId = Number(boot?.viewerId);
  if (!Number.isSafeInteger(blogId) || blogId < 1 ||
      !Number.isSafeInteger(viewerId) || viewerId < 1) return null;
  return `${PREFIX}:${blogId}:${viewerId}`;
}

export function normalizeAccessLevel(subject) {
  if (!subject || typeof subject !== "object") return null;
  if (subject.type === "visitor" || subject.type === "default") {
    return { type: subject.type, id: null };
  }
  if (subject.type === "role" && typeof subject.id === "string" && subject.id) {
    return { type: "role", id: subject.id };
  }
  const id = Number(subject.id);
  if (subject.type === "user" && Number.isSafeInteger(id) && id > 0) {
    return { type: "user", id };
  }
  return null;
}

export function readAccessLevel(storage, key) {
  if (!storage || !key) return null;
  try {
    const value = storage.getItem(key);
    return value ? normalizeAccessLevel(JSON.parse(value)) : null;
  } catch {
    return null;
  }
}

export function rememberAccessLevel(storage, key, subject) {
  const value = normalizeAccessLevel(subject);
  if (!storage || !key || !value) return;
  try {
    storage.setItem(key, JSON.stringify(value));
  } catch {
    // Browser storage can be disabled; the workspace still works for this visit.
  }
}

export function forgetAccessLevel(storage, key) {
  if (!storage || !key) return;
  try {
    storage.removeItem(key);
  } catch {
    // Ignore storage errors and use the server-provided context.
  }
}

export function shouldRestoreAccessLevel(search, saved, current) {
  if (!saved || new URLSearchParams(search).has("aam_level") ||
      new URLSearchParams(search).has("aam_subject")) return false;
  const active = normalizeAccessLevel(current);
  return !active || saved.type !== active.type || saved.id !== active.id;
}
