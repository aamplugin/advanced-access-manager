import {
  useState,
  useEffect,
  useWorkspace,
  useResource,
  useWrite,
  Resource,
  Title,
  EffectToggle,
  TableEmpty,
  Reset,
  Button,
  ActionTooltip,
  Modal,
  ModalIntro,
  SelectControl,
  TextControl,
  TextareaControl,
  ToggleControl,
  Confirm,
  t,
  plain,
  arr,
  base64Path,
} from "./core";
import { PagePicker } from "./page-picker";
import { needsPageSelection } from "./page-picker.mjs";
import { AccessOutcome, StateIcon } from "./resource-visuals";
import { ExpirationChoices } from "./expiration-choices";
import { expirationPayload } from "./expiration.mjs";
import { localDateTime } from "./date-time.mjs";
import { copyText } from "./clipboard.mjs";
import { ConditionalFields, conditionComplete } from "./premium-controls";
import {
  parseClaimsJson,
  parseClaimRows,
  claimsToRows,
} from "./jwt-editor.mjs";

function UrlBehaviorIcon({ row }: { row: any }) {
  const behavior =
    row.effect === "deny" ? row.redirect?.type || "default" : "allow";
  const details: any = {
    allow: { icon: "yes-alt", label: "Allow access" },
    default: { icon: "lock", label: "Deny access" },
    page_redirect: { icon: "admin-page", label: "Redirect to a page" },
    url_redirect: { icon: "migrate", label: "Redirect to a URL" },
    login_redirect: { icon: "admin-users", label: "Redirect to login" },
    custom_message: { icon: "admin-comments", label: "Show a custom message" },
    trigger_callback: { icon: "admin-plugins", label: "Run a callback" },
    conditional: { icon: "filter", label: "Conditional restriction" },
  };
  const item = details[behavior] || details.default;
  return (
    <span
      className={`ar-url-behavior ar-url-behavior-${behavior}`}
      role="img"
      aria-label={t(item.label)}
      title={t(item.label)}
    >
      <span className={`dashicons dashicons-${item.icon}`} aria-hidden="true" />
    </span>
  );
}
export function RedirectFields({
  value,
  onChange,
  required = true,
  showBehavior = true,
  conditionalOptions = null,
  types = [
    "default",
    "page_redirect",
    "url_redirect",
    "trigger_callback",
    "login_redirect",
    "custom_message",
  ],
}: any) {
  const set = (key: string, v: any) => onChange({ ...value, [key]: v });
  const names: any = {
    default: "Default behavior",
    page_redirect: "WordPress page",
    url_redirect: "URL",
    trigger_callback: "PHP callback",
    login_redirect: "Login page",
    custom_message: "Custom message",
    conditional: "Conditional restriction",
  };
  return (
    <>
      {showBehavior && (
        <SelectControl
          label={t("Behavior")}
          value={value.type || "default"}
          options={types.map((type) => ({
            value: type,
            label: t(names[type] || type),
          }))}
          onChange={(v) => onChange({ type: v })}
        />
      )}
      {value.type === "page_redirect" && (
        <PagePicker
          value={value.redirect_page_id}
          onChange={(pageId) => set("redirect_page_id", pageId)}
        />
      )}
      {value.type === "url_redirect" && (
        <TextControl
          label={t("Redirect URL")}
          help={t("Use a relative path or a URL on an allowed redirect host.")}
          value={value.redirect_url || ""}
          required={required}
          onChange={(v) => set("redirect_url", v)}
        />
      )}
      {value.type === "trigger_callback" && (
        <TextControl
          label={t("Registered PHP callback")}
          value={value.callback || ""}
          required={required}
          onChange={(v) => set("callback", v)}
        />
      )}
      {value.type === "custom_message" && (
        <TextareaControl
          label={t("Access denied message")}
          value={value.message || ""}
          required={required}
          onChange={(v) => set("message", v)}
        />
      )}
      {value.type === "conditional" && (
        <ConditionalFields
          value={value.condition}
          options={conditionalOptions}
          required={required}
          onChange={(condition) => set("condition", condition)}
        />
      )}
    </>
  );
}
export function Redirect({ id }: any) {
  const endpoints: any = {
    login_redirect: "login",
    logout_redirect: "logout",
    "404redirect": "not-found",
    redirect: "access-denied",
  };
  const names: any = {
    login_redirect: "Login Redirect",
    logout_redirect: "Logout Redirect",
    "404redirect": "404 Redirect",
    redirect: "Access Denied Redirect",
  };
  const resetLabels: any = {
    login_redirect: "Reset Login Redirect",
    logout_redirect: "Reset Logout Redirect",
    "404redirect": "Reset 404 Redirect",
    redirect: "Reset Access Denied Redirect",
  };
  const icons: any = {
    login_redirect: "migrate",
    logout_redirect: "exit",
    "404redirect": "warning",
    redirect: "randomize",
  };
  const descriptions: any = {
    login_redirect: "Choose what happens after this access level signs in.",
    logout_redirect: "Choose what happens after this access level signs out.",
    "404redirect": "Choose what happens when a requested page is not found.",
    redirect:
      "Choose a denied-access behavior for the website and admin area, or an API error response for REST, Ability, and MCP calls.",
  };
  const [area, setArea] = useState("frontend");
  const endpoint =
    "/redirect/" + endpoints[id] + (id === "redirect" ? "?area=" + area : "");
  const r = useResource(endpoint);
  return (
    <>
      <Title
        title={t(names[id])}
        description={t(descriptions[id])}
        icon={icons[id]}
        controls={
          id === "redirect" ? (
            <div
              className="ar-redirect-area-switch"
              role="group"
              aria-label={t("Access denied area")}
            >
              {[
                { value: "frontend", label: t("Frontend"), icon: "admin-site" },
                { value: "backend", label: t("Backend"), icon: "dashboard" },
                { value: "api", label: t("API"), icon: "rest-api" },
              ].map((option) => (
                <button
                  key={option.value}
                  type="button"
                  className={area === option.value ? "is-active" : ""}
                  aria-pressed={area === option.value}
                  onClick={() => setArea(option.value)}
                >
                  <span
                    className={`dashicons dashicons-${option.icon}`}
                    aria-hidden="true"
                  />
                  {option.label}
                </button>
              ))}
            </div>
          ) : null
        }
      >
        <Reset
          endpoint={endpoint}
          label={t(resetLabels[id])}
          serviceName={t(names[id])}
          iconOnly
        />
      </Title>
      <Resource resource={r}>
        {(data) => (
          <RedirectEditor
            key={JSON.stringify(data) + area}
            id={id}
            area={area}
            endpoint={endpoint}
            initial={data}
          />
        )}
      </Resource>
    </>
  );
}

