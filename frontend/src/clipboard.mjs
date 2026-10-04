export async function copyText(
  value,
  clipboard = globalThis.navigator?.clipboard,
  doc = globalThis.document,
) {
  const text = String(value || "");
  if (!text) throw new Error("Nothing to copy.");

  if (clipboard?.writeText) {
    try {
      await clipboard.writeText(text);
      return;
    } catch {
      // A browser may expose the Clipboard API but deny access in this context.
    }
  }

  if (!doc?.execCommand) throw new Error("Clipboard access is unavailable.");
  const temporary = doc.createElement("textarea");
  temporary.value = text;
  temporary.readOnly = true;
  temporary.style.position = "fixed";
  temporary.style.opacity = "0";
  doc.body.append(temporary);
  try {
    temporary.select();
    if (!doc.execCommand("copy"))
      throw new Error("Clipboard access is unavailable.");
  } finally {
    temporary.remove();
  }
}
