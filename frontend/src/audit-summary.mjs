export function safeAuditReference(value) {
  if (typeof value !== "string") return null;
  try {
    const url = new URL(value);
    return url.protocol === "https:" && !url.username && !url.password
      ? url.href
      : null;
  } catch {
    return null;
  }
}

export function normalizeAuditSummary(value) {
  if (!value || typeof value !== "object" || Array.isArray(value)) return null;

  const list = (key) =>
    Array.isArray(value[key])
      ? value[key].filter((item) => typeof item === "string" && item.trim())
      : [];
  const summary = typeof value.summary === "string" ? value.summary.trim() : "";
  const critical = list("critical");
  const concerns = list("concerns");
  const recommendations = list("recommendations");
  const references = list("references").map(safeAuditReference).filter(Boolean);

  if (
    !summary &&
    !critical.length &&
    !concerns.length &&
    !recommendations.length
  )
    return null;

  return { summary, critical, concerns, recommendations, references };
}
