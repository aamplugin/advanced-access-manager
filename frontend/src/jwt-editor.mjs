const isObject = (value) =>
  value !== null && typeof value === "object" && !Array.isArray(value);

export function parseClaimsJson(value) {
  const parsed = JSON.parse(value || "{}");
  if (!isObject(parsed)) throw new Error("Claims must be a JSON object.");
  return parsed;
}

export function parseClaimRows(rows) {
  const claims = {};
  for (const row of rows) {
    const key = row.key.trim();
    if (!key && !String(row.value).trim()) continue;
    if (!key) throw new Error("Enter a property name for each claim.");
    if (Object.hasOwn(claims, key))
      throw new Error(`The claim “${key}” is listed more than once.`);

    let value = row.value;
    if (row.type === "number") {
      if (!String(value).trim() || !Number.isFinite(Number(value)))
        throw new Error(`Enter a valid number for “${key}”.`);
      value = Number(value);
    } else if (row.type === "boolean") {
      value = value === "true";
    } else if (row.type === "json") {
      try {
        value = JSON.parse(value);
      } catch {
        throw new Error(`Enter valid JSON for “${key}”.`);
      }
    }
    Object.defineProperty(claims, key, {
      value,
      enumerable: true,
      configurable: true,
      writable: true,
    });
  }
  return claims;
}

export function claimsToRows(claims) {
  return Object.entries(claims).map(([key, value]) => ({
    key,
    type:
      typeof value === "string"
        ? "text"
        : typeof value === "number"
          ? "number"
          : typeof value === "boolean"
            ? "boolean"
            : "json",
    value:
      typeof value === "string"
        ? value
        : typeof value === "number" || typeof value === "boolean"
          ? String(value)
          : JSON.stringify(value),
  }));
}
