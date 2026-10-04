import {
  useState,
  useEffect,
  Workspace,
  Button,
  Modal,
  ModalIntro,
  Spinner,
  ConfirmationDialog,
  t,
  plain,
  arr,
  request,
  mutate,
  pathFor,
  FrontendErrorBoundary,
} from "./core";
import { ScopedContentForm, ContentPremiumPrompt } from "./content";
import { Toast } from "./toast";
import {
  accessLevelStorageKey,
  readAccessLevel,
  rememberAccessLevel,
  forgetAccessLevel,
} from "./access-level-memory.mjs";
import { availableEditorAccessLevel } from "./editor-access-selection.mjs";

const kinds = [
  { type: "role", label: "Roles", icon: "groups" },
  { type: "user", label: "Users", icon: "admin-users" },
  { type: "visitor", label: "Visitors", icon: "admin-site-alt3" },
  { type: "default", label: "Default", icon: "admin-generic" },
];

function TermAccess({ termId, taxonomy, termName, boot }: any) {
  const levels = kinds.filter((kind) => boot.levels[kind.type]);
  const [memory] = useState(() => {
    let storage = null;
    try {
      storage = window.localStorage;
    } catch {
      // Browser storage is optional.
    }
    const key = accessLevelStorageKey(boot);
    const saved = readAccessLevel(storage, key);
    return {
      storage,
      key,
      saved,
      initial: availableEditorAccessLevel(boot, saved),
    };
  });
  const [kind, setKind] = useState(
    memory.initial?.type || levels[0]?.type || "",
  );
  const [role, setRole] = useState(
    memory.initial?.type === "role" ? memory.initial.id : null,
  );
  const [user, setUser] = useState(null);
  const [pendingUser, setPendingUser] = useState(
    memory.initial?.type === "user" ? memory.initial.id : null,
  );
  const [search, setSearch] = useState("");
  const [users, setUsers] = useState([]);
  const [usersLoading, setUsersLoading] = useState(false);
  const [open, setOpen] = useState(false);
  const [data, setData] = useState(null);
  const [loadedFor, setLoadedFor] = useState("");
  const [failedFor, setFailedFor] = useState("");
  const [reload, setReload] = useState(0);
  const [loading, setLoading] = useState(false);
  const [busy, setBusy] = useState(false);
  const [dirty, setDirty] = useState(false);
  const [pendingAction, setPendingAction] = useState(null);
  const [resetOpen, setResetOpen] = useState(false);
  const [message, setMessage] = useState(null);
  const subject =
    kind === "role" && role
      ? {
          type: "role",
          id: role,
          name: boot.roles.find((r) => r.value === role)?.label || role,
        }
      : kind === "user" && user
        ? { type: "user", id: user.id, name: user.name }
        : kind === "visitor" || kind === "default"
          ? {
              type: kind,
              id: null,
              name: kind === "visitor" ? t("Visitors") : t("Default access"),
            }
          : null;
  const subjectKey = subject ? `${subject.type}:${subject.id ?? ""}` : "";
  const endpoint = `/content/term/${termId}?taxonomy=${encodeURIComponent(taxonomy)}`;
  const target = {
    kind: "term",
    row: { id: termId, taxonomy, title: termName },
  };

  useEffect(() => {
    if (memory.saved && !memory.initial)
      forgetAccessLevel(memory.storage, memory.key);
  }, []);

  useEffect(() => {
    if (!pendingUser || kind !== "user" || user) return;
    const controller = new AbortController();
    let live = true;
    request(`/aam/v2/user/${pendingUser}?fields=display_name,user_login`, {
      signal: controller.signal,
    })
      .then((result) => {
        if (!live) return;
        setUser({
          id: pendingUser,
          name: plain(
            result.display_name || result.user_login || `#${pendingUser}`,
          ),
          login: plain(result.user_login || ""),
        });
        setPendingUser(null);
      })
      .catch((error) => {
        if (!live || error.name === "AbortError") return;
        setPendingUser(null);
        if (error.status === 404 || error.status === 403) {
          forgetAccessLevel(memory.storage, memory.key);
          setKind(levels[0]?.type || "");
        } else {
          setMessage({
            status: "error",
            text: error.message || t("The selected user could not be loaded."),
          });
        }
      });
    return () => {
      live = false;
      controller.abort();
    };
  }, [pendingUser, kind, user?.id]);

  useEffect(() => {
    if (!subject) {
      setData(null);
      setLoadedFor("");
      setFailedFor("");
      return;
    }
    rememberAccessLevel(memory.storage, memory.key, subject);
    if (!boot.contentPremium?.enabled) {
      setData(null);
      setLoadedFor("");
      setFailedFor("");
      setLoading(false);
      return;
    }
    const controller = new AbortController();
    let live = true;
    setLoading(true);
    setData(null);
    setLoadedFor("");
    setFailedFor("");
    request(pathFor(endpoint, subject), { signal: controller.signal })
      .then((result) => {
        if (!live) return;
        if (result?.error)
          throw Error(
            result.error.message || t("Term access could not be loaded."),
          );
        setData(result);
        setLoadedFor(subjectKey);
      })
      .catch((error) => {
        if (live && error.name !== "AbortError") {
          setFailedFor(subjectKey);
          setMessage({
            status: "error",
            text: error.message || t("Term access could not be loaded."),
          });
        }
      })
      .finally(() => {
        if (live) setLoading(false);
      });
    return () => {
      live = false;
      controller.abort();
    };
  }, [subjectKey, termId, taxonomy, reload, boot.contentPremium?.enabled]);

  useEffect(() => {
    if (!open || kind !== "user") return;
    const controller = new AbortController();
    let live = true;
    const timer = setTimeout(
      () => {
        setUsersLoading(true);
        request(
          `/aam/v2/users?fields=display_name,user_login&per_page=20&search=${encodeURIComponent(search)}`,
          { signal: controller.signal },
        )
          .then((result) => {
            if (live)
              setUsers(
                arr(result).map((item: any) => ({
                  id: Number(item.id),
                  name: plain(item.display_name || item.user_login),
                  login: plain(item.user_login || ""),
                })),
              );
          })
          .catch((error) => {
            if (live && error.name !== "AbortError")
              setMessage({
                status: "error",
                text: t("Users could not be loaded."),
              });
          })
          .finally(() => {
            if (live) setUsersLoading(false);
          });
      },
      search ? 250 : 0,
    );
    return () => {
      live = false;
      clearTimeout(timer);
      controller.abort();
    };
  }, [open, kind, search]);

  const guarded = (action: () => void) => {
    if (busy) return;
    if (dirty) setPendingAction(() => action);
    else action();
  };
  const close = () =>
    guarded(() => {
      setOpen(false);
      setDirty(false);
    });
  const write = async (path: string, method: string, changes: any) => {
    if (busy || !subject) return false;
    setBusy(true);
    try {
      const result = await mutate(path, method, changes);
      if (result?.success === false || result?.error) {
        throw Error(
          result?.error?.message || t("Term access could not be saved."),
        );
      }
      if (method !== "DELETE" && result?.permissions) {
        setData(result);
        setLoadedFor(subjectKey);
      } else {
        try {
          const current = await request(pathFor(endpoint, subject));
          setData(current);
          setLoadedFor(subjectKey);
        } catch {
          setReload((n) => n + 1);
        }
      }
      setMessage({
        status: "success",
        text: wp.i18n.sprintf(
          method === "DELETE"
            ? t("Term access reset for %s.")
            : t("Term access saved for %s."),
          plain(subject.name),
        ),
      });
      return true;
    } catch (error) {
      setMessage({
        status: "error",
        text: error.message || t("Term access could not be saved."),
      });
      return false;
    } finally {
      setBusy(false);
    }
  };
  const selectedLabel = subject
    ? `${subject.type === "role" ? t("Role") + ": " : subject.type === "user" ? t("User") + ": " : ""}${plain(subject.name)}`
    : pendingUser
      ? t("Restoring selected user…")
      : t("Select an access level");
  const denyCount =
    data && loadedFor === subjectKey
      ? Object.entries(data.permissions || {}).filter(
          ([key, rule]: any) =>
            !!data.controls?.[key] && rule?.effect === "deny",
        ).length
      : 0;
  const choices =
    user && !users.some((item) => item.id === user.id)
      ? [user, ...users]
      : users;
  const matchingRoles = boot.roles.filter((item) =>
    `${item.label} ${item.value}`
      .toLowerCase()
      .includes(search.trim().toLowerCase()),
  );

  return (
    <Workspace.Provider
      value={{
        boot: { ...boot, subject },
        busy,
        write,
        setMessage,
      }}
    >
      <div className="aam-term-card">
        <div className="aam-term-card-icon" aria-hidden="true">
          <span className="dashicons dashicons-shield" />
        </div>
        <div className="aam-term-card-copy">
          <strong>{t("Access controls")}</strong>
          <span>
            {boot.contentPremium?.enabled
              ? selectedLabel
              : t("A preview of term access")}
          </span>
          <small>
            {!boot.contentPremium?.enabled
              ? boot.contentPremium?.active
                ? t("Enable Posts & Terms to manage this term.")
                : boot.contentPremium?.installed
                  ? t("Activate Premium to manage this term.")
                  : t("Premium adds visibility, browsing and editing rules.")
              : loading || pendingUser
                ? t("Checking effective access…")
                : !subject
                  ? t("Choose who you want to manage.")
                  : data && loadedFor === subjectKey
                    ? `${data.is_customized ? t("Customized") : t("Inherited")} · ${denyCount} ${t("restricted actions")}`
                    : failedFor === subjectKey
                      ? t("Could not load this access level.")
                      : t("Open to review this term's access rules.")}
          </small>
        </div>
        <Button type="button" variant="secondary" onClick={() => setOpen(true)}>
          {boot.contentPremium?.enabled
            ? t("Manage access")
            : t("Explore controls")}
          <span
            className="dashicons dashicons-arrow-right-alt"
            aria-hidden="true"
          />
        </Button>
      </div>
      {open && (
        <Modal
          title={`${t("Access controls")} · ${plain(termName)}`}
          onRequestClose={close}
          className="ar-modal aam-term-modal"
        >
          <ModalIntro icon="lock" title={t("Manage this term's access")}>
            {t(
              "Choose an access level, then set its rules for this term. Changes save separately from the term details.",
            )}
          </ModalIntro>
          <div className="aam-term-workspace">
            <aside className="aam-term-levels" aria-label={t("Access levels")}>
              <div className="aam-term-kind-grid">
                {levels.map((item) => (
                  <button
                    type="button"
                    key={item.type}
                    className={kind === item.type ? "is-active" : ""}
                    aria-pressed={kind === item.type}
                    disabled={busy}
                    onClick={() => {
                      if (kind !== item.type)
                        guarded(() => {
                          setPendingUser(null);
                          setKind(item.type);
                          setSearch("");
                        });
                    }}
                  >
                    <span
                      className={`dashicons dashicons-${item.icon}`}
                      aria-hidden="true"
                    />
                    {t(item.label)}
                  </button>
                ))}
              </div>
              {(kind === "role" || kind === "user") && (
                <div className="aam-term-people">
                  <label htmlFor="aam-term-search">
                    {kind === "role" ? t("Find a role") : t("Find a user")}
                  </label>
                  <input
                    id="aam-term-search"
                    type="search"
                    value={search}
                    placeholder={
                      kind === "role" ? t("Search roles…") : t("Search users…")
                    }
                    onChange={(event) => setSearch(event.target.value)}
                  />
                  <div className="aam-term-people-list">
                    {kind === "role" ? (
                      matchingRoles.length ? (
                        matchingRoles.map((item) => (
                          <button
                            type="button"
                            key={item.value}
                            className={role === item.value ? "is-selected" : ""}
                            aria-pressed={role === item.value}
                            disabled={busy}
                            onClick={() => {
                              if (role !== item.value)
                                guarded(() => setRole(item.value));
                            }}
                          >
                            <span
                              className="dashicons dashicons-groups"
                              aria-hidden="true"
                            />
                            <span>
                              <strong>{plain(item.label)}</strong>
                              <small>{item.value}</small>
                            </span>
                          </button>
                        ))
                      ) : (
                        <p className="aam-term-list-status">
                          {t("No matching roles")}
                        </p>
                      )
                    ) : (
                      <>
                        {usersLoading && (
                          <p className="aam-term-list-status">
                            <Spinner /> {t("Loading users…")}
                          </p>
                        )}
                        {choices.map((item) => (
                          <button
                            type="button"
                            key={item.id}
                            className={
                              user?.id === item.id ? "is-selected" : ""
                            }
                            aria-pressed={user?.id === item.id}
                            disabled={busy}
                            onClick={() => {
                              if (user?.id !== item.id)
                                guarded(() => {
                                  setPendingUser(null);
                                  setUser(item);
                                });
                            }}
                          >
                            <span
                              className="dashicons dashicons-admin-users"
                              aria-hidden="true"
                            />
                            <span>
                              <strong>{item.name}</strong>
                              <small>
                                {item.login ? `@${item.login} · ` : ""}#
                                {item.id}
                              </small>
                            </span>
                          </button>
                        ))}
                        {!usersLoading && !choices.length && (
                          <p className="aam-term-list-status">
                            {t("No matching users")}
                          </p>
                        )}
                      </>
                    )}
                  </div>
                </div>
              )}
              {(kind === "visitor" || kind === "default") && (
                <p className="aam-term-level-note">
                  {kind === "visitor"
                    ? t("Rules for people who are not signed in.")
                    : t("Baseline rules without a more specific rule.")}
                </p>
              )}
            </aside>
            <section
              className="aam-term-editor"
              aria-label={t("Term access settings")}
            >
              <div className="aam-term-editor-head">
                <div>
                  <small>{t("MANAGING")}</small>
                  <strong>{selectedLabel}</strong>
                </div>
                {data && loadedFor === subjectKey && (
                  <span className={data.is_customized ? "is-customized" : ""}>
                    {data.is_customized ? t("Customized") : t("Inherited")}
                  </span>
                )}
              </div>
              {!boot.contentPremium?.enabled ? (
                <ContentPremiumPrompt
                  target={target}
                  premium={boot.contentPremium}
                  onClose={close}
                />
              ) : !subject ? (
                <div className="aam-term-empty">
                  {pendingUser ? (
                    <>
                      <Spinner /> {t("Restoring selected user…")}
                    </>
                  ) : (
                    t(
                      "Select a role or user to review their access to this term.",
                    )
                  )}
                </div>
              ) : failedFor === subjectKey ? (
                <div className="aam-term-empty">
                  {t("Access controls could not be loaded.")}
                  <Button
                    type="button"
                    variant="secondary"
                    onClick={() => setReload((n) => n + 1)}
                  >
                    {t("Try again")}
                  </Button>
                </div>
              ) : loading || loadedFor !== subjectKey ? (
                <div className="aam-term-empty">
                  <Spinner /> {t("Loading access controls…")}
                </div>
              ) : (
                <ScopedContentForm
                  key={subjectKey}
                  target={target}
                  endpoint={endpoint}
                  initial={data}
                  onDirtyChange={setDirty}
                  onClose={close}
                  onSaved={() => {
                    setDirty(false);
                    setOpen(false);
                  }}
                  onResetRequest={() => setResetOpen(true)}
                />
              )}
            </section>
          </div>
        </Modal>
      )}
      {pendingAction && (
        <ConfirmationDialog
          title={t("Discard unsaved access changes?")}
          confirmLabel={t("Discard and continue")}
          cancelLabel={t("Keep editing")}
          onClose={() => setPendingAction(null)}
          onConfirm={() => {
            pendingAction();
            setDirty(false);
          }}
          impact={t("Changes to this access level have not been saved.")}
        >
          {t(
            "Switching access levels or closing this dialog will discard the current draft.",
          )}
        </ConfirmationDialog>
      )}
      {resetOpen && (
        <ConfirmationDialog
          title={t("Reset term access overrides?")}
          confirmLabel={t("Reset overrides")}
          cancelLabel={t("Keep overrides")}
          busy={busy}
          onClose={() => setResetOpen(false)}
          onConfirm={async () => {
            const saved = await write(
              pathFor(endpoint, subject),
              "DELETE",
              undefined,
            );
            if (saved) {
              setDirty(false);
              setOpen(false);
            }
            return saved;
          }}
          impact={t(
            "Only this term's rules for the selected access level are affected.",
          )}
        >
          {t("Remove custom rules for")} <strong>{selectedLabel}</strong>?
        </ConfirmationDialog>
      )}
      {message && (
        <div className="aam-term-toast-region">
          <Toast
            message={message}
            onClose={() =>
              setMessage((current) => (current === message ? null : current))
            }
          />
        </div>
      )}
    </Workspace.Provider>
  );
}

const root = document.getElementById("aam-term-access-root");
if (root) {
  wp.element
    .createRoot(root)
    .render(
      <FrontendErrorBoundary>
        <TermAccess
          termId={Number(root.dataset.termId)}
          taxonomy={root.dataset.taxonomy}
          termName={root.dataset.termName}
          boot={window.aamTermAccessBootstrap || { roles: [], levels: {} }}
        />
      </FrontendErrorBoundary>,
    );
}
