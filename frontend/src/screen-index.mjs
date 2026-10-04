export function screenIndexUrl(input, adminUrl, kind) {
  const base = new URL(adminUrl);
  const value = String(input || "").trim();
  if (!value) throw new Error("Enter a WordPress admin URL.");

  const url = new URL(value, base);
  if (
    url.origin !== base.origin ||
    !url.pathname.startsWith(base.pathname) ||
    url.username ||
    url.password
  ) {
    throw new Error("Use a URL within this site's WordPress admin area.");
  }

  url.hash = "";
  url.searchParams.set("init", kind);
  return url.href;
}

export async function indexScreens(
  urls,
  adminUrl,
  kind,
  onProgress,
  request = fetch,
) {
  const failures = [];
  for (let i = 0; i < urls.length; i += 1) {
    let requested = false;
    let url;
    try {
      url = screenIndexUrl(urls[i], adminUrl, kind);
      requested = true;
      const response = await request(url, { credentials: "same-origin" });
      const finalUrl = new URL(response.url || url);
      const base = new URL(adminUrl);
      if (
        !response.ok ||
        finalUrl.origin !== base.origin ||
        !finalUrl.pathname.startsWith(base.pathname)
      ) {
        const error = new Error("The admin screen could not be indexed.");
        error.status = response.status || null;
        throw error;
      }
    } catch (error) {
      if (requested) recordFailure(apiFailure(url, "GET", error));
      failures.push({ url: urls[i], error });
    }
    onProgress?.(i + 1, urls.length);
  }
  return { total: urls.length, failures };
}
import { apiFailure, recordFailure } from "./error-report.mjs";
