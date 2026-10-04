import { normalizeAccessLevel } from "./access-level-memory.mjs";

export function availableEditorAccessLevel(boot, saved) {
  const subject = normalizeAccessLevel(saved);
  if (!subject || !boot?.levels?.[subject.type]) return null;
  if (subject.type === "role" &&
      !boot.roles?.some((role) => role.value === subject.id)) return null;
  return subject;
}