function RedirectEditor({ id, area, endpoint, initial }: any) {
  const isApi = id === "redirect" && area === "api";
  const apiTypes = ["default", "custom_message", "trigger_callback"];
  const legacyApiRule =
    isApi && initial?.type && !apiTypes.includes(initial.type);
  const [value, setValue] = useState(
    legacyApiRule ? { type: "default" } : initial || { type: "default" },
  );
  const statusPresets = [401, 403, 404, 429, 500, 503];
  const statusDescriptions: Record<number, string> = {
    401: t("Unauthorized"),
    403: t("Forbidden"),
    404: t("Not found"),
    429: t("Rate limited"),
    500: t("Server error"),
    503: t("Unavailable"),
  };
  const initialCode = Number(initial?.http_status_code || 401);
  const [customStatus, setCustomStatus] = useState(
    !statusPresets.includes(initialCode),
  );
  const [customCode, setCustomCode] = useState(
    String(!statusPresets.includes(initialCode) ? initialCode : 451),
  );
  const write = useWrite();
  const { boot, busy, setMessage } = useWorkspace();
  const types = isApi
    ? apiTypes
    : ["default", "page_redirect", "url_redirect", "trigger_callback"];
  if (!isApi && (id === "redirect" || id === "404redirect"))
    types.push("login_redirect");
  if (!isApi && id === "redirect") types.push("custom_message");
  const options: any = {
    default: {
      label: "Default behavior",
      description: "Use the standard response.",
      icon: "admin-generic",
    },
    page_redirect: {
      label: "WordPress page",
      description: "Send people to a page on this site.",
      icon: "admin-page",
    },
    url_redirect: {
      label: "URL redirect",
      description: "Send people to a specific URL.",
      icon: "admin-links",
    },
    trigger_callback: {
      label: "PHP callback",
      description: "Run your registered PHP callback.",
      icon: "admin-plugins",
    },
    login_redirect: {
      label: "Login page",
      description: "Ask people to sign in first.",
      icon: "lock",
      detail:
        "People are sent to the WordPress login screen. No extra destination is needed.",
    },
    custom_message: {
      label: "Custom message",
      description: "Explain why access is denied.",
      icon: "admin-comments",
    },
  };
  if (isApi) {
    options.default.description = "Use the request's standard denial message.";
    options.custom_message.description =
      "Explain why the API request was denied.";
    options.trigger_callback.description =
      "Return a custom API error from PHP.";
  }
  const current = options[value.type || "default"] || options.default;
  const eventLabels: any = {
    login_redirect: "After sign-in",
    logout_redirect: "After sign-out",
    "404redirect": "Page not found",
    redirect: {
      frontend: "Frontend access denied",
      backend: "Backend access denied",
      api: "API access denied",
    }[area],
  };
  const defaultDetails: any = {
    login_redirect: "Send the user to the WordPress admin area after sign-in.",
    logout_redirect: "Use the site's default logout destination.",
    "404redirect": "Keep the standard page-not-found response.",
    redirect: isApi
      ? "Use the request's standard denial message for REST, Ability, and MCP calls."
      : "Show the standard Access Denied response.",
  };
  const detail =
    (value.type || "default") === "default"
      ? defaultDetails[id]
      : current.detail;
  return (
    <form
      className="ar-redirect-panel"
      onSubmit={(e) => {
        e.preventDefault();
        if (isApi && value.type === "trigger_callback") {
          if (!String(value.callback || "").trim()) {
            setMessage({
              status: "error",
              text: t("Enter a registered PHP callback before saving."),
            });
            return;
          }
          write(endpoint, "POST", {
            type: "trigger_callback",
            callback: String(value.callback).trim(),
          });
          return;
        }
        if (
          isApi &&
          value.type === "custom_message" &&
          !String(value.message || "").trim()
        ) {
          setMessage({
            status: "error",
            text: t("Enter an error message before saving."),
          });
          return;
        }
        if (needsPageSelection(value)) {
          setMessage({
            status: "error",
            text: t("Select a page before saving."),
          });
          return;
        }
        if (
          id === "redirect" &&
          ["default", "custom_message"].includes(value.type || "default")
        ) {
          const rawCode = customStatus
            ? customCode.trim()
            : String(value.http_status_code ?? 401);
          const code = Number(rawCode);
          if (!/^\d{3}$/.test(rawCode) || code < 400 || code > 599) {
            setMessage({
              status: "error",
              text: t("Enter an HTTP status code from 400 to 599."),
            });
            return;
          }
          write(endpoint, "POST", { ...value, http_status_code: code });
        } else {
          write(endpoint, "POST", value);
        }
      }}
    >
      <div className="ar-redirect-context-strip">
        <span>
          {t("Event")} <strong>{t(eventLabels[id])}</strong>
        </span>
        <span>
          {t("Access level")} <strong>{plain(boot.subject.name)}</strong>
        </span>
      </div>
      <div className="ar-redirect-panel-body">
        <div className="ar-redirect-choice-area">
          <div className="ar-redirect-section-heading">
            <span>01</span>
            <div>
              <h4>{t("Choose a behavior")}</h4>
              <p>{t("What should happen for this access level?")}</p>
            </div>
          </div>
          <div
            className="ar-redirect-choices"
            role="group"
            aria-label={t("Behavior")}
          >
            {types.map((type) => {
              const option = options[type];
              const selected = (value.type || "default") === type;
              return (
                <button
                  type="button"
                  key={type}
                  className={`ar-redirect-choice${selected ? " is-active" : ""}`}
                  aria-pressed={selected}
                  disabled={busy}
                  onClick={() =>
                    setValue(
                      id === "redirect" &&
                        ["default", "custom_message"].includes(type)
                        ? {
                            type,
                            http_status_code: value.http_status_code || 401,
                          }
                        : { type },
                    )
                  }
                >
                  <span className="ar-redirect-choice-icon" aria-hidden="true">
                    <span className={`dashicons dashicons-${option.icon}`} />
                  </span>
                  <span className="ar-redirect-choice-copy">
                    <strong>{t(option.label)}</strong>
                    <small>{t(option.description)}</small>
                  </span>
                  <span
                    className="ar-redirect-choice-check dashicons dashicons-yes"
                    aria-hidden="true"
                  />
                </button>
              );
            })}
          </div>
        </div>
        <div className="ar-redirect-detail-area">
          <div className="ar-redirect-section-heading">
            <span>02</span>
            <div>
              <h4>{t("Set the details")}</h4>
              <p>{t("Configure the selected outcome.")}</p>
            </div>
          </div>
          <div className="ar-redirect-detail-card">
            <span className="ar-redirect-detail-icon" aria-hidden="true">
              <span className={`dashicons dashicons-${current.icon}`} />
            </span>
            <h5>{t(current.label)}</h5>
            {legacyApiRule && (
              <p>
                {t(
                  "An older API redirect rule was found. Choose and save an API response behavior to replace it.",
                )}
              </p>
            )}
            {detail ? (
              <p>{t(detail)}</p>
            ) : isApi && value.type === "trigger_callback" ? (
              <TextControl
                label={t("Registered PHP callback")}
                help={t(
                  "The callback receives a WP_Error and must return a WP_Error with a 4xx or 5xx status in its error data. If it fails, the original denial is returned.",
                )}
                value={value.callback || ""}
                required
                onChange={(callback) => setValue({ ...value, callback })}
              />
            ) : (
              <RedirectFields
                value={value}
                onChange={setValue}
                types={types}
                showBehavior={false}
              />
            )}
            {id === "redirect" &&
              ["default", "custom_message"].includes(
                value.type || "default",
              ) && (
                <div className="ar-redirect-status">
                  <div className="ar-redirect-status-heading">
                    <strong>{t("HTTP response code")}</strong>
                    <small>
                      {t(
                        isApi
                          ? "Sent with the API error response."
                          : "Sent with the access denied response.",
                      )}
                    </small>
                  </div>
                  <div
                    className="ar-redirect-status-presets"
                    role="group"
                    aria-label={t("HTTP response code")}
                  >
                    {statusPresets.map((code) => (
                      <button
                        key={code}
                        type="button"
                        className={
                          !customStatus &&
                          Number(value.http_status_code || 401) === code
                            ? "is-active"
                            : ""
                        }
                        aria-pressed={
                          !customStatus &&
                          Number(value.http_status_code || 401) === code
                        }
                        onClick={() => {
                          setCustomStatus(false);
                          setValue({ ...value, http_status_code: code });
                        }}
                      >
                        <strong>{code}</strong>
                        <small>{statusDescriptions[code]}</small>
                      </button>
                    ))}
                    <button
                      type="button"
                      className={customStatus ? "is-active" : ""}
                      aria-pressed={customStatus}
                      onClick={() => {
                        setCustomStatus(true);
                        setValue({ ...value, http_status_code: customCode });
                      }}
                    >
                      <strong>{t("Custom")}</strong>
                      <small>{t("400–599")}</small>
                    </button>
                  </div>
                  {customStatus && (
                    <TextControl
                      label={t("Custom HTTP code")}
                      type="number"
                      min={400}
                      max={599}
                      step={1}
                      value={customCode}
                      onChange={(code) => {
                        setCustomCode(code);
                        setValue({ ...value, http_status_code: code });
                      }}
                    />
                  )}
                </div>
              )}
            {isApi && (
              <div
                className="ar-api-response-summary"
                role="status"
                aria-live="polite"
              >
                <span>{t("Clients will receive")}</span>
                {value.type === "trigger_callback" ? (
                  <p>
                    {t("The callback's error code, message, and HTTP status.")}
                  </p>
                ) : (
                  <>
                    <strong>
                      {t("HTTP")}{" "}
                      {customStatus
                        ? customCode
                        : value.http_status_code || 401}
                    </strong>
                    <p>
                      {value.type === "custom_message" ? (
                        <>
                          {t("Message:")}{" "}
                          {plain(value.message || "").trim() ||
                            t("Enter a message to preview it.")}
                        </>
                      ) : (
                        t(
                          "The request's standard denial message will be sent. It may vary by endpoint.",
                        )
                      )}
                    </p>
                  </>
                )}
              </div>
            )}
          </div>
        </div>
      </div>
      <div className="ar-redirect-panel-footer">
        <span>{t("Your changes take effect after you save.")}</span>
        <Button type="submit" variant="primary" disabled={busy}>
          {t(isApi ? "Save response" : "Save redirect")}
        </Button>
      </div>
    </form>
  );
}
export function UrlRules() {
  const r = useResource("/urls");
  const [edit, setEdit] = useState(null);
  const [remove, setRemove] = useState(null);
  const write = useWrite();
  return (
    <>
      <Title
        title={t("URL Access")}
        description={t(
          "Define access rules for specific URLs or URL patterns.",
        )}
        icon="admin-links"
      >
        <Reset
          endpoint="/urls"
          label={t("Reset URL Access")}
          serviceName={t("URL Access")}
          iconOnly
        />
        <Button
          variant="primary"
          onClick={() => setEdit({ url_schema: "", effect: "deny" })}
        >
          {t("Add URL rule")}
        </Button>
      </Title>
      <Resource resource={r}>
        {(data) =>
          arr(data).length ? (
            <table className="ar-table ar-url-rules-table">
              <thead>
                <tr>
                  <th>{t("URL pattern")}</th>
                  <th>{t("Rule")}</th>
                  <th>{t("Actions")}</th>
                </tr>
              </thead>
              <tbody>
                {arr(data).map((row) => (
                  <tr key={row.id}>
                    <td>
                      <div className="ar-url-name">
                        <UrlBehaviorIcon row={row} />
                        <code>{row.url_schema}</code>
                      </div>
                    </td>
                    <td>
                      <AccessOutcome
                        effect={row.effect}
                        customized={row.is_customized}
                      />
                    </td>
                    <td>
                      <div className="ar-actions ar-row-actions">
                        <ActionTooltip text={t("Edit URL rule")}>
                          <Button
                            variant="tertiary"
                            className="ar-table-icon-action"
                            aria-label={t("Edit URL rule")}
                            onClick={() => setEdit(row)}
                          >
                            <span
                              className="dashicons dashicons-edit"
                              aria-hidden="true"
                            />
                          </Button>
                        </ActionTooltip>
                        <ActionTooltip text={t("Delete URL rule")}>
                          <Button
                            isDestructive
                            variant="tertiary"
                            className="ar-table-icon-action"
                            aria-label={t("Delete URL rule")}
                            onClick={() => setRemove(row)}
                          >
                            <span
                              className="dashicons dashicons-trash"
                              aria-hidden="true"
                            />
                          </Button>
                        </ActionTooltip>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          ) : (
            <TableEmpty
              headings={[t("URL pattern"), t("Rule"), t("Actions")]}
              title={t("No URL rules yet")}
            >
              {t("Add a URL rule to control access to a specific location.")}
            </TableEmpty>
          )
        }
      </Resource>
      {edit && <UrlEditor initial={edit} onClose={() => setEdit(null)} />}
      {remove && (
        <Confirm
          title={t("Delete URL rule")}
          confirmLabel={t("Delete rule")}
          cancelLabel={t("Keep rule")}
          impact={t(
            "The inherited URL behavior will apply after this override is removed.",
          )}
          onClose={() => setRemove(null)}
          onConfirm={() => write("/url/" + base64Path(remove.id), "DELETE")}
        >
          {t("Delete the rule for")} <strong>{remove.url_schema}</strong>?
        </Confirm>
      )}
    </>
  );
}
function UrlEditor({ initial, onClose }: any) {
  const [value, setValue] = useState(initial);
  const write = useWrite();
  const { boot, busy, setMessage } = useWorkspace();
  return (
    <Modal
      title={initial.id ? t("Edit URL rule") : t("Add URL rule")}
      onRequestClose={() => !busy && onClose()}
      className="ar-modal"
    >
      <form
        onSubmit={async (e) => {
          e.preventDefault();
          if (value.effect === "deny" && needsPageSelection(value.redirect)) {
            setMessage({
              status: "error",
              text: t("Select a page before saving."),
            });
            return;
          }
          if (
            value.effect === "deny" &&
            value.redirect?.type === "conditional" &&
            !conditionComplete(value.redirect.condition)
          ) {
            setMessage({
              status: "error",
              text: t("Complete the condition before saving this URL rule."),
            });
            return;
          }
          const payload = {
            url_schema: value.url_schema,
            effect: value.effect,
            ...(value.effect === "deny" && value.redirect
              ? { redirect: value.redirect }
              : {}),
          };
          if (
            await write(
              initial.id ? "/url/" + base64Path(initial.id) : "/urls",
              initial.id ? "PATCH" : "POST",
              payload,
            )
          )
            onClose();
        }}
      >
        <ModalIntro
          icon="admin-links"
          title={t("Choose what happens at this URL")}
        >
          {t(
            "Match a URL or pattern, then allow access or choose how a denied request should be handled.",
          )}
        </ModalIntro>
        <TextControl
          label={t("URL or pattern")}
          value={value.url_schema}
          required
          onChange={(v) => setValue({ ...value, url_schema: v })}
        />
        <div className="ar-effect-field">
          <span className="ar-effect-label">{t("Access")}</span>
          <EffectToggle
            label={t("Access")}
            value={value.effect}
            onChange={(effect) => setValue({ ...value, effect })}
          />
        </div>
        {value.effect === "deny" && (
          <RedirectFields
            value={value.redirect || { type: "default" }}
            onChange={(v) => setValue({ ...value, redirect: v })}
            types={
              boot.premiumUi?.url
                ? [
                    "default",
                    "page_redirect",
                    "url_redirect",
                    "trigger_callback",
                    "login_redirect",
                    "custom_message",
                    "conditional",
                  ]
                : undefined
            }
            conditionalOptions={
              boot.premiumUi?.url ? boot.premiumUi.conditional : null
            }
          />
        )}
        <Button type="submit" variant="primary" disabled={busy}>
          {t("Save rule")}
        </Button>
      </form>
    </Modal>
  );
}
export function Policies() {
  const r = useResource("/policies?fields=permissions,excerpt");
  const [remove, setRemove] = useState(null);
  const write = useWrite();
  const { boot, busy } = useWorkspace();
  return (
    <>
      <Title
        title={t("Access Policies")}
        description={t("Attach JSON policies to the current access level.")}
        icon="media-code"
      >
        <Reset
          endpoint="/policies"
          label={t("Reset Access Policies")}
          serviceName={t("Access Policies")}
          iconOnly
        />
        <Button
          variant="primary"
          href={boot.adminUrl + "post-new.php?post_type=aam_policy"}
        >
          {t("Create policy")}
        </Button>
      </Title>
      <Resource resource={r}>
        {(data) =>
          arr(data).length ? (
            <table className="ar-table ar-policy-table">
              <thead>
                <tr>
                  <th>{t("Policy")}</th>
                  <th>{t("Actions")}</th>
                </tr>
              </thead>
              <tbody>
                {arr(data).map((row) => (
                  <tr key={row.id}>
                    <td>
                      <span className="ar-name-line">
                        <strong>{plain(row.title)}</strong>
                        {row.is_attached && <StateIcon state="attached" />}
                      </span>
                      {row.excerpt && <small>{plain(row.excerpt)}</small>}
                    </td>
                    <td>
                      <div className="ar-actions ar-row-actions">
                        <Button
                          variant="secondary"
                          disabled={
                            busy || !row.permissions?.includes("toggle_policy")
                          }
                          onClick={() =>
                            write("/policy/" + row.id, "PATCH", {
                              effect: row.is_attached ? "detach" : "attach",
                            })
                          }
                        >
                          {row.is_attached ? t("Detach") : t("Attach")}
                        </Button>
                        {row.permissions?.includes("edit_policy") && (
                          <ActionTooltip text={t("Edit policy")}>
                            <Button
                              variant="tertiary"
                              className="ar-table-icon-action"
                              aria-label={t("Edit policy")}
                              href={
                                boot.adminUrl +
                                "post.php?action=edit&post=" +
                                row.id
                              }
                            >
                              <span
                                className="dashicons dashicons-edit"
                                aria-hidden="true"
                              />
                            </Button>
                          </ActionTooltip>
                        )}
                        {row.permissions?.includes("delete_policy") && (
                          <ActionTooltip text={t("Delete policy")}>
                            <Button
                              isDestructive
                              variant="tertiary"
                              className="ar-table-icon-action"
                              aria-label={t("Delete policy")}
                              onClick={() => setRemove(row)}
                            >
                              <span
                                className="dashicons dashicons-trash"
                                aria-hidden="true"
                              />
                            </Button>
                          </ActionTooltip>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          ) : (
            <TableEmpty
              headings={[t("Policy"), t("Actions")]}
              title={t("No policies yet")}
            >
              {t(
                "Create your first JSON access policy using the WordPress policy editor.",
              )}
            </TableEmpty>
          )
        }
      </Resource>
      {remove && (
        <Confirm
          title={t("Delete policy")}
          confirmLabel={t("Delete policy")}
          cancelLabel={t("Keep policy")}
          impact={t(
            "This deletes the policy document itself, not just an attachment. Access levels using it will stop receiving its rules.",
          )}
          onClose={() => setRemove(null)}
          onConfirm={() => write("/policy/" + remove.id, "DELETE")}
        >
          {t("Delete the policy")} <strong>{plain(remove.title)}</strong>?
        </Confirm>
      )}
    </>
  );
}
export function Tokens() {
  const r = useResource("/jwts?fields=claims,signed_url");
  const [create, setCreate] = useState(false);
  const [remove, setRemove] = useState(null);
  const [reveal, setReveal] = useState(null);
  const write = useWrite();
  const { busy, setMessage } = useWorkspace();
  const copyCredential = async (kind: string, value: string) => {
    try {
      await copyText(value);
      setMessage({
        status: "success",
        text:
          kind === "token"
            ? t("JWT token copied.")
            : t("Passwordless URL copied."),
      });
    } catch {
      setMessage({
        status: "error",
        text: t(
          "Could not copy automatically. Open the details and select the value to copy it.",
        ),
      });
    }
  };
  return (
    <>
      <Title
        title={t("JWT Tokens")}
        description={t(
          "Credentials issued for this user. Treat tokens like passwords.",
        )}
        icon="privacy"
      >
        <Reset
          endpoint="/jwts"
          label={t("Revoke all JWT tokens")}
          serviceName={t("JWT Tokens")}
          iconOnly
        />
        <Button variant="primary" onClick={() => setCreate(true)}>
          {t("Create token")}
        </Button>
      </Title>
      <Resource resource={r}>
        {(data) =>
          arr(data).length ? (
            <table className="ar-table">
              <thead>
                <tr>
                  <th>{t("Token")}</th>
                  <th>{t("Actions")}</th>
                </tr>
              </thead>
              <tbody>
                {arr(data).map((row) => (
                  <tr key={row.id}>
                    <td>
                      <span className="ar-name-line">
                        <strong>{row.description || t("Unnamed token")}</strong>
                        <StateIcon state={row.is_valid ? "valid" : "invalid"} />
                      </span>
                      <small>{row.id}</small>
                      {row.claims?.exp && (
                        <small>
                          {t("Expires")}:{" "}
                          {new Date(row.claims.exp * 1000).toLocaleString()}
                        </small>
                      )}
                    </td>
                    <td>
                      <div className="ar-actions ar-token-actions">
                        <ActionTooltip
                          text={t("Copy JWT token")}
                          disabled={!row.is_valid || busy}
                        >
                          <Button
                            variant="secondary"
                            className="ar-token-copy-action"
                            disabled={!row.is_valid || busy}
                            onClick={() => copyCredential("token", row.token)}
                          >
                            <span
                              className="dashicons dashicons-admin-page"
                              aria-hidden="true"
                            />
                            {t("Copy token")}
                          </Button>
                        </ActionTooltip>
                        <ActionTooltip
                          text={t("Copy passwordless sign-in URL")}
                          disabled={!row.signed_url || busy}
                        >
                          <Button
                            variant="secondary"
                            className="ar-token-copy-action"
                            disabled={!row.signed_url || busy}
                            onClick={() =>
                              copyCredential("url", row.signed_url)
                            }
                          >
                            <span
                              className="dashicons dashicons-admin-links"
                              aria-hidden="true"
                            />
                            {t("Copy URL")}
                          </Button>
                        </ActionTooltip>
                        <ActionTooltip text={t("View token and sign-in URL")}>
                          <Button
                            variant="tertiary"
                            className="ar-token-icon-action"
                            aria-label={t("View token and sign-in URL")}
                            onClick={() => setReveal(row)}
                          >
                            <span
                              className="dashicons dashicons-visibility"
                              aria-hidden="true"
                            />
                          </Button>
                        </ActionTooltip>
                        <ActionTooltip
                          text={t("Refresh token")}
                          disabled={busy}
                        >
                          <Button
                            variant="tertiary"
                            className="ar-token-icon-action"
                            aria-label={t("Refresh token")}
                            disabled={busy}
                            onClick={() => write("/jwt/" + row.id, "PATCH")}
                          >
                            <span
                              className="dashicons dashicons-update"
                              aria-hidden="true"
                            />
                          </Button>
                        </ActionTooltip>
                        <ActionTooltip text={t("Revoke token")} disabled={busy}>
                          <Button
                            isDestructive
                            variant="tertiary"
                            className="ar-token-icon-action"
                            aria-label={t("Revoke token")}
                            disabled={busy}
                            onClick={() => setRemove(row)}
                          >
                            <span
                              className="dashicons dashicons-trash"
                              aria-hidden="true"
                            />
                          </Button>
                        </ActionTooltip>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          ) : (
            <TableEmpty
              headings={[t("Token"), t("Actions")]}
              title={t("No tokens yet")}
            >
              {t("This user has no stored JWT tokens.")}
            </TableEmpty>
          )
        }
      </Resource>
      {create && <TokenEditor onClose={() => setCreate(false)} />}
      {reveal && (
        <Modal
          title={t("Token and sign-in link")}
          onRequestClose={() => setReveal(null)}
          className="ar-modal"
        >
          <div className="ar-credential-intro">
            <span className="dashicons dashicons-lock" aria-hidden="true" />
            <div>
              <strong>{t("Handle this token carefully")}</strong>
              <p>
                {t(
                  "Copy it only to a trusted application. Anyone with the token may be able to use its access.",
                )}
              </p>
            </div>
          </div>
          <div className="ar-token-credential-list">
            <div className="ar-token-credential-card">
              <div className="ar-token-credential-heading">
                <span
                  className="ar-token-credential-icon dashicons dashicons-editor-code"
                  aria-hidden="true"
                />
                <div>
                  <strong>{t("JWT token")}</strong>
                  <small>{t("Use this credential for API requests.")}</small>
                </div>
                <Button
                  variant="secondary"
                  disabled={!reveal.is_valid}
                  onClick={() => copyCredential("token", reveal.token)}
                >
                  {t("Copy token")}
                </Button>
              </div>
              <textarea
                aria-label={t("JWT token")}
                value={reveal.token || ""}
                readOnly
                rows={3}
                onFocus={(event) => event.target.select()}
              />
            </div>
            {reveal.signed_url ? (
              <div className="ar-token-credential-card ar-token-credential-link">
                <div className="ar-token-credential-heading">
                  <span
                    className="ar-token-credential-icon dashicons dashicons-admin-links"
                    aria-hidden="true"
                  />
                  <div>
                    <strong>{t("Passwordless sign-in URL")}</strong>
                    <small>
                      {t("Opens this site and signs in as this user.")}
                    </small>
                  </div>
                  <Button
                    variant="secondary"
                    onClick={() => copyCredential("url", reveal.signed_url)}
                  >
                    {t("Copy URL")}
                  </Button>
                </div>
                <textarea
                  aria-label={t("Passwordless sign-in URL")}
                  value={reveal.signed_url}
                  readOnly
                  rows={3}
                  onFocus={(event) => event.target.select()}
                />
              </div>
            ) : (
              <p className="ar-token-unavailable">
                {t("This token no longer has a usable sign-in URL.")}
              </p>
            )}
          </div>
          <div className="ar-credential-actions">
            <Button variant="primary" onClick={() => setReveal(null)}>
              {t("Done")}
            </Button>
          </div>
        </Modal>
      )}
      {remove && (
        <Confirm
          title={t("Revoke token")}
          confirmLabel={t("Revoke token")}
          cancelLabel={t("Keep token")}
          impact={t(
            "Applications using this token will lose access and need a new credential.",
          )}
          onClose={() => setRemove(null)}
          onConfirm={() => write("/jwt/" + remove.id, "DELETE")}
        >
          {t("Revoke this JWT token now")}?
        </Confirm>
      )}
    </>
  );
}
function TokenEditor({ onClose }: any) {
  const [description, setDescription] = useState("");
  const [expiration, setExpiration] = useState("day");
  const [customDate, setCustomDate] = useState(
    localDateTime(new Date(Date.now() + 24 * 60 * 60 * 1000)),
  );
  const [refreshable, setRefreshable] = useState(false);
  const [revocable, setRevocable] = useState(true);
  const [claimsMode, setClaimsMode] = useState("fields");
  const [claimRows, setClaimRows] = useState([] as any[]);
  const [claimsJson, setClaimsJson] = useState("{}");
  const write = useWrite();
  const { busy, setMessage } = useWorkspace();
  const changeClaimsMode = (next: string) => {
    if (next === claimsMode) return;
    try {
      if (next === "json") {
        setClaimsJson(JSON.stringify(parseClaimRows(claimRows), null, 2));
      } else {
        setClaimRows(claimsToRows(parseClaimsJson(claimsJson)));
      }
      setClaimsMode(next);
    } catch (e: any) {
      setMessage({ status: "error", text: e.message });
    }
  };
  const setClaimRow = (index: number, change: any) =>
    setClaimRows(
      claimRows.map((row, at) => (at === index ? { ...row, ...change } : row)),
    );
  return (
    <Modal
      title={t("Create JWT token")}
      onRequestClose={() => !busy && onClose()}
      className="ar-modal"
    >
      <form
        className="ar-jwt-editor"
        onSubmit={async (e) => {
          e.preventDefault();
          try {
            const parsed =
              claimsMode === "json"
                ? parseClaimsJson(claimsJson)
                : parseClaimRows(claimRows);
            const expiry = expirationPayload(expiration, customDate);
            if (
              await write("/jwts", "POST", {
                description,
                ...expiry,
                is_refreshable: refreshable,
                is_revocable: revocable,
                additional_claims: parsed,
              })
            )
              onClose();
          } catch (e: any) {
            setMessage({ status: "error", text: e.message });
          }
        }}
      >
        <ModalIntro icon="privacy" title={t("Issue a token for this user")}>
          {t(
            "Set its expiration and claims before creating it. Applications can use this token to authenticate as the selected user.",
          )}
        </ModalIntro>
        <TextControl
          label={t("Description")}
          placeholder={t("For example, reporting integration")}
          value={description}
          onChange={setDescription}
        />
        <section
          className="ar-jwt-section"
          aria-labelledby="ar-jwt-expiration-heading"
        >
          <div className="ar-jwt-section-heading">
            <span className="dashicons dashicons-clock" aria-hidden="true" />
            <div>
              <h3 id="ar-jwt-expiration-heading">{t("Expiration")}</h3>
              <p>{t("Pick a quick duration or set an exact deadline.")}</p>
            </div>
          </div>
          <ExpirationChoices
            choice={expiration}
            onChoice={setExpiration}
            customDate={customDate}
            onCustomDate={setCustomDate}
            label={t("Token expiration")}
            help={t("Select a future date and time for this token to expire.")}
          />
        </section>
        <section
          className="ar-jwt-section"
          aria-labelledby="ar-jwt-claims-heading"
        >
          <div className="ar-jwt-section-heading ar-jwt-claims-heading">
            <span
              className="dashicons dashicons-editor-code"
              aria-hidden="true"
            />
            <div>
              <h3 id="ar-jwt-claims-heading">{t("Additional claims")}</h3>
              <p>{t("Optional properties to include in the token payload.")}</p>
            </div>
          </div>
          <div
            className="ar-jwt-mode-list"
            role="group"
            aria-label={t("Claims editor mode")}
          >
            <button
              type="button"
              className={claimsMode === "fields" ? "is-selected" : ""}
              aria-pressed={claimsMode === "fields"}
              onClick={() => changeClaimsMode("fields")}
            >
              {t("Property & value")}
            </button>
            <button
              type="button"
              className={claimsMode === "json" ? "is-selected" : ""}
              aria-pressed={claimsMode === "json"}
              onClick={() => changeClaimsMode("json")}
            >
              {t("JSON")}
            </button>
          </div>
          {claimsMode === "fields" ? (
            <div className="ar-jwt-claims-fields">
              {claimRows.map((row, index) => (
                <div className="ar-jwt-claim-row" key={index}>
                  <TextControl
                    label={t("Property")}
                    value={row.key}
                    placeholder="scope"
                    onChange={(key) => setClaimRow(index, { key })}
                  />
                  {row.type === "boolean" ? (
                    <SelectControl
                      label={t("Value")}
                      value={row.value}
                      options={[
                        { label: t("True"), value: "true" },
                        { label: t("False"), value: "false" },
                      ]}
                      onChange={(value) => setClaimRow(index, { value })}
                    />
                  ) : (
                    <TextControl
                      label={t("Value")}
                      value={row.value}
                      placeholder={
                        row.type === "json" ? '{"key":"value"}' : "read"
                      }
                      onChange={(value) => setClaimRow(index, { value })}
                    />
                  )}
                  <SelectControl
                    label={t("Type")}
                    value={row.type}
                    options={[
                      { label: t("Text"), value: "text" },
                      { label: t("Number"), value: "number" },
                      { label: t("True / false"), value: "boolean" },
                      { label: t("JSON"), value: "json" },
                    ]}
                    onChange={(type) =>
                      setClaimRow(index, {
                        type,
                        value:
                          type === "boolean"
                            ? "true"
                            : row.type === "boolean"
                              ? ""
                              : row.value,
                      })
                    }
                  />
                  <Button
                    type="button"
                    variant="tertiary"
                    className="ar-jwt-remove-claim"
                    aria-label={t("Remove claim")}
                    title={t("Remove claim")}
                    onClick={() =>
                      setClaimRows(claimRows.filter((_, at) => at !== index))
                    }
                  >
                    <span
                      className="dashicons dashicons-trash"
                      aria-hidden="true"
                    />
                  </Button>
                </div>
              ))}
              {claimRows.length === 0 && (
                <p className="ar-jwt-empty-claims">
                  {t(
                    "No additional claims yet. Add a property or switch to JSON.",
                  )}
                </p>
              )}
              <Button
                type="button"
                variant="secondary"
                className="ar-jwt-add-claim"
                onClick={() =>
                  setClaimRows([
                    ...claimRows,
                    { key: "", value: "", type: "text" },
                  ])
                }
              >
                <span
                  className="dashicons dashicons-plus-alt2"
                  aria-hidden="true"
                />
                {t("Add claim")}
              </Button>
            </div>
          ) : (
            <div className="ar-jwt-claims-json">
              <TextareaControl
                label={t("Claims JSON object")}
                help={t("Enter an object with property names and values.")}
                value={claimsJson}
                onChange={setClaimsJson}
              />
            </div>
          )}
        </section>
        <div className="ar-jwt-options">
          <ToggleControl
            className="ar-toggle"
            label={t("Allow refresh")}
            checked={refreshable}
            onChange={setRefreshable}
          />
          <ToggleControl
            className="ar-toggle"
            label={t("Allow revocation")}
            checked={revocable}
            onChange={setRevocable}
          />
        </div>
        <div className="ar-jwt-actions">
          <Button
            type="button"
            variant="tertiary"
            onClick={onClose}
            disabled={busy}
          >
            {t("Cancel")}
          </Button>
          <Button type="submit" variant="primary" disabled={busy}>
            {t("Create token")}
          </Button>
        </div>
      </form>
    </Modal>
  );
}
