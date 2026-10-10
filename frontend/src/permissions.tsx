import {
  useState,
  useEffect,
  useWorkspace,
  useResource,
  useWrite,
  Resource,
  Title,
  ActionTooltip,
  Search,
  Pager,
  Badge,
  TableEmptyRow,
  Reset,
  Button,
  Modal,
  ModalIntro,
  Notice,
  TextControl,
  TextareaControl,
  ToggleControl,
  Confirm,
  t,
  plain,
  arr,
  base64Path,
} from "./core";
import { indexScreens, screenIndexUrl } from "./screen-index.mjs";
import { ResourceIcon, AccessOutcome } from "./resource-visuals";
import { AccessRuleControl } from "./access-rule-control";
import { PremiumModeHint } from "./premium-mode-hint";
import { widgetViewForSubject } from "./widget-view.mjs";
const definitions: any = {
  admin_menu: {
    endpoint: "/backend-menu",
    item: "/backend-menu/",
    title: "Backend Menu",
    resetLabel: "Reset Backend Menu",
    description:
      "Control which administration menus this access level can access.",
    icon: "menu",
  },
  toolbar: {
    endpoint: "/admin-toolbar",
    item: "/admin-toolbar/",
    title: "Admin Toolbar",
    resetLabel: "Reset Admin Toolbar",
    description: "Manage access to the shortcuts in the WordPress toolbar.",
    icon: "admin-generic",
  },
  metabox: {
    endpoint: "/metaboxes",
    item: "/metabox/",
    title: "Metaboxes",
    resetLabel: "Reset Metaboxes",
    description: "Manage panels on WordPress editing screens.",
    icon: "layout",
  },
  widget: {
    endpoint: "/widgets",
    item: "/widget/",
    title: "Widgets",
    resetLabel: "Reset Widgets",
    description: "Manage dashboard and website widgets.",
    icon: "screenoptions",
  },
  route: {
    endpoint: "/api-routes",
    item: "/api-route/",
    title: "API Routes",
    resetLabel: "Reset API Routes",
    description:
      "Control access by endpoint and HTTP method. WordPress authorization still applies.",
    icon: "rest-api",
  },
};
function flatten(list: any[], depth = 0): any[] {
  return list.flatMap((row) => [
    { ...row, depth },
    ...flatten(arr(row.children), depth + 1),
  ]);
}
const toolbarIcons: Record<string, string> = {
  "wp-logo": "wordpress",
  "site-name": "admin-home",
  updates: "update",
  comments: "admin-comments",
  "new-content": "plus-alt2",
  "my-account": "admin-users",
  search: "search",
  customize: "admin-appearance",
  "edit-profile": "id",
};

function toolbarIcon(row: any) {
  return `dashicons-${toolbarIcons[row.slug] || "admin-generic"}`;
}

