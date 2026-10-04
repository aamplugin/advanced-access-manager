const LIMIT = 30;
const stateKey = "__aamFrontendErrors";

function text(value, max = 2000) {
  return String(value ?? "")
    .replace(/https?:\/\/[^\s)]+/g, (match) => {
      try {
        const url = new URL(match);
        const position = match.match(/:(\d+):(\d+)$/);
        return url.origin + url.pathname + (position ? `:${position[1]}:${position[2]}` : "");
      } catch {
        return match.split("?")[0];
      }
    })
    .replace(/([?&](?:_wpnonce|nonce|token|key|password)=)[^\s&]+/gi, "$1[redacted]")
    .slice(0, max);
}

function endpoint(path) {
  try {
    const url = new URL(path, globalThis.location?.origin || "http://localhost");
    return url.pathname;
  } catch {
    return text(path, 300).split("?")[0];
  }
}

export function apiFailure(path, method, error) {
  if (error?.name === "AbortError") return null;
  return {
    type: "http",
    method: String(method || "GET").toUpperCase(),
    endpoint: endpoint(path),
    status: Number(error?.status || error?.data?.status) || null,
    code: text(error?.code || "", 120),
    message: text(error?.message || error || "Request failed"),
  };
}

export function javascriptFailure(error, source = "javascript", details = {}) {
  return {
    type: "javascript",
    source,
    name: text(error?.name || "Error", 120),
    message: text(error?.message || error || "Unknown error"),
    stack: text(error?.stack || "", 5000),
    ...(details.file ? { file: endpoint(details.file) } : {}),
    ...(details.line ? { line: Number(details.line) } : {}),
    ...(details.column ? { column: Number(details.column) } : {}),
    ...(details.componentStack ? { componentStack: text(details.componentStack, 3000) } : {}),
  };
}

function t(value) {
  return wp.i18n.__(value, "advanced-access-manager");
}

function show(state) {
  if (!document.body) return;
  let panel = document.getElementById("aam-error-report");
  if (!panel) {
    panel = document.createElement("aside");
    panel.id = "aam-error-report";
    panel.className = "aam-error-report";
    panel.setAttribute("role", "alert");
    document.body.appendChild(panel);
  }
  panel.replaceChildren();
  const icon = document.createElement("span");
  icon.className = "dashicons dashicons-warning";
  icon.setAttribute("aria-hidden", "true");
  const content = document.createElement("div");
  content.className = "aam-error-report-content";
  const title = document.createElement("strong");
  title.textContent = t("AAM ran into a problem");
  const copy = document.createElement("p");
  copy.textContent = t("You can try again. If it keeps happening, download the report and email it to support@aamplugin.com.");
  content.append(title, copy);
  const actions = document.createElement("div");
  actions.className = "aam-error-report-actions";
  const download = document.createElement("button");
  download.type = "button";
  download.className = "aam-error-report-download";
  download.textContent = t("Download report");
  download.addEventListener("click", () => downloadReport(state));
  const retry = document.createElement("button");
  retry.type = "button";
  retry.textContent = t("Reload page");
  retry.addEventListener("click", () => location.reload());
  const close = document.createElement("button");
  close.type = "button";
  close.className = "aam-error-report-close";
  close.setAttribute("aria-label", t("Dismiss error notice"));
  close.innerHTML = '<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>';
  close.addEventListener("click", () => panel.remove());
  actions.append(download, retry, close);
  content.append(actions);
  panel.append(icon, content);
}

function downloadReport(state) {
  const report = {
    product: "Advanced Access Manager",
    pluginVersion: window.aamReactBootstrap?.version || null,
    generatedAt: new Date().toISOString(),
    page: location.origin + location.pathname,
    browser: navigator.userAgent,
    language: document.documentElement.lang,
    errors: state.entries,
    note: "Request bodies, headers, cookies, and URL query strings are excluded.",
  };
  const url = URL.createObjectURL(new Blob([JSON.stringify(report, null, 2)], { type: "application/json" }));
  const link = document.createElement("a");
  link.href = url;
  link.download = `aam-error-report-${new Date().toISOString().slice(0, 10)}.json`;
  document.body.appendChild(link);
  link.click();
  link.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}

export function recordFailure(entry) {
  if (!entry || typeof window === "undefined") return;
  const state = window[stateKey] || (window[stateKey] = { entries: [] });
  state.entries.push({ time: new Date().toISOString(), ...entry });
  if (state.entries.length > LIMIT) state.entries.shift();
  try {
    show(state);
  } catch {
    // Reporting must never create another uncaught error.
  }
}

export function installErrorHandling() {
  if (typeof window === "undefined") return;
  const state = window[stateKey] || (window[stateKey] = { entries: [] });
  if (state.installed) return;
  state.installed = true;
  window.addEventListener("error", (event) => {
    recordFailure(javascriptFailure(event.error || event.message, "window", {
      file: endpoint(event.filename || ""),
      line: event.lineno || null,
      column: event.colno || null,
    }));
  });
  window.addEventListener("unhandledrejection", (event) => {
    if (event.reason?.name !== "AbortError")
      recordFailure(javascriptFailure(event.reason, "unhandled promise"));
  });
}
