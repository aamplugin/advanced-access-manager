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
    try {
      const url = screenIndexUrl(urls[i], adminUrl, kind);
      const response = await request(url, { credentials: "same-origin" });
      const finalUrl = new URL(response.url || url);
      const base = new URL(adminUrl);
      if (
        !response.ok ||
        finalUrl.origin !== base.origin ||
        !finalUrl.pathname.startsWith(base.pathname)
      ) {
        throw new Error("The admin screen could not be indexed.");
      }
    } catch (error) {
      failures.push({ url: urls[i], error });
    }
    onProgress?.(i + 1, urls.length);
  }
  return { total: urls.length, failures };
}
