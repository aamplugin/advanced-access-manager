import { contextPath, preloadPath, createMutationQueue } from "./transport.mjs";
import {
  shouldRefreshResource,
  subscribeResourceRefresh,
} from "./resource-refresh.mjs";
import { resetResources } from "./reset-resources.mjs";
import {
  apiFailure,
  installErrorHandling,
  javascriptFailure,
  recordFailure,
} from "./error-report.mjs";
installErrorHandling();
export const {
  useState,
  useEffect,
  useRef,
  useMemo,
  createContext,
  useContext,
} = wp.element;
export const {
  Button,
  Tooltip,
  Modal,
  Notice,
  Spinner,
  TextControl,
  TextareaControl,
  SelectControl,
  ComboboxControl,
  ToggleControl,
} = wp.components;
export const t = (s: string) => wp.i18n.__(s, "advanced-access-manager");
export class FrontendErrorBoundary extends wp.element.Component {
  state = { error: null };
  static getDerivedStateFromError(error: any) {
    return { error };
  }
  componentDidCatch(error: any, info: any) {
    recordFailure(javascriptFailure(error, "render", {
      componentStack: info?.componentStack || "",
    }));
  }
  render() {
    if (this.state.error)
      return (
        <Notice status="error" isDismissible={false}>
          {t("This screen could not be displayed.")} {this.state.error.message}{" "}
          <Button variant="secondary" onClick={() => this.setState({ error: null })}>
            {t("Try again")}
          </Button>
        </Notice>
      );
    return this.props.children;
  }
}
export type Subject = {
  type: "role" | "user" | "visitor" | "default";
  id: string | number | null;
  name: string;
};
export const Workspace = createContext(null);
export const useWorkspace = () => useContext(Workspace);
export const plain = (s: any) => {
  const el = document.createElement("div");
  el.innerHTML = String(s ?? "");
  return el.textContent || "";
};
export const arr = (data: any) =>
  Array.isArray(data)
    ? data
    : Array.isArray(data?.list)
      ? data.list
      : Object.values(data || {});
export const pathFor = contextPath;
// AAM 7.1.4 matches base64 identifiers before URL decoding. Percent-encoding
// '=' or '/' prevents those legacy route regexes from matching.
export function base64Path(id: string) {
  if (!/^[A-Za-z0-9/+=]+$/.test(id))
    throw new Error(t("Invalid resource identifier."));
  return id;
}
async function apiRequest(options: any) {
  try {
    return await wp.apiFetch(options);
  } catch (error) {
    recordFailure(apiFailure(options.path || options.url, options.method, error));
    throw error;
  }
}
export const request = (path: string, extra: any = {}) =>
  apiRequest({ path, ...extra });