function ResourceDetails({ type, row }: { type: string; row: any }) {
  const fields: Record<string, [string, any][]> = {
    admin_menu: [
      [t("Menu identifier"), row.slug],
      [t("Required capability"), row.capability],
      [
        t("WordPress capability"),
        row.has_required_capability === false
          ? t("Missing for this access level")
          : null,
      ],
      [t("Admin destination"), row.path],
    ],
    toolbar: [
      [t("Toolbar identifier"), row.slug],
      [t("Destination"), row.uri],
      [t("Parent item"), row.parent_id],
    ],
    metabox: [
      [t("Metabox identifier"), row.slug],
      [t("Editor screen"), row.screen_id],
    ],
    widget: [
      [t("Widget identifier"), row.slug],
      [
        t("Shown in"),
        row.area === "dashboard"
          ? t("Dashboard")
          : row.area === "frontend"
            ? t("Website")
            : row.area,
      ],
    ],
    route: [[t("HTTP method"), row.method]],
  };
  const name = plain(row.name || row.title || row.endpoint || row.slug || "");

  return (
    <div className="ar-resource-details">
      <div className="ar-resource-details-hero">
        <span className="ar-resource-details-icon" aria-hidden="true">
          <span className={`dashicons dashicons-${definitions[type].icon}`} />
        </span>
        <div className="ar-resource-details-heading">
          <span className="ar-resource-details-type">
            {t(definitions[type].title)}
          </span>
          <h3>{name}</h3>
        </div>
      </div>
      <div className="ar-resource-details-status">
        <AccessOutcome
          effect={row.is_restricted ? "deny" : "allow"}
          customized={row.is_customized}
          native={type === "route"}
        />
      </div>
      <dl className="ar-resource-details-fields">
        {(fields[type] || [])
          .filter(
            ([, value]) =>
              value !== null && value !== undefined && value !== "",
          )
          .map(([label, value]) => (
            <div key={label}>
              <dt>{label}</dt>
              <dd>{plain(String(value))}</dd>
            </div>
          ))}
      </dl>
    </div>
  );
}
export function PermissionList({ id }: any) {
  const def = definitions[id];
  const { boot, busy, setMessage } = useWorkspace();
  const visitorWidgets = id === "widget" && boot.subject?.type === "visitor";
  const widgetView = widgetViewForSubject(boot.subject?.type);
  const r = useResource(visitorWidgets ? widgetView.endpoint : def.endpoint);
  const write = useWrite();
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(0);
  const [details, setDetails] = useState(null);
  const [indexing, setIndexing] = useState(false);
  const [indexProgress, setIndexProgress] = useState(null);
  const [indexUrlOpen, setIndexUrlOpen] = useState(false);
  const [indexUrl, setIndexUrl] = useState("");
  const [indexError, setIndexError] = useState("");
  const canIndex = id === "metabox" || id === "widget";
  const premiumMode = !!boot.premiumUi?.modes?.[id];
  const [modeStates, setModeStates] = useState(
    boot.premiumUi?.modes?.[id] || {},
  );
  useEffect(() => {
    setModeStates(boot.premiumUi?.modes?.[id] || {});
  }, [boot.subject?.type, boot.subject?.id, boot.premiumUi?.modes?.[id], id]);
  const modeKey =
    id === "route" ? "* *" : visitorWidgets ? widgetView.modeKey : "*";
  const modeEnabled = modeStates[modeKey] ?? modeStates["*"] ?? false;
  const modeScopes =
    id === "widget"
      ? widgetView.modeScopes
      : id === "metabox"
        ? [
            ...new Set([
              ...flatten(arr(r.data)).map((row) => row.screen_id),
              ...Object.keys(modeStates)
                .filter((key) => key.startsWith("*|"))
                .map((key) => key.slice(2)),
            ]),
          ].filter((screenId) => screenId && screenId !== "*")
        : [];
  const runIndex = async (urls: string[]) => {
    setIndexing(true);
    setIndexProgress({ done: 0, total: urls.length });
    try {
      const result = await indexScreens(
        urls,
        boot.adminUrl,
        id,
        (done, total) => setIndexProgress({ done, total }),
      );
      setMessage(
        result.failures.length
          ? {
              status: "warning",
              text: `${result.total - result.failures.length} / ${result.total} ${t("screens indexed. Check access to the remaining admin screens and try again.")}`,
            }
          : {
              status: "success",
              text:
                id === "metabox"
                  ? t("Metabox index refreshed.")
                  : t("Widget index refreshed."),
            },
      );
      r.refresh();
    } finally {
      setIndexing(false);
    }
  };
  return (
    <>
      <Title
        title={t(def.title)}
        description={
          visitorWidgets
            ? t("Manage website widgets visible to visitors.")
            : t(def.description)
        }
        icon={def.icon}
        controls={
          <Search
            compact
            value={search}
            onChange={(value) => {
              setSearch(value);
              setPage(0);
            }}
          />
        }
      >
        {canIndex && (
          <>
            <ActionTooltip
              text={
                indexing
                  ? t("Indexing WordPress screens…")
                  : id === "metabox"
                    ? t("Refresh metaboxes")
                    : t("Refresh widgets")
              }
              disabled={busy || indexing || !boot.indexUrls?.length}
            >
              <Button
                variant="primary"
                className="ar-icon-action"
                aria-label={
                  id === "metabox"
                    ? t("Refresh metaboxes")
                    : t("Refresh widgets")
                }
                disabled={busy || indexing || !boot.indexUrls?.length}
                onClick={() => runIndex(boot.indexUrls)}
              >
                <span
                  className="dashicons dashicons-update"
                  aria-hidden="true"
                />
              </Button>
            </ActionTooltip>
            {id === "metabox" && (
              <ActionTooltip
                text={t("Index a URL")}
                disabled={busy || indexing}
              >
                <Button
                  variant="secondary"
                  className="ar-icon-action"
                  aria-label={t("Index a URL")}
                  disabled={busy || indexing}
                  onClick={() => {
                    setIndexError("");
                    setIndexUrlOpen(true);
                  }}
                >
                  <span
                    className="dashicons dashicons-admin-links"
                    aria-hidden="true"
                  />
                </Button>
              </ActionTooltip>
            )}
          </>
        )}
        <Reset
          endpoint={def.endpoint}
          label={t(def.resetLabel)}
          serviceName={t(def.title)}
          iconOnly
          onReset={() => setModeStates({})}
        />
      </Title>
      {premiumMode && (
        <section
          className={`ar-premium-mode${modeScopes.length ? " ar-premium-mode-scoped" : ""}`}
          aria-label={t("Default access mode")}
        >
          <span
            className="ar-premium-mode-icon dashicons dashicons-lock"
            aria-hidden="true"
          />
          <div className="ar-premium-mode-copy">
            <strong>{t("Default access mode")}</strong>
            <small>
              {visitorWidgets
                ? t(
                    "Set the default rule for website widgets seen by visitors.",
                  )
                : modeEnabled
                  ? t(
                      "New items are restricted unless you allow them individually.",
                    )
                  : t(
                      "New items are available unless you restrict them individually.",
                    )}
            </small>
          </div>
          <ToggleControl
            className="ar-toggle"
            label={
              visitorWidgets
                ? t("Restrict website widgets by default")
                : t("Restricted by default")
            }
            checked={modeEnabled}
            disabled={busy}
            onChange={async (checked) => {
              const payload: any = { effect: checked ? "deny" : "allow" };
              if (visitorWidgets) payload.area = widgetView.modeArea;
              const saved = await write(def.endpoint, "PATCH", payload);
              if (saved) {
                setModeStates((current: any) => ({
                  ...current,
                  [modeKey]: checked,
                }));
                r.refresh();
              }
            }}
          />
          {modeScopes.length > 0 && (
            <div className="ar-premium-mode-scopes">
              {modeScopes.map((scope: string) => {
                const key = `*|${scope}`;
                return (
                  <div className="ar-premium-mode-scope" key={scope}>
                    <span>
                      {id === "widget"
                        ? scope === "dashboard"
                          ? t("Dashboard widgets")
                          : t("Website widgets")
                        : `${t("Editor screen")}: ${scope}`}
                    </span>
                    <ToggleControl
                      className="ar-toggle"
                      label={t("Restricted")}
                      checked={modeStates[key] ?? modeEnabled}
                      disabled={busy}
                      onChange={async (checked) => {
                        const payload = {
                          effect: checked ? "deny" : "allow",
                          [id === "widget" ? "area" : "screen_id"]: scope,
                        };
                        if (await write(def.endpoint, "PATCH", payload)) {
                          setModeStates((current: any) => ({
                            ...current,
                            [key]: checked,
                          }));
                          r.refresh();
                        }
                      }}
                    />
                  </div>
                );
              })}
            </div>
          )}
        </section>
      )}
      {!premiumMode && id !== "route" && (
        <PremiumModeHint
          label="Default access mode"
          description={
            id === "admin_menu"
              ? "Premium can restrict new menu items by default, with explicit exceptions."
              : id === "toolbar"
                ? "Premium can set a default rule for toolbar items, then allow exceptions."
                : id === "metabox"
                  ? "Premium can hide editor panels by default, then allow selected panels."
                  : visitorWidgets
                    ? "Premium can set a default rule for website widgets, then override individual items."
                    : "Premium can set a default rule for widgets, then override individual items."
          }
        />
      )}
      {canIndex && (
        <div className="ar-index-status" aria-live="polite">
          {indexing && indexProgress && (
            <Notice status="info" isDismissible={false}>
              {t("Indexing WordPress screens…")} {indexProgress.done} /{" "}
              {indexProgress.total}
            </Notice>
          )}
        </div>
      )}
      <Resource resource={r}>
        {(data) => {
          const allRows = flatten(arr(data));
          const missingCapabilityCount =
            id === "admin_menu"
              ? allRows.filter((row) => row.has_required_capability === false)
                  .length
              : 0;
          const rows = allRows.filter(
            (row) =>
              (!visitorWidgets || row.area === "frontend") &&
              JSON.stringify([
                row.title,
                row.name,
                row.slug,
                row.endpoint,
                row.method,
                row.screen_id,
                row.area,
              ])
                .toLowerCase()
                .includes(search.toLowerCase()),
          );
          return (
            <>
              {missingCapabilityCount > 0 && (
                <div className="ar-menu-capability-summary" role="status">
                  <span className="dashicons dashicons-lock" aria-hidden="true" />
                  <span>
                    <strong>
                      {missingCapabilityCount === allRows.length
                        ? t("No menu items have their required capabilities")
                        : t("Some menu items need WordPress capabilities")}
                    </strong>
                    <span>
                      {t(
                        "The marked items are unavailable to this access level until their required capabilities are granted.",
                      )}
                    </span>
                  </span>
                  <span className="ar-menu-capability-count">
                    {missingCapabilityCount}
                  </span>
                </div>
              )}
              <div className="ar-table-wrap">
                <table
                  className={
                    id === "route"
                      ? "ar-table"
                      : "ar-table ar-resource-rule-table"
                  }
                >
                  <thead>
                    <tr>
                      <th>{t("Resource")}</th>
                      <th>{t("Actions")}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {rows.slice(page * 20, page * 20 + 20).map((row, i) => {
                      const key = row.id || row.slug;
                      const query = new URLSearchParams();
                      if (row.screen_id) query.set("screen_id", row.screen_id);
                      if (row.area) query.set("area", row.area);
                      const endpoint =
                        def.item +
                        (id === "admin_menu" || id === "route"
                          ? base64Path(key)
                          : encodeURIComponent(key)) +
                        (query.size ? "?" + query : "");
                      const locked =
                        id === "admin_menu" &&
                        [
                          "index.php",
                          "menu/index.php",
                          "aam",
                          "menu/aam",
                        ].includes(row.slug);
                      const name = plain(
                        row.name || row.title || row.endpoint || row.slug,
                      );
                      const effect = row.is_restricted ? "deny" : "allow";
                      const selectedRule = row.is_customized
                        ? effect
                        : "inherit";
                      return (
                        <tr
                          key={String(key) + i}
                          className={
                            id === "admin_menu" &&
                            row.has_required_capability === false
                              ? "ar-menu-missing-capability"
                              : undefined
                          }
                        >
                          <td>
                            <div
                              className="ar-resource-name"
                              style={{ paddingInlineStart: row.depth * 18 }}
                            >
                              <ResourceIcon
                                icon={
                                  id === "admin_menu"
                                    ? row.icon
                                    : id === "toolbar"
                                      ? toolbarIcon(row)
                                      : null
                                }
                                fallback={def.icon}
                                child={
                                  (id === "admin_menu" || id === "toolbar") &&
                                  row.depth > 0
                                }
                              />
                              <div>
                                <span className="ar-name-line">
                                  <strong>{name}</strong>
                                  {id === "admin_menu" &&
                                    row.has_required_capability === false && (
                                      <span
                                        className="ar-menu-capability-badge"
                                        title={`${t("Required WordPress capability:")} ${row.capability}`}
                                      >
                                        <span
                                          className="dashicons dashicons-lock"
                                          aria-hidden="true"
                                        />
                                        {t("Missing capability")}
                                      </span>
                                    )}
                                  {id === "route" && (
                                    <span className="ar-state-icons">
                                      <AccessOutcome
                                        effect={effect}
                                        customized={row.is_customized}
                                        native
                                      />
                                    </span>
                                  )}
                                </span>
                                <small>
                                  {row.method ? row.method + " " : ""}
                                  {id === "metabox"
                                    ? row.slug
                                    : row.screen_id ||
                                      row.area ||
                                      row.capability ||
                                      row.slug}
                                </small>
                              </div>
                            </div>
                          </td>
                          <td>
                            <div
                              className={
                                id === "route"
                                  ? "ar-actions"
                                  : "ar-resource-rule-actions"
                              }
                            >
                              {id === "route" ? (
                                <>
                                  <Button
                                    variant="secondary"
                                    disabled={busy || locked}
                                    onClick={() =>
                                      write(endpoint, "PATCH", {
                                        effect: row.is_restricted
                                          ? "allow"
                                          : "deny",
                                      })
                                    }
                                  >
                                    {row.is_restricted
                                      ? t("Allow")
                                      : t("Restrict")}
                                  </Button>
                                  <Button
                                    variant="tertiary"
                                    disabled={busy || locked}
                                    onClick={() => write(endpoint, "DELETE")}
                                  >
                                    {t("Inherit")}
                                  </Button>
                                </>
                              ) : (
                                <AccessRuleControl
                                  effect={effect}
                                  customized={row.is_customized}
                                  disabled={busy || locked}
                                  disabledReason={
                                    locked
                                      ? t(
                                          "This essential menu item cannot be changed.",
                                        )
                                      : undefined
                                  }
                                  native={false}
                                  label={`${t("Access rule for")} ${name}`}
                                  onChange={(choice: string) => {
                                    if (choice === selectedRule) return;
                                    if (choice === "inherit")
                                      write(endpoint, "DELETE");
                                    else
                                      write(endpoint, "PATCH", {
                                        effect: choice,
                                      });
                                  }}
                                />
                              )}
                              <Button
                                className={
                                  id === "route"
                                    ? undefined
                                    : "ar-resource-details-button"
                                }
                                label={t("Resource details")}
                                icon="info-outline"
                                onClick={() => setDetails(row)}
                              />
                            </div>
                          </td>
                        </tr>
                      );
                    })}
                    {!rows.length && (
                      <TableEmptyRow
                        colSpan={2}
                        title={t("No resources found")}
                      >
                        {search
                          ? t("No resources match this search.")
                          : id === "metabox"
                            ? t(
                                "Refresh metaboxes to scan editor screens, or index a specific URL for a conditional metabox.",
                              )
                            : id === "widget"
                              ? visitorWidgets
                                ? t(
                                    "Refresh widgets to scan registered website widgets.",
                                  )
                                : t(
                                    "Refresh widgets to scan dashboard and registered website widgets.",
                                  )
                              : t(
                                  "No resources are available for this access level.",
                                )}
                      </TableEmptyRow>
                    )}
                  </tbody>
                </table>
              </div>
              <Pager page={page} setPage={setPage} total={rows.length} />
              <p className="ar-footnote">
                {t(
                  "Not restricted means AAM is not denying this resource. It does not grant missing WordPress capabilities. Inherit removes the explicit override.",
                )}
              </p>
            </>
          );
        }}
      </Resource>
      {indexUrlOpen && (
        <Modal
          title={t("Index metaboxes from a URL")}
          onRequestClose={() => !indexing && setIndexUrlOpen(false)}
          className="ar-modal"
        >
          <form
            onSubmit={async (e) => {
              e.preventDefault();
              try {
                const target = screenIndexUrl(
                  indexUrl,
                  boot.adminUrl,
                  "metabox",
                );
                setIndexError("");
                await runIndex([target]);
                setIndexUrlOpen(false);
              } catch (_) {
                setIndexError(
                  t("Enter a valid URL on this site's WordPress admin area."),
                );
              }
            }}
          >
            <ModalIntro icon="search" title={t("Find a conditional metabox")}>
              {t(
                "Paste the URL of the editor screen where it appears. AAM will scan that screen and add its metaboxes to the list.",
              )}
            </ModalIntro>
            <TextControl
              label={t("Admin screen URL")}
              type="url"
              value={indexUrl}
              placeholder={boot.adminUrl + "post.php?post=123&action=edit"}
              required
              onChange={setIndexUrl}
            />
            {indexError && (
              <p className="ar-index-error" role="alert">
                {indexError}
              </p>
            )}
            <div className="ar-actions ar-index-modal-actions">
              <Button type="submit" variant="primary" disabled={indexing}>
                {indexing ? t("Indexing…") : t("Index this URL")}
              </Button>
              <Button
                type="button"
                variant="secondary"
                disabled={indexing}
                onClick={() => setIndexUrlOpen(false)}
              >
                {t("Cancel")}
              </Button>
            </div>
          </form>
        </Modal>
      )}
      {details && (
        <Modal
          title={t("Resource details")}
          onRequestClose={() => setDetails(null)}
          className="ar-modal"
        >
          <ResourceDetails type={id} row={details} />
          <div className="ar-modal-ack-actions">
            <Button variant="primary" onClick={() => setDetails(null)}>
              {t("Close details")}
            </Button>
          </div>
        </Modal>
      )}
    </>
  );
}
const standardCapabilitySlug = /^[a-z\d\-_]+$/;
export function Capabilities() {
  const r = useResource(
    "/capabilities?fields=description,permissions,is_granted,is_direct",
  );
  const [search, setSearch] = useState("");
  const [grantFilter, setGrantFilter] = useState("all");
  const [page, setPage] = useState(0);
  const [edit, setEdit] = useState(null);
  const [remove, setRemove] = useState(null);
  const [removeAllRoles, setRemoveAllRoles] = useState(false);
  const [slug, setSlug] = useState("");
  const [global, setGlobal] = useState(false);
  const [ignore, setIgnore] = useState(false);
  const invalidSlug =
    slug !== "" && !ignore && standardCapabilitySlug.exec(slug)?.[0] !== slug;
  const write = useWrite();
  const { boot, busy } = useWorkspace();
  const toggle = async (row: any) => {
    const saved = await write(
      "/" + boot.subject.type + "/" + encodeURIComponent(boot.subject.id),
      "PATCH",
      {
        [row.is_granted ? "deprive_capabilities" : "add_capabilities"]: [
          row.slug,
        ],
      },
      false,
    );
    if (saved) r.refresh();
  };
  return (
    <>
      <Title
        title={t("Capabilities")}
        description={t(
          "Grant only the capabilities needed for this role or user.",
        )}
        icon="admin-network"
        controls={
          <div className="ar-capability-filter-tools">
            <Search
              compact
              value={search}
              onChange={(value) => {
                setSearch(value);
                setPage(0);
              }}
            />
            <div
              className="ar-capability-grant-filter"
              role="group"
              aria-label={t("Filter capabilities by grant status")}
            >
              {[
                ["all", t("All")],
                ["granted", t("Granted")],
                ["not_granted", t("Not granted")],
              ].map(([value, label]) => (
                <button
                  key={value}
                  type="button"
                  className={grantFilter === value ? "is-active" : ""}
                  aria-pressed={grantFilter === value}
                  onClick={() => {
                    setGrantFilter(value);
                    setPage(0);
                  }}
                >
                  <span
                    className={`ar-capability-filter-dot is-${value}`}
                    aria-hidden="true"
                  />
                  {label}
                </button>
              ))}
            </div>
          </div>
        }
      >
        <Button
          variant="primary"
          disabled={busy}
          onClick={() => {
            setEdit({});
            setSlug("");
            setGlobal(false);
            setIgnore(false);
          }}
        >
          {t("Add capability")}
        </Button>
      </Title>
      <Resource resource={r}>
        {(data) => {
          const rows = arr(data).filter(
            (row) =>
              (grantFilter === "all" ||
                Boolean(row.is_granted) === (grantFilter === "granted")) &&
              (row.slug + " " + (row.description || ""))
                .toLowerCase()
                .includes(search.toLowerCase()),
          );
          return (
            <>
              <div className="ar-table-wrap">
                <table className="ar-table ar-capabilities-table">
                  <thead>
                    <tr>
                      <th>{t("Capability")}</th>
                      <th>{t("Actions")}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {rows.slice(page * 20, page * 20 + 20).map((row) => (
                      <tr key={row.slug}>
                        <td>
                          <div className="ar-capability-name">
                            <strong>
                              <code>{row.slug}</code>
                            </strong>
                            <Badge
                              tone={row.is_granted ? "success" : "neutral"}
                              title={
                                row.is_direct
                                  ? t("Set on this role or user")
                                  : row.is_granted
                                    ? t(
                                        "Granted through a role or access policy",
                                      )
                                    : t(
                                        "This role or user does not have this capability",
                                      )
                              }
                            >
                              {row.is_granted ? t("Granted") : t("Not granted")}
                              {row.is_direct
                                ? ` · ${t("set here")}`
                                : row.is_granted
                                  ? ` · ${t("inherited")}`
                                  : ""}
                            </Badge>
                          </div>
                          {row.description && (
                            <small>{plain(row.description)}</small>
                          )}
                        </td>
                        <td>
                          <div className="ar-actions ar-capability-actions">
                            <Button
                              variant="secondary"
                              disabled={
                                busy ||
                                !row.permissions?.includes("allow_toggle")
                              }
                              onClick={() => toggle(row)}
                            >
                              {row.is_granted ? t("Revoke") : t("Grant")}
                            </Button>
                            {row.permissions?.includes("allow_update") && (
                              <Button
                                variant="tertiary"
                                onClick={() => {
                                  setEdit(row);
                                  setSlug(row.slug);
                                  setGlobal(false);
                                  setIgnore(false);
                                }}
                              >
                                {t("Rename")}
                              </Button>
                            )}
                            {row.permissions?.includes("allow_delete") && (
                              <Button
                                isDestructive
                                variant="tertiary"
                                onClick={() => {
                                  setRemoveAllRoles(false);
                                  setRemove(row);
                                }}
                              >
                                {t("Delete")}
                              </Button>
                            )}
                          </div>
                        </td>
                      </tr>
                    ))}
                    {!rows.length && (
                      <TableEmptyRow
                        colSpan={2}
                        title={t("No capabilities found")}
                      >
                        {t("Try another search or filter.")}
                      </TableEmptyRow>
                    )}
                  </tbody>
                </table>
              </div>
              <Pager page={page} setPage={setPage} total={rows.length} />
            </>
          );
        }}
      </Resource>
      {edit && (
        <Modal
          title={edit.slug ? t("Rename capability") : t("Add capability")}
          onRequestClose={() => !busy && setEdit(null)}
          className="ar-modal"
        >
          <form
            onSubmit={async (e) => {
              e.preventDefault();
              if (!ignore && standardCapabilitySlug.exec(slug)?.[0] !== slug)
                return;
              if (
                await write(
                  edit.slug
                    ? "/capability/" + encodeURIComponent(edit.slug)
                    : "/capabilities",
                  edit.slug ? "PATCH" : "POST",
                  {
                    slug,
                    ignore_format: ignore,
                    ...(edit.slug
                      ? { globally: global }
                      : { is_granted: true }),
                  },
                )
              )
                setEdit(null);
            }}
          >
            <ModalIntro
              icon="admin-network"
              title={
                edit.slug ? t("Rename this capability") : t("Add a capability")
              }
            >
              {edit.slug
                ? t(
                    "Update the capability name. You can apply the rename across access levels below.",
                  )
                : t(
                    "Create a capability that you can grant or revoke in access settings.",
                  )}
            </ModalIntro>
            <TextControl
              label={t("Capability slug")}
              value={slug}
              required
              aria-invalid={invalidSlug}
              onChange={setSlug}
            />
            {invalidSlug && (
              <p className="ar-form-error" role="alert">
                {t(
                  "Use only lowercase letters, numbers, hyphens, and underscores, or allow a nonstandard capability name.",
                )}
              </p>
            )}
            <ToggleControl
              className="ar-toggle"
              label={t("Allow a nonstandard capability name")}
              checked={ignore}
              onChange={setIgnore}
            />
            {edit.slug && (
              <ToggleControl
                className="ar-toggle"
                label={t("Rename globally across access levels")}
                checked={global}
                onChange={setGlobal}
              />
            )}
            <p>
              {t(
                "Creating a capability also grants it to the Administrator role, following the existing AAM API behavior.",
              )}
            </p>
            <Button
              type="submit"
              variant="primary"
              disabled={busy || invalidSlug}
            >
              {t("Save capability")}
            </Button>
          </form>
        </Modal>
      )}
      {remove && (
        <Confirm
          title={t("Delete capability")}
          confirmLabel={
            removeAllRoles ? t("Remove from all roles") : t("Remove capability")
          }
          cancelLabel={t("Keep capability")}
          impact={
            removeAllRoles
              ? boot.subject.type === "user"
                ? t(
                    "Every registered role, including Administrator, and this user will lose this capability. Other users' direct grants remain.",
                  )
                : t(
                    "Every registered role, including Administrator, will lose this capability. Direct user grants remain.",
                  )
              : t(
                  "Only the selected role or user will lose this capability. Other roles and users keep their existing grants.",
                )
          }
          onClose={() => setRemove(null)}
          onConfirm={() =>
            write("/capability/" + encodeURIComponent(remove.slug), "DELETE", {
              globally: removeAllRoles,
            })
          }
        >
          {t("Remove")} <strong>{remove.slug}</strong>{" "}
          {removeAllRoles
            ? boot.subject.type === "user"
              ? t("from all registered roles and this user")
              : t("from all registered roles")
            : t("from this access level")}
          ?
          <div className="ar-capability-delete-scope">
            <ToggleControl
              className="ar-toggle"
              label={t("Remove from all registered roles")}
              checked={removeAllRoles}
              disabled={busy}
              onChange={setRemoveAllRoles}
            />
          </div>
        </Confirm>
      )}
    </>
  );
}
