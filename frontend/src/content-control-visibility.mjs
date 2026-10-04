const visitorHiddenControls = new Set([
  "create",
  "edit",
  "publish",
  "delete",
  "assign",
]);

export function visibleContentControls(controls, subjectType, keyOf) {
  if (subjectType !== "visitor") return controls;
  return controls.filter((control) => !visitorHiddenControls.has(keyOf(control)));
}