export const mutate = createMutationQueue(apiRequest);
const preloads = new Map();
export function seed(boot: any) {
  if (boot.preload) preloads.set(boot.preload.path, boot.preload.data);
}
export async function loadContext(
  subject: Subject,
  screen: string,
  signal?: AbortSignal,
) {
  try {
    const result = await request(preloadPath(subject, screen), { signal });
    seed(result);
    return result;
  } catch (error) {
    if (error.data?.status && !error.status) error.status = error.data.status;
    throw error;
  }
}
export function useResource(endpoint: string, scoped = true) {
  const { boot } = useWorkspace();
  const path = scoped ? pathFor(endpoint, boot.subject) : "/aam/v2" + endpoint;
  const [state, setState] = useState({
    path,
    data: preloads.get(path),
    loading: !preloads.has(path),
    loaded: preloads.has(path),
    error: null,
  });
  const [reload, setReload] = useState(0);
  useEffect(
    () =>
      subscribeResourceRefresh((mutationPath) => {
        if (shouldRefreshResource(path, mutationPath))
          setReload((current) => current + 1);
      }),
    [path],
  );
  useEffect(() => {
    const controller = new AbortController();
    let live = true;
    const cached = preloads.get(path);
    preloads.delete(path);
    if (cached !== undefined && reload === 0) {
      setState({
        path,
        data: cached,
        loading: false,
        loaded: true,
        error: null,
      });
      return () => controller.abort();
    }
    setState((previous) =>
      previous.path === path && previous.loaded
        ? { ...previous, error: null }
        : { path, data: null, loading: true, loaded: false, error: null },
    );
    request(path, { signal: controller.signal })
      .then((data) => {
        if (live)
          setState({ path, data, loading: false, loaded: true, error: null });
      })
      .catch((error) => {
        if (live && error.name !== "AbortError")
          setState((previous) => ({
            path,
            data: previous.path === path ? previous.data : null,
            loading: false,
            loaded: previous.path === path ? previous.loaded : false,
            error,
          }));
      });
    return () => {
      live = false;
      controller.abort();
    };
  }, [path, reload]);
  return {
    ...(state.path === path
      ? state
      : { data: null, loading: true, loaded: false, error: null }),
    refresh: () => setReload((x) => x + 1),
  };
}
export function Resource({ resource, children }: any) {
  if (resource.loading)
    return (
      <div className="ar-loading" role="status">
        <Spinner />
        {t("Loading…")}
      </div>
    );
  if (resource.error && !resource.loaded)
    return (
      <Notice status="error" isDismissible={false}>
        {resource.error.message}
        <Button variant="link" onClick={resource.refresh}>
          {t("Try again")}
        </Button>
      </Notice>
    );
  return (
    <>
      {children(resource.data)}
      {resource.error && (
        <Notice status="warning" isDismissible={false}>
          {t(
            "The latest data could not be loaded. Showing the previous results.",
          )}
          <Button variant="link" onClick={resource.refresh}>
            {t("Try again")}
          </Button>
        </Notice>
      )}
    </>
  );
}
export function Empty({ title = t("Nothing here yet"), children }: any) {
  return (
    <div className="ar-empty">
      <span className="dashicons dashicons-shield" />
      <h3>{title}</h3>
      <p>{children}</p>
    </div>
  );
}
export function ModalIntro({
  icon = "info-outline",
  title,
  children,
  compact = false,
}: any) {
  return (
    <div className={`ar-modal-intro${compact ? " is-compact" : ""}`}>
      <span className="ar-modal-intro-icon" aria-hidden="true">
        <span className={`dashicons dashicons-${icon}`} />
      </span>
      <div>
        <strong>{title}</strong>
        <p>{children}</p>
      </div>
    </div>
  );
}
export function TableEmptyRow({ colSpan, title, children }: any) {
  return (
    <tr className="ar-table-empty-row">
      <td className="ar-table-empty-cell" colSpan={colSpan}>
        <div className="ar-table-empty">
          <span className="ar-table-empty-icon" aria-hidden="true">
            <span className="dashicons dashicons-search" />
          </span>
          <strong>{title}</strong>
          {children && <span>{children}</span>}
        </div>
      </td>
    </tr>
  );
}
export function TableEmpty({ headings, title, children }: any) {
  return (
    <table className="ar-table">
      <thead>
        <tr>
          {headings.map((heading: string) => (
            <th key={heading}>{heading}</th>
          ))}
        </tr>
      </thead>
      <tbody>
        <TableEmptyRow colSpan={headings.length} title={title}>
          {children}
        </TableEmptyRow>
      </tbody>
    </table>
  );
}
export function Badge({ children, tone = "neutral", ...props }: any) {
  return (
    <span className={"ar-badge ar-badge-" + tone} {...props}>
      {children}
    </span>
  );
}
export function EffectToggle({
  label,
  value,
  onChange,
  disabled = false,
}: any) {
  return (
    <ToggleControl
      className="ar-toggle ar-effect-toggle"
      label={
        <>
          <span className="screen-reader-text">{label}: </span>
          {value === "deny" ? t("Deny") : t("Allow")}
        </>
      }
      checked={value === "deny"}
      disabled={disabled}
      onChange={(checked) => onChange(checked ? "deny" : "allow")}
    />
  );
}
export function Title({
  title,
  description,
  children,
  controls,
  icon,
  className = "",
}: any) {
  return (
    <header className={`ar-page-title ${className}`}>
      <div className="ar-page-title-top">
        {icon && (
          <span className="ar-page-title-icon" aria-hidden="true">
            <span className={`dashicons dashicons-${icon}`} />
          </span>
        )}
        <div className="ar-page-title-copy">
          <h2 tabIndex={-1} id="ar-screen-title">
            {title}
          </h2>
          {description && <p>{description}</p>}
        </div>
        {children && <div className="ar-actions">{children}</div>}
      </div>
      {controls && <div className="ar-page-title-controls">{controls}</div>}
    </header>
  );
}
export function ActionTooltip({ text, disabled = false, children }: any) {
  if (!text) return children;
  return (
    <Tooltip text={text} delay={300}>
      {disabled ? (
        <span
          className="ar-tooltip-anchor"
          tabIndex={0}
          role="note"
          aria-label={text}
        >
          {children}
        </span>
      ) : (
        children
      )}
    </Tooltip>
  );
}
export function Search({
  value,
  onChange,
  label = t("Search this list"),
  compact = false,
}: any) {
  if (compact) {
    return (
      <div className="ar-search ar-search-compact">
        <span className="dashicons dashicons-search" aria-hidden="true" />
        <input
          type="search"
          value={value}
          aria-label={label}
          placeholder={label}
          onChange={(event) => onChange(event.target.value)}
          onKeyDown={(event) => {
            if (event.key === "Enter") event.preventDefault();
          }}
        />
      </div>
    );
  }
  return (
    <div className="ar-search">
      <TextControl
        label={label}
        type="search"
        value={value}
        onChange={onChange}
      />
    </div>
  );
}
export function Pager({ page, setPage, total, pageSize = 20 }: any) {
  if (!total) return null;
  return (
    <div className="ar-pager">
      <span>
        {total} {t("items")}
      </span>
      <Button
        variant="secondary"
        disabled={page === 0}
        onClick={() => setPage(page - 1)}
      >
        {t("Previous")}
      </Button>
      <span>
        {page + 1} / {Math.max(1, Math.ceil(total / pageSize))}
      </span>
      <Button
        variant="secondary"
        disabled={(page + 1) * pageSize >= total}
        onClick={() => setPage(page + 1)}
      >
        {t("Next")}
      </Button>
    </div>
  );
}
export function useWrite() {
  const w = useWorkspace();
  return async (
    endpoint: string,
    method = "POST",
    data?: any,
    scoped = true,
    returnResponse = false,
  ) =>
    w.write(
      scoped ? pathFor(endpoint, w.boot.subject) : "/aam/v2" + endpoint,
      method,
      data,
      returnResponse,
    );
}
export function ConfirmationDialog({
  title,
  children,
  impact,
  confirmLabel,
  cancelLabel = t("Cancel"),
  onConfirm,
  onClose,
  busy = false,
  tone = "danger",
}: any) {
  const [working, setWorking] = useState(false);
  const submitting = useRef(false);
  const pending = busy || working;
  return (
    <Modal
      title={title}
      onRequestClose={() => !pending && onClose()}
      className={`aam-confirm-dialog aam-confirm-${tone}`}
    >
      <div className="aam-confirm-intro">
        <span className="aam-confirm-icon" aria-hidden="true">
          <span
            className={`dashicons dashicons-${tone === "danger" ? "warning" : "info-outline"}`}
          />
        </span>
        <div>
          <span className="aam-confirm-eyebrow">{t("Review this change")}</span>
          <div className="aam-confirm-description">{children}</div>
        </div>
      </div>
      {impact && (
        <div className="aam-confirm-impact">
          <span
            className="dashicons dashicons-info-outline"
            aria-hidden="true"
          />
          <span>{impact}</span>
        </div>
      )}
      <div className="aam-confirm-actions">
        <Button variant="secondary" disabled={pending} onClick={onClose}>
          {cancelLabel}
        </Button>
        <Button
          variant="primary"
          isDestructive={tone === "danger"}
          disabled={pending}
          onClick={async () => {
            if (submitting.current) return;
            submitting.current = true;
            setWorking(true);
            try {
              if ((await onConfirm()) !== false) onClose();
            } finally {
              submitting.current = false;
              setWorking(false);
            }
          }}
        >
          {pending ? t("Working…") : confirmLabel || title}
        </Button>
      </div>
    </Modal>
  );
}
export function Confirm(props: any) {
  const { busy } = useWorkspace();
  return <ConfirmationDialog {...props} busy={busy} />;
}
export function Reset({
  endpoint,
  label,
  serviceName,
  scoped = true,
  iconOnly = false,
  onReset,
  additionalEndpoints = [],
}: {
  endpoint: string;
  label: string;
  serviceName: string;
  scoped?: boolean;
  iconOnly?: boolean;
  onReset?: () => void;
  additionalEndpoints?: string[];
}) {
  const [open, setOpen] = useState(false);
  const write = useWrite();
  const { boot, busy, setMessage } = useWorkspace();
  return (
    <>
      <ActionTooltip text={iconOnly ? label : undefined} disabled={busy}>
        <Button
          variant="secondary"
          className={iconOnly ? "ar-icon-action" : undefined}
          aria-label={iconOnly ? label : undefined}
          disabled={busy}
          onClick={() => setOpen(true)}
        >
          {iconOnly ? (
            <span
              className={`dashicons dashicons-${endpoint === "/jwts" ? "trash" : "image-rotate"}`}
              aria-hidden="true"
            />
          ) : (
            label
          )}
        </Button>
      </ActionTooltip>
      {open && (
        <Confirm
          title={label}
          confirmLabel={label}
          cancelLabel={
            endpoint === "/jwts" ? t("Keep tokens") : t("Keep settings")
          }
          impact={
            endpoint === "/jwts"
              ? t(
                  "Applications using these tokens will lose access. New tokens will be needed.",
                )
              : t(
                  "Inherited settings may take effect after these rules are removed. This change cannot be undone.",
                )
          }
          onClose={() => setOpen(false)}
          onConfirm={async () => {
            const saved = additionalEndpoints.length
              ? await resetResources(
                  [endpoint, ...additionalEndpoints],
                  (path: string) =>
                    write(path, "DELETE", undefined, scoped, true),
                )
              : await write(endpoint, "DELETE", undefined, scoped);
            if (saved && additionalEndpoints.length) {
              setMessage({
                status: "success",
                text: wp.i18n.sprintf(
                  t("MCP server, tool, resource, and prompt rules reset for %s."),
                  plain(boot.subject.name),
                ),
              });
            }
            if (saved) onReset?.();
            return saved;
          }}
        >
          {endpoint === "/jwts" ? (
            <>
              {t("Revoke all JWT tokens for")}{" "}
              <strong>{plain(boot.subject.name)}</strong>?
            </>
          ) : (
            <>
              {t("Remove explicit")} <strong>{serviceName}</strong>{" "}
              {t("settings for")}{" "}
              <strong>
                {scoped ? plain(boot.subject.name) : t("this service")}
              </strong>
              ?
            </>
          )}
        </Confirm>
      )}
    </>
  );
}
export function download(name: string, data: any) {
  const blob = new Blob(
    [typeof data === "string" ? data : JSON.stringify(data, null, 2)],
    { type: "application/json" },
  );
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = name;
  a.click();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}
