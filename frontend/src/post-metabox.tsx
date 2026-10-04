import {
  useState,
  useEffect,
  Workspace,
  Button,
  ConfirmationDialog,
  Spinner,
  ToggleControl,
  t,
  plain,
  arr,
  request,
  mutate,
  pathFor,
} from "./core";
import { ContentCustomizations } from "./content";
import { needsPageSelection } from "./page-picker.mjs";
import { Toast } from "./toast";
import { AccessOutcome } from "./resource-visuals";
import {
  accessLevelStorageKey,
  readAccessLevel,
  rememberAccessLevel,
  forgetAccessLevel,
} from "./access-level-memory.mjs";
import { availableEditorAccessLevel } from "./editor-access-selection.mjs";
import { changedPermissions } from "./content-permissions.mjs";
import { visibleContentControls } from "./content-control-visibility.mjs";

const controls = [
  {
    key: "list",
    title: "Hide from lists",
    note: "Direct links can still work.",
    icon: "hidden",
  },
  {
    key: "read",
    title: "Restrict direct access",
    note: "Control reading and downloads.",
    icon: "lock",
  },
  {
    key: "comment",
    title: "Block comments",
    note: "Prevent new comments.",
    icon: "admin-comments",
  },
  {
    key: "edit",
    title: "Block editing",
    note: "Prevent changes in admin and API.",
    icon: "edit",
  },
  {
    key: "publish",
    title: "Block publishing",
    note: "Prevent new published versions.",
    icon: "upload",
  },
  {
    key: "delete",
    title: "Block deletion",
    note: "Prevent trashing or deleting.",
    icon: "trash",
  },
];
const audiences = [
  { type: "role", label: "Role", icon: "groups" },
  { type: "user", label: "User", icon: "admin-users" },
  { type: "visitor", label: "Visitors", icon: "admin-site-alt3" },
  { type: "default", label: "Default", icon: "admin-generic" },
];

