import {
  useState,
  useEffect,
  useRef,
  useWorkspace,
  useResource,
  useWrite,
  Resource,
  Title,
  ActionTooltip,
  Badge,
  Search,
  Pager,
  Button,
  Modal,
  Spinner,
  SelectControl,
  TextControl,
  TextareaControl,
  ToggleControl,
  Confirm,
  t,
  plain,
  arr,
  request,
  mutate,
  download,
} from "./core";
import { registerIniMode } from "./ini-mode.mjs";
import { normalizeAuditSummary } from "./audit-summary.mjs";
export function Settings() {
  const { boot, busy, write } = useWorkspace();
  const [tab, setTab] = useState(boot.settings?.[0]?.id || "configpress");
  const [resetOpen, setResetOpen] = useState(false);
  const [resetVersion, setResetVersion] = useState(0);
  const geoEnabled = !!boot.premiumUi?.geo?.enabled;
  useEffect(() => {
    if (tab === "premium-geo" && !geoEnabled) {
      setTab(boot.settings?.[0]?.id || "configpress");
    }
  }, [tab, geoEnabled, boot.settings]);
  return (
    <>
      <Title
        title={t("AAM Settings")}
        description={t(
          "Site-wide configuration. These settings do not belong to the selected role or user.",
        )}
        icon="admin-settings"
        className="ar-settings-title"
      >
        <ActionTooltip
          text={t("Remove all AAM settings and restore defaults")}
          disabled={busy || !boot.caps.manage_settings}
        >
          <Button
            variant="secondary"
            isDestructive
            className="ar-settings-reset-button"
            disabled={busy || !boot.caps.manage_settings}
            onClick={() => setResetOpen(true)}
          >
            <span
              className="dashicons dashicons-image-rotate"
              aria-hidden="true"
            />
            {t("Reset all settings")}
          </Button>
        </ActionTooltip>
      </Title>
      <div className="ar-tabs">
        {boot.settings.map((group) => (
          <Button
            key={group.id}
            variant={tab === group.id ? "primary" : "tertiary"}
            onClick={() => setTab(group.id)}
          >
            {plain(group.title)}
          </Button>
        ))}
        {geoEnabled && (
          <Button
            variant={tab === "premium-geo" ? "primary" : "tertiary"}
            onClick={() => setTab("premium-geo")}
          >
            {t("Geo Lookup")}
          </Button>
        )}
        <Button
          variant={tab === "configpress" ? "primary" : "tertiary"}
          onClick={() => setTab("configpress")}
        >
          {t("ConfigPress")}
        </Button>
        <Button
          variant={tab === "transfer" ? "primary" : "tertiary"}
          onClick={() => setTab("transfer")}
        >
          {t("Import / Export")}
        </Button>
      </div>
      <div key={resetVersion} className="ar-settings-panel">
        {tab === "premium-geo" && geoEnabled ? (
          <GeoSettings
            key={JSON.stringify(boot.premiumUi.geo)}
            initial={boot.premiumUi.geo}
          />
        ) : tab === "configpress" ? (
          <ConfigPress />
        ) : tab === "transfer" ? (
          <Transfer />
        ) : tab === "settings-services" ? (
          boot.settings
            .filter((group) => group.id === tab)
            .map((group) => <ServiceSettings key={group.id} group={group} />)
        ) : (
          boot.settings
            .filter((group) => group.id === tab)
            .map((group) => <SettingGroup key={group.id} group={group} />)
        )}
      </div>
      {resetOpen && (
        <Confirm
          title={t("Reset all AAM settings")}
          confirmLabel={t("Reset all settings")}
          cancelLabel={t("Keep current settings")}
          impact={t(
            "Default settings will take effect across the site. This change cannot be undone.",
          )}
          onClose={() => setResetOpen(false)}
          onConfirm={async () => {
            const saved = await write(
              "/aam/v2/core/reset",
              "DELETE",
              undefined,
              false,
            );
            if (saved) setResetVersion((version) => version + 1);
            return saved;
          }}
        >
          {t("Remove all AAM settings across this site")}?
        </Confirm>
      )}
    </>
  );
}
function GeoSettings({ initial }: any) {
  const write = useWrite();
  const { busy, boot } = useWorkspace();
  const [value, setValue] = useState(initial);
  return (
    <form
      className="ar-geo-settings"
      onSubmit={async (event) => {
        event.preventDefault();
        await write("/premium/geo-settings", "POST", value, false);
      }}
    >
      <div className="ar-geo-heading">
        <span className="dashicons dashicons-location-alt" aria-hidden="true" />
        <div>
          <h3>{t("Location lookup")}</h3>
          <p>
            {t(
              "Turn visitor IP addresses into location data for access rules in Posts & Terms, URL Access, and JSON policies.",
            )}
          </p>
        </div>
      </div>
      <div className="ar-geo-guide">
        <div>
          <strong>{t("Use location in access rules")}</strong>
          <p>
            {t("For example,")} <code>{"${GEO.country_code}"}</code>{" "}
            {t(
              "reads the visitor's country in a JSON policy. Common location fields are normalized across providers.",
            )}
          </p>
        </div>
        <div className="ar-geo-guide-links">
          <a
            href="https://aamportal.com/article/manage-access-to-wp-website-based-on-ip-or-geo-location/"
            target="_blank"
            rel="noopener noreferrer"
          >
            {t("Setup guide")} <span aria-hidden="true">↗</span>
          </a>
          <a
            href="https://aamportal.com/reference/json-access-policy/marker/geo/"
            target="_blank"
            rel="noopener noreferrer"
          >
            {t("GEO marker reference")} <span aria-hidden="true">↗</span>
          </a>
        </div>
      </div>
      <div className="ar-geo-grid">
        <SelectControl
          label={t("API provider")}
          help={t(
            "Coverage, returned fields, and usage limits vary by provider.",
          )}
          value={value.adapter}
          options={[
            { value: "ipapi", label: "ipapi.co" },
            { value: "ip-api", label: "ip-api.com" },
            { value: "ipstack", label: "ipstack.com" },
            { value: "geoapify", label: "geoapify.com" },
            { value: "ipgeolocation", label: "ipgeolocation.io" },
          ]}
          onChange={(adapter) => setValue({ ...value, adapter })}
        />
        <TextControl
          label={t("Provider API key")}
          help={t("Enter a key if your chosen provider requires one.")}
          type="password"
          autoComplete="off"
          value={value.api_key || ""}
          onChange={(api_key) => setValue({ ...value, api_key })}
        />
        <TextControl
          label={t("Test IP address")}
          help={t(
            "Uses this IP instead of each visitor's IP for lookups. Clear it when testing is finished.",
          )}
          value={value.test_ip || ""}
          placeholder="8.8.8.8"
          onChange={(test_ip) => setValue({ ...value, test_ip })}
        />
      </div>
      <Button
        variant="primary"
        type="submit"
        disabled={busy || !boot.caps.manage_configs}
      >
        {t("Save Geo Lookup settings")}
      </Button>
    </form>
  );
}
function ServiceSettings({ group }: any) {
  const write = useWrite();
  const { boot, busy } = useWorkspace();
  const [pending, setPending] = useState({} as Record<string, boolean>);
  const items = arr(group.items).map((item: any) => ({
    key: item.setting,
    title: plain(item.title),
    description: plain(item.description || ""),
    checked: pending[item.setting] ?? !!item.status,
    label: t("Enabled"),
    disabled: busy || !boot.caps.manage_services,
    onChange: async (checked: boolean) => {
      setPending((current) => ({ ...current, [item.setting]: checked }));
      try {
        await write(
          "/config/" + encodeURIComponent(item.setting),
          "POST",
          { value: checked },
          false,
        );
      } finally {
        setPending((current) => {
          const next = { ...current };
          delete next[item.setting];
          return next;
        });
      }
    },
  }));

  return <SettingsBrowser items={items} kind="services" filterByStatus />;
}
function SettingGroup({ group }: any) {
  const write = useWrite();
  const { boot, busy } = useWorkspace();
  const items = Object.entries(group.items || {}).map(([key, item]: any) => ({
    key,
    title: plain(item.title),
    description: plain(item.description || ""),
    checked: !!item.value,
    label: plain(item.optionOn || t("Enabled")),
    disabled: busy || !boot.caps.manage_configs,
    onChange: (checked: boolean) =>
      write(
        "/config/" + encodeURIComponent(key),
        "POST",
        {
          value: checked ? (item.valueOn ?? true) : (item.valueOff ?? false),
        },
        false,
      ),
  }));

  return <SettingsBrowser items={items} kind="settings" />;
}
function SettingsBrowser({ items, kind, filterByStatus = false }: any) {
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("all");
  const [page, setPage] = useState(0);
  const pageSize = 8;
  const query = search.trim().toLocaleLowerCase();
  const searched = items.filter((item: any) => {
    if (!query) return true;
    const haystack = `${item.title} ${item.description} ${item.key} ${item.key.replace(/[._-]/g, " ")}`;
    return haystack.toLocaleLowerCase().includes(query);
  });
  const enabledCount = searched.filter((item: any) => item.checked).length;
  const filtered =
    filterByStatus && status !== "all"
      ? searched.filter((item: any) => item.checked === (status === "enabled"))
      : searched;
  const currentPage = Math.min(
    page,
    Math.max(0, Math.ceil(filtered.length / pageSize) - 1),
  );
  const visible = filtered.slice(
    currentPage * pageSize,
    (currentPage + 1) * pageSize,
  );

  return (
    <section
      className="ar-setting-browser"
      aria-label={kind === "services" ? t("Services") : t("Settings")}
    >
      {items.length > 0 && (
        <div className="ar-setting-browser-toolbar">
          <div className="ar-setting-browser-search">
            <Search
              compact
              value={search}
              label={
                kind === "services" ? t("Find a service") : t("Find a setting")
              }
              onChange={(value: string) => {
                setSearch(value);
                setPage(0);
              }}
            />
          </div>
          <div className="ar-setting-browser-tools">
            {filterByStatus && (
              <div
                className="ar-setting-browser-filters"
                role="group"
                aria-label={t("Filter services by status")}
              >
                {[
                  { value: "all", label: t("All"), count: searched.length },
                  {
                    value: "enabled",
                    label: t("Enabled"),
                    count: enabledCount,
                  },
                  {
                    value: "disabled",
                    label: t("Disabled"),
                    count: searched.length - enabledCount,
                  },
                ].map((choice) => (
                  <button
                    type="button"
                    key={choice.value}
                    className={status === choice.value ? "is-active" : ""}
                    aria-pressed={status === choice.value}
                    onClick={() => {
                      setStatus(choice.value);
                      setPage(0);
                    }}
                  >
                    {choice.label} <span>{choice.count}</span>
                  </button>
                ))}
              </div>
            )}
            <span className="ar-setting-browser-count">
              {filtered.length}{" "}
              {filtered.length === 1
                ? kind === "services"
                  ? t("service")
                  : t("setting")
                : kind === "services"
                  ? t("services")
                  : t("settings")}
            </span>
          </div>
        </div>
      )}
      {visible.length ? (
        <div className="ar-setting-list">
          {visible.map((item: any) => (
            <div className="ar-setting" key={item.key}>
              <div className="ar-setting-copy">
                <h3>{item.title}</h3>
                {item.description && <p>{item.description}</p>}
              </div>
              <ToggleControl
                className="ar-toggle"
                label={item.label}
                checked={item.checked}
                disabled={item.disabled}
                onChange={item.onChange}
              />
            </div>
          ))}
        </div>
      ) : (
        <div className="ar-setting-browser-empty">
          <span className="dashicons dashicons-search" aria-hidden="true" />
          <strong>
            {items.length
              ? kind === "services"
                ? t("No matching services")
                : t("No matching settings")
              : kind === "services"
                ? t("No services in this section yet")
                : t("No settings in this section yet")}
          </strong>
          {items.length > 0 && (
            <Button
              variant="secondary"
              onClick={() => {
                setSearch("");
                setStatus("all");
                setPage(0);
              }}
            >
              {t("Clear filters")}
            </Button>
          )}
        </div>
      )}
      {filtered.length > pageSize && (
        <div className="ar-setting-browser-footer">
          <Pager
            page={currentPage}
            setPage={setPage}
            total={filtered.length}
            pageSize={pageSize}
          />
        </div>
      )}
    </section>
  );
}
function ConfigPress() {
  const r = useResource("/configpress", false);
  return (
    <Resource resource={r}>
      {(data) => (
        <ConfigEditor
          key={JSON.stringify(data)}
          initial={typeof data === "string" ? data : data.ini || ""}
        />
      )}
    </Resource>
  );
}
function ConfigEditor({ initial }: any) {
  const [value, setValue] = useState(initial);
  const textarea = useRef(null);
  const write = useWrite();
  const { busy, boot } = useWorkspace();
  useEffect(() => {
    if (!window.aamReactIniEditorEnabled || !wp.codeEditor || !wp.CodeMirror)
      return;
    registerIniMode(wp.CodeMirror);
    const editor = wp.codeEditor.initialize(textarea.current, {
      codemirror: { mode: "aam-ini", lint: false, indentWithTabs: false },
    }).codemirror;
    const onChange = () => setValue(editor.getValue());
    editor.on("change", onChange);
    return () => {
      editor.off("change", onChange);
      editor.toTextArea();
    };
  }, []);
  return (
    <form
      onSubmit={(e) => {
        e.preventDefault();
        write("/configpress", "POST", { ini: value }, false);
      }}
    >
      <div className="ar-ini-editor">
        <label htmlFor="ar-configpress-ini">{t("ConfigPress (INI)")}</label>
        <textarea
          id="ar-configpress-ini"
          ref={textarea}
          defaultValue={initial}
          rows={18}
          spellCheck={false}
          onChange={(e) => setValue(e.currentTarget.value)}
        />
      </div>
      <Button
        variant="primary"
        type="submit"
        disabled={busy || !boot.caps.manage_configs}
      >
        {t("Save configuration")}
      </Button>
    </form>
  );
}
function Transfer() {
  const { write, busy, setMessage } = useWorkspace();
  const [payload, setPayload] = useState(null);
  const [selectedFile, setSelectedFile] = useState("");
  const [exporting, setExporting] = useState(false);
  return (
    <div className="ar-transfer-list">
      <section
        className="ar-transfer-card ar-transfer-export"
        aria-labelledby="ar-transfer-export-title"
      >
        <span
          className="ar-transfer-icon dashicons dashicons-download"
          aria-hidden="true"
        />
        <div className="ar-transfer-body">
          <h3 id="ar-transfer-export-title">{t("Export settings")}</h3>
          <p>
            {t(
              "Download a JSON copy of your AAM settings. Keep it as a backup before making broad changes.",
            )}
          </p>
          <div className="ar-transfer-action">
            <Button
              variant="secondary"
              disabled={exporting}
              onClick={async () => {
                setExporting(true);
                try {
                  download(
                    "aam-settings.json",
                    await request("/aam/v2/core/export"),
                  );
                } catch (e) {
                  setMessage({ status: "error", text: e.message });
                } finally {
                  setExporting(false);
                }
              }}
            >
              {exporting ? t("Preparing download…") : t("Download JSON backup")}
            </Button>
          </div>
        </div>
      </section>

      <section
        className="ar-transfer-card ar-transfer-import"
        aria-labelledby="ar-transfer-import-title"
      >
        <span
          className="ar-transfer-icon dashicons dashicons-upload"
          aria-hidden="true"
        />
        <div className="ar-transfer-body">
          <h3 id="ar-transfer-import-title">{t("Import settings")}</h3>
          <p>
            {t(
              "Choose a JSON backup to replace the current AAM settings. You can review the choice before confirming.",
            )}
          </p>
          <div className="ar-transfer-upload">
            <label htmlFor="ar-transfer-file">
              {t("Choose a JSON backup file")}
            </label>
            <input
              id="ar-transfer-file"
              type="file"
              accept=".json,application/json"
              disabled={busy}
              onChange={async (e) => {
                const input = e.currentTarget;
                const file = input.files?.[0];
                if (!file) return;
                setSelectedFile(file.name);
                try {
                  const parsed = JSON.parse(await file.text());
                  if (!parsed.dataset)
                    throw Error(t("Missing dataset in settings file."));
                  setPayload(parsed);
                } catch (e) {
                  setPayload(null);
                  setMessage({ status: "error", text: e.message });
                } finally {
                  input.value = "";
                }
              }}
            />
            {selectedFile && (
              <p className="ar-transfer-filename">{selectedFile}</p>
            )}
          </div>
        </div>
      </section>

      {payload && (
        <Confirm
          title={t("Replace AAM settings")}
          confirmLabel={t("Replace settings")}
          cancelLabel={t("Keep current settings")}
          impact={t(
            "The current site configuration will be overwritten. Keep a backup if you may need to restore it later.",
          )}
          onClose={() => {
            setPayload(null);
            setSelectedFile("");
          }}
          onConfirm={async () => {
            const saved = await write(
              "/aam/v2/core/import",
              "POST",
              { dataset: payload.dataset },
              false,
            );
            if (saved) setSelectedFile("");
            return saved;
          }}
        >
          {t("Replace all AAM settings with the selected backup")}{" "}
          <strong>{selectedFile}</strong>?
        </Confirm>
      )}
    </div>
  );
}
function AuditIssueMessage({ issue }: { issue: any }) {
  const fallback = issue.code
    ? String(issue.code).replace(/_/g, " ").toLowerCase()
    : t("Audit finding");
  const message = plain(
    issue.message || issue.description || issue.title || fallback,
  );
  const name = issue.user_profile?.name && plain(issue.user_profile.name);
  const position = name ? message.indexOf(name) : -1;

  if (position < 0 || !issue.user_profile?.url) return <>{message}</>;

  return (
    <>
      {message.slice(0, position)}
      <a href={issue.user_profile.url}>{name}</a>
      {message.slice(position + name.length)}
    </>
  );
}
function auditTone(type: string) {
  if (["critical", "error"].includes(type)) return "danger";
  if (type === "warning") return "warning";
  if (type === "notice") return "notice";
  if (type === "ok") return "success";
  return "neutral";
}
function auditSeverityLabel(type: string) {
  if (type === "critical") return t("Critical");
  if (type === "error") return t("Error");
  if (type === "warning") return t("Warning");
  if (type === "notice") return t("Notice");
  return t("Finding");
}
function AuditStep({
  step,
  result,
  running,
  busy,
  onReview,
  onAcknowledge,
}: any) {
  const [visibleCount, setVisibleCount] = useState(10);
  const issues = arr(result?.issues);
  const openCount = issues.filter(
    (issue) => !["acknowledged", "resolved"].includes(issue.review?.status),
  ).length;
  const completed = !!result?.is_completed;
  const fallbackStatus =
    ["error", "critical", "warning", "notice"].find((type) =>
      issues.some((issue) => issue.type === type),
    ) || "ok";
  const status = running
    ? "scanning"
    : completed
      ? result?.check_status || fallbackStatus
      : "pending";
  const statusLabel = running
    ? t("Scanning…")
    : completed
      ? {
          ok: t("Passed"),
          error: t("Error"),
          critical: t("Critical"),
          warning: t("Warning"),
          notice: t("Notice"),
        }[status] || t("Completed")
      : t("Not scanned");
  const icon =
    status === "ok"
      ? "yes-alt"
      : status === "critical" || status === "error"
        ? "warning"
        : status === "warning"
          ? "flag"
          : status === "notice"
            ? "info-outline"
            : status === "scanning"
              ? "update"
              : "shield";

  return (
    <details className={`ar-audit-step is-${status}`}>
      <summary>
        <span
          className={`ar-audit-step-icon dashicons dashicons-${icon}`}
          aria-hidden="true"
        />
        <span className="ar-audit-step-copy">
          <span className="ar-audit-step-category">
            {plain(step.category || t("Security check"))}
          </span>
          <strong>{plain(step.title)}</strong>
          <span className="ar-audit-step-hint">
            {completed
              ? issues.length
                ? `${issues.length} ${issues.length === 1 ? t("finding") : t("findings")}${openCount ? ` · ${openCount} ${t("to review")}` : ` · ${t("all reviewed")}`}`
                : t("No issues found")
              : t("What this check looks for")}
          </span>
        </span>
        <span className="ar-audit-step-meta">
          <Badge tone={auditTone(status)}>{statusLabel}</Badge>
          <span
            className="dashicons dashicons-arrow-down-alt2 ar-audit-step-chevron"
            aria-hidden="true"
          />
        </span>
      </summary>
      <div className="ar-audit-step-content">
        <div className="ar-audit-step-about">
          <span className="dashicons dashicons-lightbulb" aria-hidden="true" />
          <p>
            {plain(step.description)}{" "}
            {step.article && (
              <a href={step.article} target="_blank" rel="noopener noreferrer">
                {t("Learn why this matters")} <span aria-hidden="true">↗</span>
              </a>
            )}
          </p>
        </div>
        {completed && !issues.length && (
          <div className="ar-audit-step-clear">
            <span className="dashicons dashicons-yes-alt" aria-hidden="true" />
            {t("This check found no issues in the latest audit.")}
          </div>
        )}
        {!!issues.length && (
          <div className="ar-audit-findings-table-wrap">
            <div className="ar-audit-findings-heading">
              <strong>{t("Identified issues")}</strong>
              <div className="ar-audit-findings-heading-actions">
                <span>
                  {issues.length}{" "}
                  {issues.length === 1 ? t("finding") : t("findings")}
                </span>
                {openCount > 0 && (
                  <Button
                    variant="tertiary"
                    disabled={busy}
                    onClick={() => onAcknowledge(step.step, openCount)}
                  >
                    {t("Acknowledge step")}
                  </Button>
                )}
              </div>
            </div>
            <div className="ar-audit-findings-scroll">
              <table className="ar-audit-findings-table">
                <thead>
                  <tr>
                    <th scope="col">{t("Severity")}</th>
                    <th scope="col">{t("Finding")}</th>
                    <th scope="col">{t("Review")}</th>
                  </tr>
                </thead>
                <tbody>
                  {issues.slice(0, visibleCount).map((issue, index) => (
                    <tr key={`${issue.code || "issue"}-${index}`}>
                      <td>
                        <Badge tone={auditTone(issue.type || "")}>
                          {auditSeverityLabel(issue.type || "")}
                        </Badge>
                      </td>
                      <td>
                        <AuditIssueMessage issue={issue} />
                        {issue.review?.note && (
                          <span
                            className="ar-audit-issue-note"
                            title={plain(issue.review.note)}
                          >
                            {plain(issue.review.note)}
                          </span>
                        )}
                      </td>
                      <td className="ar-audit-review-cell">
                        <span
                          className={`ar-audit-review-state is-${issue.review?.status || "open"}`}
                        >
                          {issue.review?.status === "acknowledged"
                            ? t("Acknowledged")
                            : issue.review?.status === "resolved"
                              ? t("Resolved")
                              : t("Open")}
                        </span>
                        <Button
                          variant="tertiary"
                          aria-label={`${t("Review finding")}: ${plain(issue.message || issue.code || "")}`}
                          disabled={busy}
                          onClick={() => onReview(step.step, issue)}
                        >
                          {issue.review ? t("Edit") : t("Review")}
                        </Button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            {issues.length > visibleCount && (
              <div className="ar-audit-findings-more">
                <span>
                  {t("Showing")} {visibleCount} {t("of")} {issues.length}
                </span>
                <Button
                  variant="secondary"
                  onClick={() => setVisibleCount((count) => count + 10)}
                >
                  {t("Show 10 more")}
                </Button>
              </div>
            )}
          </div>
        )}
      </div>
    </details>
  );
}
function ExecutiveSummary({ summary }: { summary: any }) {
  const sections = [
    {
      key: "critical",
      title: t("Critical findings"),
      icon: "warning",
      tone: "critical",
    },
    {
      key: "concerns",
      title: t("Additional concerns"),
      icon: "visibility",
      tone: "concerns",
    },
    {
      key: "recommendations",
      title: t("Recommended next steps"),
      icon: "yes-alt",
      tone: "recommendations",
    },
  ];

  return (
    <section className="ar-executive-report" id="ar-executive-report">
      <div className="ar-executive-report-heading">
        <span className="ar-executive-report-mark" aria-hidden="true">
          <span className="dashicons dashicons-media-text" />
        </span>
        <div>
          <span className="ar-audit-ai-eyebrow">
            {t("YOUR EXECUTIVE SUMMARY")}
          </span>
          <h3>{t("A clearer path to stronger access security")}</h3>
        </div>
        <Badge tone="success">{t("Ready to review")}</Badge>
      </div>

      {summary.summary && (
        <div className="ar-executive-overview">
          <span
            className="dashicons dashicons-format-quote"
            aria-hidden="true"
          />
          <p>{plain(summary.summary)}</p>
        </div>
      )}

      <div className="ar-executive-sections">
        {sections
          .filter((section) => summary[section.key].length)
          .map((section) => (
            <section
              className={`ar-executive-section ar-executive-section-${section.tone}`}
              key={section.key}
            >
              <div className="ar-executive-section-heading">
                <span
                  className={`dashicons dashicons-${section.icon}`}
                  aria-hidden="true"
                />
                <h4>{section.title}</h4>
                <span className="ar-executive-count">
                  {summary[section.key].length}
                </span>
              </div>
              <ol>
                {summary[section.key].map((item: string, index: number) => (
                  <li key={index}>{plain(item)}</li>
                ))}
              </ol>
            </section>
          ))}
      </div>

      {!!summary.references.length && (
        <div className="ar-executive-references">
          <strong>{t("Further reading")}</strong>
          <div>
            {summary.references.map((url: string) => (
              <a key={url} href={url} target="_blank" rel="noopener noreferrer">
                {url.replace(/^https:\/\//, "").replace(/\/$/, "")}
                <span aria-hidden="true"> ↗</span>
              </a>
            ))}
          </div>
        </div>
      )}

      <div className="ar-executive-next">
        <div>
          <strong>{t("Ready to act on these findings?")}</strong>
          <p>
            {t(
              "Review the detailed checks below or discuss the plan with an AAM specialist.",
            )}
          </p>
        </div>
        <div className="ar-executive-next-actions">
          <a href="#ar-audit-findings">
            {t("Review detailed findings")} <span aria-hidden="true">↓</span>
          </a>
          <a
            href="https://aamportal.com/consultation/security-audit"
            target="_blank"
            rel="noopener noreferrer"
          >
            {t("Schedule a free consultation")}{" "}
            <span aria-hidden="true">↗</span>
          </a>
        </div>
      </div>
    </section>
  );
}
export function Audit() {
  const { boot, busy, setBusy, setMessage, refreshContext } = useWorkspace();
  const [report, setReport] = useState(boot.audit?.report || {});
  const [score, setScore] = useState(boot.audit?.score ?? null);
  const [running, setRunning] = useState("");
  const [summary, setSummary] = useState(
    normalizeAuditSummary(boot.audit?.summary),
  );
  const [consentOpen, setConsentOpen] = useState(false);
  const [generating, setGenerating] = useState(false);
  const [reviewTarget, setReviewTarget] = useState(null);
  const [reviewStatus, setReviewStatus] = useState("acknowledged");
  const [reviewNote, setReviewNote] = useState("");
  const [reviewBusy, setReviewBusy] = useState(false);
  const cancelled = useRef(false);
  useEffect(
    () => () => {
      cancelled.current = true;
    },
    [],
  );
  useEffect(() => setScore(boot.audit?.score ?? null), [boot.audit?.score]);
  const steps = arr(boot.audit?.steps);
  const hasReport = steps.some((step) => report?.[step.step]?.is_completed);
  const findingCount = steps.reduce(
    (count, step) => count + arr(report?.[step.step]?.issues || []).length,
    0,
  );
  const openCount = steps.reduce(
    (count, step) =>
      count +
      arr(report?.[step.step]?.issues).filter(
        (issue) => !["acknowledged", "resolved"].includes(issue.review?.status),
      ).length,
    0,
  );
  const openIssueReview = (step: string, issue: any) => {
    setReviewTarget({ scope: "issue", step, issue });
    setReviewStatus(issue.review?.status || "acknowledged");
    setReviewNote(issue.review?.note || "");
  };
  const openBulkReview = (scope: string, count: number, step = "") => {
    setReviewTarget({ scope, step, count });
    setReviewStatus("acknowledged");
    setReviewNote("");
  };
  const saveReview = async () => {
    if (!reviewTarget || reviewBusy) return;
    setReviewBusy(true);
    try {
      const response: any = await mutate("/aam/v2/audit/review", "POST", {
        scope: reviewTarget.scope,
        status: reviewTarget.scope === "issue" ? reviewStatus : "acknowledged",
        step: reviewTarget.step || "",
        issue_id: reviewTarget.issue?.id || "",
        note: reviewNote.trim(),
      });
      if (typeof response?.score === "number") setScore(response.score);
      const updated = response?.updated || {};
      setReport((previous) =>
        Object.fromEntries(
          Object.entries(previous).map(([step, result]: [string, any]) => [
            step,
            {
              ...result,
              issues: arr(result?.issues).map((issue) =>
                Object.prototype.hasOwnProperty.call(updated, issue.id)
                  ? { ...issue, review: updated[issue.id] }
                  : issue,
              ),
            },
          ]),
        ),
      );
      setReviewTarget(null);
      setMessage({
        status: "success",
        text:
          reviewTarget.scope === "issue"
            ? reviewStatus === "resolved"
              ? t("Finding marked resolved.")
              : reviewStatus === "open"
                ? t("Finding kept open.")
                : t("Finding acknowledged.")
            : response.count
              ? `${response.count} ${response.count === 1 ? t("finding acknowledged.") : t("findings acknowledged.")}`
              : t("All findings in this selection were already reviewed."),
      });
    } catch (error) {
      setMessage({
        status: "error",
        text: error.message || t("The review could not be saved."),
      });
    } finally {
      setReviewBusy(false);
    }
  };
  const run = async () => {
    cancelled.current = false;
    setBusy(true);
    setReport({});
    setScore(null);
    setSummary(null);
    try {
      let first = true;
      for (const step of steps) {
        if (cancelled.current) break;
        setRunning(step.step);
        let completed = false;
        let iterations = 0;
        while (!completed && !cancelled.current) {
          if (++iterations > 1000)
            throw Error(t("Audit step did not finish. Please retry."));
          const response: any = await mutate("/aam/v2/audit", "POST", {
            step: step.step,
            reset: first,
          });
          first = false;
          completed = !!response.is_completed;
          setReport((prev) => ({ ...prev, [step.step]: response }));
        }
      }
      if (!cancelled.current) {
        setMessage({ status: "success", text: t("Security audit completed.") });
        await refreshContext();
      }
    } catch (e) {
      setMessage({ status: "error", text: e.message });
    } finally {
      setRunning("");
      setBusy(false);
    }
  };
  const generateSummary = async () => {
    setConsentOpen(false);
    setGenerating(true);
    try {
      const response: any = await request("/aam/v2/audit/summary");
      const result = normalizeAuditSummary(response?.results);
      if (response?.status !== "success" || !result) {
        throw new Error(
          response?.reason ||
            t("The summary could not be prepared. Please try again."),
        );
      }
      if (!cancelled.current) {
        setSummary(result);
        setMessage({
          status: "success",
          text: t("Your executive summary is ready."),
        });
      }
    } catch (error) {
      if (!cancelled.current) {
        setMessage({
          status: "error",
          text:
            error.message ||
            t("The summary could not be prepared. Please try again."),
        });
      }
    } finally {
      if (!cancelled.current) setGenerating(false);
    }
  };
  return (
    <>
      <Title
        title={t("Security Audit")}
        description={t(
          "Find excessive privileges and access-governance issues across this site.",
        )}
        icon="shield"
      >
        <ActionTooltip text={t("Download results")} disabled={busy}>
          <Button
            variant="secondary"
            className="ar-icon-action"
            aria-label={t("Download results")}
            disabled={busy}
            onClick={() => download("aam-security-audit.json", report)}
          >
            <span className="dashicons dashicons-download" aria-hidden="true" />
          </Button>
        </ActionTooltip>
        <Button
          variant="primary"
          disabled={busy || generating || reviewBusy}
          onClick={run}
        >
          {running ? t("Scanning…") : t("Run security audit")}
        </Button>
      </Title>
      <section
        className={`ar-audit-hero ${summary ? "has-summary" : ""}`}
        aria-labelledby="ar-audit-ai-title"
      >
        <div className="ar-audit-score-block">
          <span className="ar-audit-score-label">
            {t("ACCESS SECURITY SCORE")}
          </span>
          <span className="ar-score" aria-label={t("Access security score")}>
            {running ? "…" : (score ?? "—")}
            {score != null && !running && <small>/ 100</small>}
          </span>
          <span className="ar-audit-score-note">
            {t("A guide for review, not a security guarantee")}
          </span>
        </div>
        <div className="ar-audit-ai-copy">
          <span className="ar-audit-ai-eyebrow">
            <span
              className="dashicons dashicons-lightbulb"
              aria-hidden="true"
            />
            {t("AI-ASSISTED AUDIT SUMMARY")}
          </span>
          <h3 id="ar-audit-ai-title">
            {summary
              ? t("Your next steps are ready")
              : hasReport
                ? t("Know what to fix first")
                : t("Find the risks. Then get a plan.")}
          </h3>
          <p>
            {summary
              ? t(
                  "Turn the latest findings into clear priorities and practical next steps.",
                )
              : hasReport
                ? t(
                    "Get a plain-language overview and a prioritized plan for these findings.",
                  )
                : t(
                    "Run a security audit, then see what matters most and what to do next.",
                  )}
          </p>
        </div>
        <div className="ar-audit-ai-action">
          {hasReport && (
            <span className="ar-audit-ai-count">
              {findingCount === 1
                ? t("1 finding ready to review")
                : `${findingCount} ${t("findings ready to review")}`}
            </span>
          )}
          <Button
            variant="primary"
            disabled={busy || generating}
            onClick={hasReport ? () => setConsentOpen(true) : run}
          >
            {generating ? (
              <>
                <Spinner /> {t("Preparing your summary…")}
              </>
            ) : summary ? (
              t("Refresh my summary")
            ) : hasReport ? (
              t("Get my executive summary")
            ) : (
              t("Run security audit")
            )}
          </Button>
          {hasReport && !summary && (
            <small>{t("Review what is shared before anything is sent.")}</small>
          )}
        </div>
      </section>
      {summary && <ExecutiveSummary summary={summary} />}
      <section id="ar-audit-findings" aria-label={t("Detailed audit checks")}>
        <div className="ar-audit-checks-toolbar">
          <div>
            <strong>{t("Audit checks")}</strong>
            {findingCount > 0 && (
              <span>
                {openCount} {t("findings to review")}
              </span>
            )}
          </div>
          {openCount > 0 && (
            <Button
              variant="secondary"
              disabled={busy || reviewBusy}
              onClick={() => openBulkReview("all", openCount)}
            >
              {t("Acknowledge all")}
            </Button>
          )}
        </div>
        {steps.map((step) => (
          <AuditStep
            key={step.step}
            step={step}
            result={report?.[step.step]}
            running={running === step.step}
            busy={busy || reviewBusy}
            onReview={openIssueReview}
            onAcknowledge={(stepId: string, count: number) =>
              openBulkReview("step", count, stepId)
            }
          />
        ))}
      </section>
      {reviewTarget && (
        <Modal
          title={
            reviewTarget.scope === "issue"
              ? t("Review finding")
              : reviewTarget.scope === "step"
                ? t("Acknowledge this check")
                : t("Acknowledge all findings")
          }
          onRequestClose={() => !reviewBusy && setReviewTarget(null)}
          className="ar-modal ar-audit-review-modal"
        >
          {reviewTarget.scope === "issue" ? (
            <div className="ar-audit-review-finding">
              <Badge tone={auditTone(reviewTarget.issue.type || "")}>
                {auditSeverityLabel(reviewTarget.issue.type || "")}
              </Badge>
              <p>
                <AuditIssueMessage issue={reviewTarget.issue} />
              </p>
            </div>
          ) : (
            <p className="ar-audit-review-bulk-intro">
              {reviewTarget.count}{" "}
              {t(
                "open findings will be acknowledged. Already reviewed findings keep their current status.",
              )}
            </p>
          )}
          {reviewTarget.scope === "issue" && (
            <div
              className="ar-audit-review-choices"
              role="group"
              aria-label={t("Review status")}
            >
              {[
                { value: "acknowledged", label: t("Acknowledge"), icon: "yes" },
                {
                  value: "resolved",
                  label: t("Mark resolved"),
                  icon: "yes-alt",
                },
                { value: "open", label: t("Keep open"), icon: "update" },
              ].map((choice) => (
                <button
                  type="button"
                  key={choice.value}
                  className={`ar-audit-review-choice ${reviewStatus === choice.value ? "is-selected" : ""}`}
                  aria-pressed={reviewStatus === choice.value}
                  disabled={reviewBusy}
                  onClick={() => setReviewStatus(choice.value)}
                >
                  <span
                    className={`dashicons dashicons-${choice.icon}`}
                    aria-hidden="true"
                  />
                  {choice.label}
                </button>
              ))}
            </div>
          )}
          <TextareaControl
            label={t("Note (optional)")}
            value={reviewNote}
            maxLength={2000}
            rows={3}
            onChange={setReviewNote}
            help={t(
              "Record why you made this decision. Notes stay on this site.",
            )}
          />
          <p className="ar-audit-review-explainer">
            {t(
              "Acknowledged findings still count toward the score. Resolved findings do not; they reopen if a new scan detects them again.",
            )}
          </p>
          <div className="ar-audit-consent-actions">
            <Button
              variant="secondary"
              disabled={reviewBusy}
              onClick={() => setReviewTarget(null)}
            >
              {t("Cancel")}
            </Button>
            <Button
              variant="primary"
              disabled={reviewBusy}
              onClick={saveReview}
            >
              {reviewBusy
                ? t("Saving…")
                : reviewTarget.scope === "issue"
                  ? t("Save review")
                  : t("Acknowledge findings")}
            </Button>
          </div>
        </Modal>
      )}
      {consentOpen && (
        <Modal
          title={t("Prepare your executive summary")}
          onRequestClose={() => setConsentOpen(false)}
          className="ar-modal ar-audit-consent"
        >
          <div className="ar-audit-consent-intro">
            <span className="dashicons dashicons-shield" aria-hidden="true" />
            <div>
              <strong>{t("Turn findings into a practical plan")}</strong>
              <p>
                {t(
                  "AAM's AI will analyze this audit and return an overview, key concerns, and recommended next steps.",
                )}
              </p>
            </div>
          </div>
          <div className="ar-audit-consent-details">
            <h3>{t("What will be shared")}</h3>
            <p>
              {t(
                "AAM sends selected audit findings and your installed plugin list (names, versions, paths, and active status) to its API. Findings can include user or role identifiers. A site-specific token and your AAM license key, if present, are included. The resulting summary is saved on this site.",
              )}
            </p>
          </div>
          <p className="ar-audit-consent-note">
            {t("Nothing is sent until you choose to generate the summary.")}
          </p>
          <div className="ar-audit-consent-actions">
            <Button variant="secondary" onClick={() => setConsentOpen(false)}>
              {t("Not now")}
            </Button>
            <Button variant="primary" onClick={generateSummary}>
              {t("Send audit data & generate summary")}
            </Button>
          </div>
        </Modal>
      )}
    </>
  );
}
