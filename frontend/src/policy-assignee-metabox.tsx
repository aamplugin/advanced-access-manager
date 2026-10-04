import {
  useState,
  useEffect,
  Workspace,
  Spinner,
  ToggleControl,
  t,
  plain,
  arr,
  request,
  mutate,
  pathFor,
  FrontendErrorBoundary,
} from "./core";
import { Toast } from "./toast";

const levelTypes = [
  { type: "role", label: "Roles", icon: "groups" },
  { type: "user", label: "Users", icon: "admin-users" },
  { type: "visitor", label: "Visitors", icon: "admin-site-alt3" },
  { type: "default", label: "Default", icon: "admin-generic" },
];

function PolicyAssignee({ policyId, boot }: any) {
  const levels = levelTypes.filter((level) => boot.levels[level.type]);
  const [level, setLevel] = useState(levels[0]?.type || "");
  const [roles, setRoles] = useState([]);
  const [rolesLoading, setRolesLoading] = useState(false);
  const [rolesLoaded, setRolesLoaded] = useState(false);
  const [rolesError, setRolesError] = useState(false);
  const [rolesRetry, setRolesRetry] = useState(0);
  const [roleSearch, setRoleSearch] = useState("");
  const [users, setUsers] = useState([]);
  const [usersLoading, setUsersLoading] = useState(false);
  const [usersLoaded, setUsersLoaded] = useState(false);
  const [usersError, setUsersError] = useState(false);
  const [usersRetry, setUsersRetry] = useState(0);
  const [userSearch, setUserSearch] = useState("");
  const [userPage, setUserPage] = useState(0);
  const [userTotal, setUserTotal] = useState(0);
  const [general, setGeneral] = useState(boot.attached || {});
  const [pending, setPending] = useState("");
  const [message, setMessage] = useState(null);

  useEffect(() => {
    if (!boot.levels.role) return;
    const controller = new AbortController();
    let live = true;
    setRolesLoading(true);
    setRolesError(false);
    request(
      `/aam/v2/roles?fields=permissions&context=policy_assignee&policy_id=${policyId}`,
      { signal: controller.signal },
    )
      .then((result) => {
        if (live) {
          setRoles(arr(result));
          setRolesLoaded(true);
        }
      })
      .catch((error) => {
        if (live && error.name !== "AbortError") {
          setRolesLoaded(true);
          setRolesError(true);
          setMessage({
            status: "error",
            text: error.message || t("Roles could not be loaded."),
          });
        }
      })
      .finally(() => {
        if (live) setRolesLoading(false);
      });
    return () => {
      live = false;
      controller.abort();
    };
  }, [policyId, rolesRetry]);

  useEffect(() => {
    if (level !== "user") return;
    const controller = new AbortController();
    let live = true;
    const timer = setTimeout(
      () => {
        setUsersLoading(true);
        setUsersError(false);
        const query = new URLSearchParams({
          fields: "display_name,user_login,permissions",
          context: "policy_assignee",
          policy_id: String(policyId),
          search: userSearch,
          per_page: "20",
          offset: String(userPage * 20),
        });
        request(`/aam/v2/users?${query}`, { signal: controller.signal })
          .then((result) => {
            if (live) {
              setUsers((current) =>
                userPage === 0 ? arr(result) : [...current, ...arr(result)],
              );
              setUserTotal(
                result.summary?.filtered_count ?? arr(result).length,
              );
              setUsersLoaded(true);
            }
          })
          .catch((error) => {
            if (live && error.name !== "AbortError") {
              setUsersLoaded(true);
              setUsersError(true);
              setMessage({
                status: "error",
                text: error.message || t("Users could not be loaded."),
              });
            }
          })
          .finally(() => {
            if (live) setUsersLoading(false);
          });
      },
      userSearch ? 250 : 0,
    );
    return () => {
      live = false;
      clearTimeout(timer);
      controller.abort();
    };
  }, [level, policyId, userSearch, userPage, usersRetry]);

  const toggle = async (type: string, id: any, attached: boolean) => {
    const key = `${type}:${id ?? ""}`;
    if (pending) return;
    setPending(key);
    try {
      const response = await mutate(
        pathFor(`/policy/${policyId}`, { type, id }),
        "PUT",
        { effect: attached ? "attach" : "detach" },
      );
      if (typeof response?.is_attached !== "boolean")
        throw Error(t("The server did not confirm this change."));
      if (type === "role")
        setRoles((current) =>
          current.map((item) =>
            item.slug === id
              ? { ...item, is_attached: response.is_attached }
              : item,
          ),
        );
      else if (type === "user")
        setUsers((current) =>
          current.map((item) =>
            item.id === id
              ? { ...item, is_attached: response.is_attached }
              : item,
          ),
        );
      else
        setGeneral((current) => ({
          ...current,
          [type]: response.is_attached,
        }));
      setMessage({
        status: "success",
        text: response.is_attached
          ? t("Policy attached to this access level.")
          : t("Policy detached from this access level."),
      });
    } catch (error) {
      setMessage({
        status: "error",
        text: error.message || t("Policy assignment could not be saved."),
      });
    } finally {
      setPending("");
    }
  };

  const matchingRoles = roles.filter((item: any) =>
    `${plain(item.name)} ${item.slug}`
      .toLocaleLowerCase()
      .includes(roleSearch.trim().toLocaleLowerCase()),
  );
  const rows = level === "role" ? matchingRoles : users;
  const loading = level === "role" ? rolesLoading : usersLoading;
  const loaded = level === "role" ? rolesLoaded : usersLoaded;

  const assignment = (item: any) => {
    const type = level;
    const id = type === "role" ? item.slug : item.id;
    const name = plain(type === "role" ? item.name : item.display_name);
    const attached = Boolean(item.is_attached);
    const allowed = item.permissions?.includes(
      type === "role" ? "toggle_role_policy" : "toggle_user_policy",
    );
    return (
      <div
        className={`aam-policy-assignee-row${attached ? " is-attached" : ""}`}
        key={`${type}:${id}`}
      >
        <span className="aam-policy-assignee-avatar" aria-hidden="true">
          <span
            className={`dashicons dashicons-${type === "role" ? "groups" : "admin-users"}`}
          />
        </span>
        <span className="aam-policy-assignee-row-copy">
          <strong>{name}</strong>
          <small>
            {type === "role" ? item.slug : `@${item.user_login || id}`}
          </small>
        </span>
        <ToggleControl
          className="aam-policy-assignee-toggle"
          label={`${attached ? t("Detach") : t("Attach")} ${name}`}
          checked={attached}
          disabled={!allowed || Boolean(pending)}
          onChange={(checked) => toggle(type, id, checked)}
        />
      </div>
    );
  };

  return (
    <Workspace.Provider value={{ setMessage }}>
      <div className="aam-policy-assignee">
        <div className="aam-policy-assignee-intro">
          <span
            className="aam-policy-assignee-mark dashicons dashicons-shield"
            aria-hidden="true"
          />
          <div>
            <strong>{t("Apply this policy to")}</strong>
            <p>{t("Choose who receives these rules.")}</p>
          </div>
        </div>
        {levels.length ? (
          <>
            <div
              className="aam-policy-assignee-tabs"
              role="group"
              aria-label={t("Access level type")}
            >
              {levels.map((item) => (
                <button
                  type="button"
                  key={item.type}
                  className={level === item.type ? "is-active" : ""}
                  aria-pressed={level === item.type}
                  onClick={() => setLevel(item.type)}
                >
                  <span
                    className={`dashicons dashicons-${item.icon}`}
                    aria-hidden="true"
                  />
                  {t(item.label)}
                </button>
              ))}
            </div>
            {(level === "role" || level === "user") && (
              <div className="aam-policy-assignee-directory">
                <label htmlFor="aam-policy-assignee-search">
                  {level === "role" ? t("Find a role") : t("Find a user")}
                </label>
                <div className="aam-policy-assignee-search">
                  <span
                    className="dashicons dashicons-search"
                    aria-hidden="true"
                  />
                  <input
                    id="aam-policy-assignee-search"
                    type="search"
                    value={level === "role" ? roleSearch : userSearch}
                    placeholder={
                      level === "role" ? t("Search roles…") : t("Search users…")
                    }
                    onChange={(event) => {
                      if (level === "role") setRoleSearch(event.target.value);
                      else {
                        setUsers([]);
                        setUsersLoaded(false);
                        setUserPage(0);
                        setUserSearch(event.target.value);
                      }
                    }}
                    onKeyDown={(event) => {
                      if (event.key === "Enter") event.preventDefault();
                    }}
                  />
                </div>
                <div className="aam-policy-assignee-list" aria-busy={loading}>
                  {rows.length ? (
                    rows.map(assignment)
                  ) : loading || !loaded ? (
                    <div className="aam-policy-assignee-empty" role="status">
                      <Spinner /> {t("Loading access levels…")}
                    </div>
                  ) : (level === "role" ? rolesError : usersError) ? (
                    <button
                      type="button"
                      className="aam-policy-assignee-more"
                      onClick={() =>
                        level === "role"
                          ? setRolesRetry((current) => current + 1)
                          : setUsersRetry((current) => current + 1)
                      }
                    >
                      {t("Could not load this list. Try again")}
                    </button>
                  ) : (
                    <div className="aam-policy-assignee-empty">
                      {level === "role"
                        ? t("No matching roles")
                        : t("No matching users")}
                    </div>
                  )}
                </div>
                {level === "user" && users.length < userTotal && (
                  <button
                    type="button"
                    className="aam-policy-assignee-more"
                    disabled={usersLoading}
                    onClick={() => setUserPage((current) => current + 1)}
                  >
                    {usersLoading ? t("Loading…") : t("Show more users")}
                  </button>
                )}
                {level === "user" && users.length > 0 && (
                  <small className="aam-policy-assignee-count">
                    {users.length} / {userTotal} {t("users")}
                  </small>
                )}
              </div>
            )}
            {(level === "visitor" || level === "default") && (
              <div className="aam-policy-assignee-global">
                <span
                  className={`dashicons dashicons-${level === "visitor" ? "admin-site-alt3" : "admin-generic"}`}
                  aria-hidden="true"
                />
                <strong>
                  {level === "visitor" ? t("Visitors") : t("Default access")}
                </strong>
                <p>
                  {level === "visitor"
                    ? t("Apply to people who are not signed in.")
                    : t(
                        "Apply as a baseline for everyone unless a more specific rule takes precedence.",
                      )}
                </p>
                <div className="aam-policy-assignee-global-switch">
                  <span>
                    {general[level]
                      ? t("Policy attached")
                      : t("Policy not attached")}
                  </span>
                  <ToggleControl
                    className="aam-policy-assignee-toggle"
                    label={
                      general[level] ? t("Detach policy") : t("Attach policy")
                    }
                    checked={Boolean(general[level])}
                    disabled={Boolean(pending)}
                    onChange={(checked) => toggle(level, null, checked)}
                  />
                </div>
              </div>
            )}
          </>
        ) : (
          <p className="aam-policy-assignee-empty">
            {t("You do not have permission to manage any access levels.")}
          </p>
        )}
      </div>
      {message && (
        <div className="aam-policy-assignee-toast-region">
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

const root = document.getElementById("aam-policy-assignee-root");
if (root) {
  wp.element
    .createRoot(root)
    .render(
      <FrontendErrorBoundary>
        <PolicyAssignee
          policyId={Number(root.dataset.policyId)}
          boot={window.aamPolicyAssigneeBootstrap || { levels: {}, attached: {} }}
        />
      </FrontendErrorBoundary>,
    );
}