function PostAccessMetabox({ postId, boot }: any) {
  const levels = audiences.filter((item) => boot.levels[item.type]);
  const [memory] = useState(() => {
    let storage = null;
    try {
      storage = window.localStorage;
    } catch {
      // The metabox remains usable when browser storage is unavailable.
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
  const [audience, setAudience] = useState(
    memory.initial?.type || levels[0]?.type || "",
  );
  const [role, setRole] = useState(
    memory.initial?.type === "role" ? memory.initial.id : null,
  );
  const [roleSearch, setRoleSearch] = useState("");
  const [user, setUser] = useState(null);
  const [pendingRestoredUser, setPendingRestoredUser] = useState(
    memory.initial?.type === "user" ? memory.initial.id : null,
  );
  const [userSearch, setUserSearch] = useState("");
  const [userOptions, setUserOptions] = useState([]);
  const [usersLoading, setUsersLoading] = useState(false);
  const [data, setData] = useState(null);
  const [loadedFor, setLoadedFor] = useState("");
  const [failedFor, setFailedFor] = useState("");
  const [permissions, setPermissions] = useState({});
  const [dirty, setDirty] = useState({});
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [resetOpen, setResetOpen] = useState(false);
  const [message, setMessage] = useState(null);
  const busy = loading || saving;
  const changed = Object.keys(dirty).length > 0;

  const subject =
    audience === "role" && role
      ? {
          type: "role",
          id: role,
          name: boot.roles.find((item) => item.value === role)?.label || role,
        }
      : audience === "user" && user
        ? { type: "user", id: user.id, name: user.name }
        : audience === "visitor" || audience === "default"
          ? {
              type: audience,
              id: null,
              name:
                audience === "visitor" ? t("Visitors") : t("Default access"),
            }
          : null;
  const availableControls = visibleContentControls(
    controls,
    subject?.type,
    (control: any) => control.key,
  );
  const subjectKey = subject ? `${subject.type}:${subject.id ?? ""}` : "";
  const postPath = subject ? pathFor(`/post/${postId}`, subject) : "";

  useEffect(() => {
    if (memory.saved && !memory.initial) {
      forgetAccessLevel(memory.storage, memory.key);
    }
  }, []);

  useEffect(() => {
    if (!pendingRestoredUser || audience !== "user" || user) return;
    const controller = new AbortController();
    let live = true;
    request(
      `/aam/v2/user/${pendingRestoredUser}?fields=display_name,user_login`,
      { signal: controller.signal },
    )
      .then((result) => {
        if (!live) return;
        setUser({
          id: pendingRestoredUser,
          name: plain(
            result.display_name ||
              result.user_login ||
              `#${pendingRestoredUser}`,
          ),
          login: plain(result.user_login || ""),
        });
        setPendingRestoredUser(null);
      })
      .catch((error) => {
        if (!live || error.name === "AbortError") return;
        setPendingRestoredUser(null);
        if (error.status === 404 || error.status === 403) {
          forgetAccessLevel(memory.storage, memory.key);
          setAudience(levels[0]?.type || "");
        } else {
          setMessage({
            status: "error",
            text:
              error.message || t("The last selected user could not be loaded."),
          });
        }
      });
    return () => {
      live = false;
      controller.abort();
    };
  }, [pendingRestoredUser, audience, user?.id]);

  useEffect(() => {
    if (subject) rememberAccessLevel(memory.storage, memory.key, subject);
  }, [subjectKey]);

  useEffect(() => {
    if (audience !== "user") return;
    const controller = new AbortController();
    let live = true;
    const timer = setTimeout(
      async () => {
        setUsersLoading(true);
        try {
          const result = await request(
            `/aam/v2/users?fields=display_name,user_login&per_page=20&search=${encodeURIComponent(userSearch)}`,
            { signal: controller.signal },
          );
          if (live)
            setUserOptions(
              arr(result).map((item: any) => ({
                value: String(item.id),
                label: `${plain(item.display_name || item.user_login)} · #${item.id}`,
                name: plain(item.display_name || item.user_login),
                login: plain(item.user_login),
              })),
            );
        } catch (error) {
          if (live && error.name !== "AbortError") {
            setMessage({
              status: "error",
              text: t("Users could not be loaded. Try searching again."),
            });
          }
        } finally {
          if (live) setUsersLoading(false);
        }
      },
      userSearch ? 250 : 0,
    );
    return () => {
      live = false;
      clearTimeout(timer);
      controller.abort();
    };
  }, [audience, userSearch]);

  useEffect(() => {
    setData(null);
    setLoadedFor("");
    setFailedFor("");
    setPermissions({});
    setDirty({});
    if (!subjectKey || !postId) return;
    const controller = new AbortController();
    let live = true;
    setLoading(true);
    request(postPath, { signal: controller.signal })
      .then((result) => {
        if (live) {
          setData(result);
          setLoadedFor(subjectKey);
          setPermissions(result.permissions || {});
        }
      })
      .catch((error) => {
        if (live && error.name !== "AbortError") {
          setFailedFor(subjectKey);
          setMessage({
            status: "error",
            text: error.message || t("Access rules could not be loaded."),
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
  }, [subjectKey, postId]);

  const guardChange = (change: () => void) => {
    if (busy) return;
    if (changed) {
      setMessage({
        status: "warning",
        text: t("Save or discard your changes before switching access levels."),
      });
      return;
    }
    change();
  };
  const setPermission = (key: string, value: any) => {
    setPermissions((current) => ({ ...current, [key]: value }));
    setDirty((current) => ({ ...current, [key]: true }));
  };
  const reload = async () => {
    const result = await request(postPath);
    setData(result);
    setLoadedFor(subjectKey);
    setPermissions(result.permissions || {});
    setDirty({});
  };
  const save = async () => {
    if (!changed || !subject || loadedFor !== subjectKey || busy) return;
    const read = permissions.read || {};
    if (
      read.effect === "deny" &&
      read.restriction_type === "redirect" &&
      needsPageSelection(read.redirect)
    ) {
      setMessage({
        status: "error",
        text: t("Select a redirect page before saving."),
      });
      return;
    }
    if (
      read.effect === "deny" &&
      read.restriction_type === "redirect" &&
      read.redirect?.type === "url_redirect" &&
      !read.redirect.redirect_url?.trim()
    ) {
      setMessage({
        status: "error",
        text: t("Enter a redirect URL before saving."),
      });
      return;
    }
    if (
      read.effect === "deny" &&
      read.restriction_type === "redirect" &&
      read.redirect?.type === "trigger_callback" &&
      !read.redirect.callback?.trim()
    ) {
      setMessage({
        status: "error",
        text: t("Enter a registered callback before saving."),
      });
      return;
    }
    if (
      read.effect === "deny" &&
      read.restriction_type === "redirect" &&
      read.redirect?.type === "custom_message" &&
      !read.redirect.message?.trim()
    ) {
      setMessage({
        status: "error",
        text: t("Enter an access denied message before saving."),
      });
      return;
    }
    if (
      read.effect === "deny" &&
      read.restriction_type === "expire" &&
      !read.expires_after
    ) {
      setMessage({
        status: "error",
        text: t("Choose an expiration date and time before saving."),
      });
      return;
    }
    if (
      read.effect === "deny" &&
      read.restriction_type === "conditional" &&
      (!read.condition?.criteria ||
        !read.condition?.type ||
        !String(read.condition?.value || "").trim())
    ) {
      setMessage({
        status: "error",
        text: t("Complete all conditional restriction fields before saving."),
      });
      return;
    }
    if (
      read.effect === "deny" &&
      read.restriction_type === "password_protected" &&
      !read.password
    ) {
      setMessage({
        status: "error",
        text: t("Enter a content password before saving."),
      });
      return;
    }
    setSaving(true);
    try {
      const response = await mutate(postPath, "POST", {
        permissions: changedPermissions(permissions, dirty),
      });
      if (response?.success === false)
        throw Error(t("The access change was not saved."));
      setData(response);
      setPermissions(response.permissions || {});
      setDirty({});
      setMessage({
        status: "success",
        text: wp.i18n.sprintf(
          t("Post access rules saved for %s."),
          plain(subject.name),
        ),
      });
    } catch (error) {
      try {
        await reload();
      } catch (_) {
        /* Keep the current draft if refresh fails. */
      }
      setMessage({
        status: "error",
        text:
          error.message ||
          t(
            "Access rules could not be saved. Review the current settings; some changes may have been saved.",
          ),
      });
    } finally {
      setSaving(false);
    }
  };
  const reset = async () => {
    setSaving(true);
    try {
      const response = await mutate(postPath, "DELETE");
      if (response?.success === false)
        throw Error(t("Access rules could not be reset."));
      await reload();
      setMessage({
        status: "success",
        text: wp.i18n.sprintf(
          t("This post now inherits access for %s."),
          plain(subject.name),
        ),
      });
      return true;
    } catch (error) {
      setMessage({
        status: "error",
        text: error.message || t("Access rules could not be reset."),
      });
      return false;
    } finally {
      setSaving(false);
    }
  };
  const currentUser = user && {
    value: String(user.id),
    label: `${user.name} · #${user.id}`,
    name: user.name,
    login: user.login,
  };
  const availableUsers = currentUser
    ? [
        currentUser,
        ...userOptions.filter((option) => option.value !== currentUser.value),
      ]
    : userOptions;
  const matchingRoles = boot.roles.filter((item: any) =>
    `${item.label} ${item.value}`
      .toLocaleLowerCase()
      .includes(roleSearch.trim().toLocaleLowerCase()),
  );

  return (
    <Workspace.Provider value={{ setMessage }}>
      <div className="aam-pa">
        {!postId ? (
          <div className="aam-pa-empty">
            <span
              className="dashicons dashicons-admin-post"
              aria-hidden="true"
            />
            <strong>{t("Save this post as a draft first")}</strong>
            <p>
              {t(
                "Once the post has an ID, you can define access rules for roles, users, visitors, and defaults here.",
              )}
            </p>
          </div>
        ) : !levels.length ? (
          <p>{t("You do not have permission to manage any access levels.")}</p>
        ) : (
          <>
            <div className="aam-pa-workspace">
              <aside className="aam-pa-sidebar" aria-label={t("Access levels")}>
                <div className="aam-pa-sidebar-title">
                  <strong>{t("Access levels")}</strong>
                  <span>#{postId}</span>
                </div>
                <div
                  className="aam-pa-audience"
                  role="group"
                  aria-label={t("Access level type")}
                >
                  {levels.map((item) => (
                    <button
                      type="button"
                      key={item.type}
                      className={audience === item.type ? "is-active" : ""}
                      aria-pressed={audience === item.type}
                      disabled={busy}
                      onClick={() => {
                        if (audience !== item.type)
                          guardChange(() => {
                            setPendingRestoredUser(null);
                            setAudience(item.type);
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
                {(audience === "role" || audience === "user") && (
                  <div className="aam-pa-directory">
                    <label htmlFor="aam-pa-search">
                      {audience === "role"
                        ? t("Find a role")
                        : t("Find a user")}
                    </label>
                    <div className="aam-pa-search">
                      <span
                        className="dashicons dashicons-search"
                        aria-hidden="true"
                      />
                      <input
                        id="aam-pa-search"
                        type="search"
                        value={audience === "role" ? roleSearch : userSearch}
                        placeholder={
                          audience === "role"
                            ? t("Search roles…")
                            : t("Search users…")
                        }
                        onChange={(event) =>
                          audience === "role"
                            ? setRoleSearch(event.target.value)
                            : setUserSearch(event.target.value)
                        }
                        onKeyDown={(event) => {
                          if (event.key === "Enter") event.preventDefault();
                        }}
                      />
                    </div>
                    <div
                      className="aam-pa-directory-list"
                      role="group"
                      aria-label={audience === "role" ? t("Roles") : t("Users")}
                    >
                      {audience === "role" ? (
                        matchingRoles.length ? (
                          matchingRoles.map((item: any) => (
                            <button
                              type="button"
                              key={item.value}
                              className={
                                role === item.value ? "is-selected" : ""
                              }
                              aria-pressed={role === item.value}
                              disabled={busy}
                              onClick={() => {
                                if (role !== item.value)
                                  guardChange(() => setRole(item.value));
                              }}
                            >
                              <span
                                className="aam-pa-directory-icon dashicons dashicons-groups"
                                aria-hidden="true"
                              />
                              <span className="aam-pa-directory-copy">
                                <strong>{plain(item.label)}</strong>
                                <small>{plain(item.value)}</small>
                              </span>
                              {role === item.value && (
                                <span
                                  className="dashicons dashicons-yes-alt aam-pa-selected-mark"
                                  aria-hidden="true"
                                />
                              )}
                            </button>
                          ))
                        ) : (
                          <p className="aam-pa-no-results">
                            {t("No matching roles")}
                          </p>
                        )
                      ) : usersLoading && !availableUsers.length ? (
                        <div className="aam-pa-list-loading" role="status">
                          <Spinner /> {t("Loading users…")}
                        </div>
                      ) : availableUsers.length ? (
                        availableUsers.map((item: any) => (
                          <button
                            type="button"
                            key={item.value}
                            className={
                              String(user?.id) === item.value
                                ? "is-selected"
                                : ""
                            }
                            aria-pressed={String(user?.id) === item.value}
                            disabled={busy}
                            onClick={() => {
                              if (String(user?.id) !== item.value)
                                guardChange(() => {
                                  setPendingRestoredUser(null);
                                  setUser({
                                    id: Number(item.value),
                                    name: item.name,
                                    login: item.login,
                                  });
                                });
                            }}
                          >
                            <span
                              className="aam-pa-directory-icon dashicons dashicons-admin-users"
                              aria-hidden="true"
                            />
                            <span className="aam-pa-directory-copy">
                              <strong>{item.name}</strong>
                              <small>
                                {item.login ? `@${item.login} · ` : ""}#
                                {item.value}
                              </small>
                            </span>
                            {String(user?.id) === item.value && (
                              <span
                                className="dashicons dashicons-yes-alt aam-pa-selected-mark"
                                aria-hidden="true"
                              />
                            )}
                          </button>
                        ))
                      ) : (
                        <p className="aam-pa-no-results">
                          {t("No matching users")}
                        </p>
                      )}
                    </div>
                    {audience === "user" && (
                      <small className="aam-pa-directory-hint">
                        {usersLoading
                          ? t("Updating results…")
                          : t("Search to find more users")}
                      </small>
                    )}
                  </div>
                )}
                {(audience === "visitor" || audience === "default") && (
                  <p className="aam-pa-level-hint">
                    {audience === "visitor"
                      ? t("Rules for people who are not signed in.")
                      : t(
                          "Baseline rules for everyone without a more specific rule.",
                        )}
                  </p>
                )}
              </aside>
              <section
                className="aam-pa-main"
                aria-label={t("Post access controls")}
              >
                <div className="aam-pa-main-heading">
                  <div>
                    <span className="aam-pa-kicker">{t("POST ACCESS")}</span>
                    <h3>{t("Access controls")}</h3>
                  </div>
                  <span>{t("This post only")}</span>
                </div>
                {!subject && pendingRestoredUser && audience === "user" ? (
                  <div className="aam-pa-loading" role="status">
                    <Spinner /> {t("Restoring selected user…")}
                  </div>
                ) : !subject ? (
                  <div className="aam-pa-empty aam-pa-select-prompt">
                    <span
                      className="dashicons dashicons-arrow-up-alt"
                      aria-hidden="true"
                    />
                    <strong>
                      {audience === "role"
                        ? t("Select a role to see its rules")
                        : t("Select a user to see their rules")}
                    </strong>
                    <p>
                      {t(
                        "Your changes will apply only to the audience you choose.",
                      )}
                    </p>
                  </div>
                ) : loading ||
                  (loadedFor !== subjectKey && failedFor !== subjectKey) ? (
                  <div className="aam-pa-loading" role="status">
                    <Spinner /> {t("Loading access rules…")}
                  </div>
                ) : data && loadedFor === subjectKey ? (
                  <>
                    <div className="aam-pa-state">
                      <div className="aam-pa-state-text">
                        <span
                          className="dashicons dashicons-shield"
                          aria-hidden="true"
                        />
                        <strong>{plain(subject.name)}</strong>
                      </div>
                      {data.is_customized && (
                        <span
                          className="aam-pa-customized-mark"
                          role="img"
                          aria-label={t("Customized access rules")}
                          title={t("Customized access rules")}
                        >
                          <span
                            className="dashicons dashicons-edit"
                            aria-hidden="true"
                          />
                        </span>
                      )}
                    </div>
                    <fieldset className="aam-pa-fields" disabled={busy}>
                      <div className="aam-pa-controls">
                        {availableControls.map((control) => {
                          const on =
                            permissions[control.key]?.effect === "deny";
                          return (
                            <div
                              className={
                                on ? "aam-pa-control is-on" : "aam-pa-control"
                              }
                              key={control.key}
                            >
                              <span
                                className={`aam-pa-control-icon dashicons dashicons-${control.icon}`}
                                aria-hidden="true"
                              />
                              <div className="aam-pa-control-copy">
                                <strong>{t(control.title)}</strong>
                                <small>{t(control.note)}</small>
                                <AccessOutcome
                                  effect={permissions[control.key]?.effect}
                                  customized={
                                    !!data.explicit_permissions?.[control.key]
                                  }
                                  pending={!!dirty[control.key]}
                                />
                              </div>
                              <div className="aam-pa-toggle-wrap">
                                <ToggleControl
                                  className="aam-pa-toggle"
                                  label={t(control.title)}
                                  checked={on}
                                  onChange={(checked) =>
                                    setPermission(control.key, {
                                      ...(permissions[control.key] || {}),
                                      effect: checked ? "deny" : "allow",
                                      ...(control.key === "list" &&
                                      checked &&
                                      !permissions.list?.on?.length
                                        ? { on: ["frontend", "backend", "api"] }
                                        : {}),
                                    })
                                  }
                                />
                                <span
                                  className="aam-pa-toggle-state"
                                  aria-hidden="true"
                                >
                                  {on ? t("On") : t("Off")}
                                </span>
                              </div>
                            </div>
                          );
                        })}
                      </div>
                      <ContentCustomizations
                        permissions={permissions}
                        set={setPermission}
                        requireInputs={false}
                        premium={
                          boot.premiumUi?.content ? boot.premiumUi : null
                        }
                        authorExceptions={["role", "default"].includes(
                          audience,
                        )}
                        authorPermissionKeys={availableControls.map(
                          (control) => control.key,
                        )}
                      />
                    </fieldset>
                    <div className="aam-pa-footer">
                      <span>
                        {changed
                          ? t("Unsaved changes")
                          : t(
                              "Changes to this post are saved separately from the post editor.",
                            )}
                      </span>
                      <div>
                        {changed && (
                          <Button
                            type="button"
                            variant="tertiary"
                            disabled={busy}
                            onClick={() => {
                              setPermissions(data.permissions || {});
                              setDirty({});
                            }}
                          >
                            {t("Discard")}
                          </Button>
                        )}
                        {data.is_customized && (
                          <Button
                            type="button"
                            variant="secondary"
                            disabled={busy || changed}
                            onClick={() => setResetOpen(true)}
                          >
                            {t("Reset overrides")}
                          </Button>
                        )}
                        <Button
                          type="button"
                          variant="primary"
                          disabled={busy || !changed}
                          onClick={save}
                        >
                          {saving ? t("Saving…") : t("Save access rules")}
                        </Button>
                      </div>
                    </div>
                  </>
                ) : (
                  <div className="aam-pa-empty">
                    <strong>{t("Access rules could not be loaded")}</strong>
                    <Button
                      type="button"
                      variant="secondary"
                      onClick={() => {
                        setFailedFor("");
                        request(postPath)
                          .then((result) => {
                            setData(result);
                            setLoadedFor(subjectKey);
                            setPermissions(result.permissions || {});
                          })
                          .catch((error) => {
                            setFailedFor(subjectKey);
                            setMessage({
                              status: "error",
                              text: error.message,
                            });
                          });
                      }}
                    >
                      {t("Try again")}
                    </Button>
                  </div>
                )}
              </section>
            </div>
          </>
        )}
      </div>
      {resetOpen && (
        <ConfirmationDialog
          title={t("Reset post access overrides")}
          confirmLabel={t("Reset overrides")}
          cancelLabel={t("Keep overrides")}
          busy={saving}
          onClose={() => setResetOpen(false)}
          onConfirm={reset}
          impact={t(
            "This access level will inherit its post rules again. Other access levels are not affected.",
          )}
        >
          {t("Remove the custom post rules for")}{" "}
          <strong>{plain(subject?.name)}</strong>?
        </ConfirmationDialog>
      )}
      {message && (
        <div className="aam-pa-toast-region">
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

const root = document.getElementById("aam-post-access-root");
if (root) {
  wp.element
    .createRoot(root)
    .render(
      <PostAccessMetabox
        postId={Number(root.dataset.postId) || 0}
        boot={window.aamPostAccessBootstrap || { roles: [], levels: {} }}
      />,
    );
}
