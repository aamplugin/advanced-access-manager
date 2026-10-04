import {
  useState,
  useEffect,
  useRef,
  Workspace,
  Button,
  Notice,
  Modal,
  Spinner,
  Title,
  Empty,
  Reset,
  Badge,
  ActionTooltip,
  Confirm,
  t,
  loadContext,
  seed,
  mutate,
  request,
  arr,
  plain,
} from "./core";
import { PermissionList, Capabilities } from "./permissions";
import { Redirect, UrlRules, Policies, Tokens } from "./rules";
import { People, Identity, SelectedUserTools, RoleEditor } from "./people";
import { Content } from "./content";
import { Settings, Audit } from "./settings";
import { Extensions } from "./extensions";
import { Abilities } from "./abilities";
import { refreshAfterMutation } from "./resource-refresh.mjs";
import { mutationToast } from "./mutation-toast.mjs";
import {
  accessLevelStorageKey,
  readAccessLevel,
  rememberAccessLevel,
  forgetAccessLevel,
  shouldRestoreAccessLevel,
} from "./access-level-memory.mjs";
import { Toast } from "./toast";
import { nextSelectableRole } from "./people-query.mjs";
const icons: any = {
  admin_menu: "menu",
  toolbar: "admin-generic",
  metabox: "layout",
  widget: "screenoptions",
  capability: "admin-network",
  post: "admin-post",
  route: "rest-api",
  ability: "networking",
  url: "admin-links",
  policy: "media-code",
  identity: "groups",
  jwt: "privacy",
  login_redirect: "migrate",
  logout_redirect: "exit",
  "404redirect": "warning",
  redirect: "randomize",
  welcome: "welcome-learn-more",
};
class ErrorBoundary extends wp.element.Component {
  state = { error: null };
  static getDerivedStateFromError(error) {
    return { error };
  }
  render() {
    if (this.state.error)
      return (
        <Notice status="error" isDismissible={false}>
          {t("This screen could not be displayed.")} {this.state.error.message}
        </Notice>
      );
    return this.props.children;
  }
}
function Screen({ id }: any) {
  if (["admin_menu", "toolbar", "metabox", "widget", "route"].includes(id))
    return <PermissionList id={id} />;
  if (id === "capability") return <Capabilities />;
  if (id === "ability") return <Abilities />;
  if (
    ["login_redirect", "logout_redirect", "404redirect", "redirect"].includes(
      id,
    )
  )
    return <Redirect id={id} />;
  if (id === "url") return <UrlRules />;
  if (id === "policy") return <Policies />;
  if (id === "jwt") return <Tokens />;
  if (id === "identity") return <Identity />;
  if (id === "post") return <Content />;
  if (id === "settings") return <Settings />;
  if (id === "audit") return <Audit />;
  if (id === "roles") return <People type="role" />;
  if (id === "users") return <People type="user" />;
  if (id === "extensions") return <Extensions />;
  return <Welcome id={id} />;
}
function Welcome({ id }: any) {
  const { boot, navigate } = wp.element.useContext(Workspace);
  const custom = boot.features.find((x) => x.id === id);
  if (id !== "welcome")
    return (
      <>
        <Title
          title={custom?.title || id}
          icon={icons[id] || "admin-plugins"}
        />
        <Empty title={t("Extension service")}>
          {t("This extension has not registered a workspace view.")}
        </Empty>
      </>
    );
  const firstService =
    boot.features.find((x) => x.id === "admin_menu")?.id ||
    boot.features.find((x) => x.id !== "welcome")?.id;
  const videos = [
    {
      title: "Introduction to AAM",
      description: "See how access levels and services fit together.",
      url: "https://aamporta.com/video/advanced-access-manager-ui-overview-for-wordpress/",
      icon: "welcome-learn-more",
    },
    {
      title: "Roles and capabilities",
      description: "Learn what WordPress grants before adding AAM rules.",
      url: "https://aamportal.com/video/managing-roles-and-capabilities-in-wordpress",
      icon: "groups",
    },
    {
      title: "Backend menu access",
      description: "Follow a practical example for a role or user.",
      url: "https://aamportal.com/video/secure-wordpress-backend-menu-with-aam",
      icon: "menu",
    },
  ];
  return (
    <div className="ar-onboarding">
      <section className="ar-welcome" aria-labelledby="ar-welcome-title">
        <div className="ar-welcome-copy">
          <Badge tone="purple">{t("Welcome to AAM")}</Badge>
          <h2 id="ar-welcome-title">
            {t("Make your first access decision with confidence.")}
          </h2>
          <p>
            {t(
              "Choose whose access you are managing, set one rule, and review the result. These short resources explain the ideas as you go.",
            )}
          </p>
          {firstService && (
            <Button variant="primary" onClick={() => navigate(firstService)}>
              {t("Start managing access")}
            </Button>
          )}
        </div>
        <a
          className="ar-welcome-featured"
          href="https://aamportal.com/video/introduction-to-aam"
          target="_blank"
          rel="noopener noreferrer"
        >
          <span className="ar-welcome-featured-art" aria-hidden="true">
            <span className="dashicons dashicons-controls-play" />
          </span>
          <span className="ar-welcome-featured-label">
            {t("Start with a video")}
          </span>
          <strong>{t("Introduction to Advanced Access Manager")}</strong>
          <span className="ar-welcome-featured-link">
            {t("Watch introduction")} <span aria-hidden="true">↗</span>
          </span>
        </a>
      </section>

      <section
        className="ar-onboarding-section"
        aria-labelledby="ar-first-steps-title"
      >
        <div className="ar-onboarding-section-title">
          <div>
            <span className="ar-onboarding-eyebrow">
              {t("A good first session")}
            </span>
            <h3 id="ar-first-steps-title">
              {t("Three steps to get oriented")}
            </h3>
          </div>
          <span className="ar-onboarding-section-note">
            {t("Start small; refine as you learn.")}
          </span>
        </div>
        <div className="ar-onboarding-steps">
          <div className="ar-onboarding-step">
            <span className="ar-onboarding-step-number">01</span>
            <h4>{t("Choose an access level")}</h4>
            <p>
              {t(
                "Use the selector above to choose a role, user, visitors, or default access.",
              )}
            </p>
          </div>
          <div className="ar-onboarding-step">
            <span className="ar-onboarding-step-number">02</span>
            <h4>{t("Set one clear rule")}</h4>
            <p>
              {t(
                "Open a service and make one change for the access level you selected.",
              )}
            </p>
            {firstService && (
              <button
                type="button"
                className="ar-onboarding-text-action"
                onClick={() => navigate(firstService)}
              >
                {t("Open access controls")} <span aria-hidden="true">→</span>
              </button>
            )}
          </div>
          <div className="ar-onboarding-step">
            <span className="ar-onboarding-step-number">03</span>
            <h4>{t("Review inheritance")}</h4>
            <p>
              {t(
                "See how default, role, and user settings combine before adding more rules.",
              )}
            </p>
            <a
              href="https://aamportal.com/article/understanding-access-controls-inheritance"
              target="_blank"
              rel="noopener noreferrer"
            >
              {t("Read the guide")} <span aria-hidden="true">↗</span>
            </a>
          </div>
        </div>
      </section>

      <section
        className="ar-onboarding-section"
        aria-labelledby="ar-learning-title"
      >
        <div className="ar-onboarding-section-title">
          <div>
            <span className="ar-onboarding-eyebrow">
              {t("Learn at your pace")}
            </span>
            <h3 id="ar-learning-title">{t("Watch and explore")}</h3>
          </div>
          <a
            href="https://aamportal.com/documentation"
            target="_blank"
            rel="noopener noreferrer"
          >
            {t("Browse all documentation")} <span aria-hidden="true">↗</span>
          </a>
        </div>
        <div className="ar-onboarding-video-grid">
          {videos.map((video) => (
            <a
              key={video.url}
              className="ar-onboarding-video"
              href={video.url}
              target="_blank"
              rel="noopener noreferrer"
            >
              <span className="ar-onboarding-video-icon" aria-hidden="true">
                <span className={`dashicons dashicons-${video.icon}`} />
              </span>
              <span className="ar-onboarding-video-content">
                <span className="ar-onboarding-media-type">{t("Video")}</span>
                <strong>{t(video.title)}</strong>
                <span>{t(video.description)}</span>
              </span>
              <span className="ar-onboarding-video-arrow" aria-hidden="true">
                ↗
              </span>
            </a>
          ))}
        </div>
        <p className="ar-onboarding-video-note">
          {t(
            "Some videos show an earlier AAM interface. The access concepts still apply.",
          )}
        </p>
        <div className="ar-onboarding-reading">
          <span className="dashicons dashicons-media-text" aria-hidden="true" />
          <span>{t("Working with content?")}</span>
          <a
            href="https://aamportal.com/article/about-posts-and-terms-service"
            target="_blank"
            rel="noopener noreferrer"
          >
            {t("Explore Posts & Terms")} <span aria-hidden="true">↗</span>
          </a>
          <a
            href="https://aamportal.com/article/understanding-wordpress-roles-and-capabilities"
            target="_blank"
            rel="noopener noreferrer"
          >
            {t("Understand roles and capabilities")}{" "}
            <span aria-hidden="true">↗</span>
          </a>
        </div>
      </section>
    </div>
  );
}
function App() {
  const [boot, setBoot] = useState(window.aamReactBootstrap);
  const [busy, setBusy] = useState(false);
  const memory = useRef(null);
  if (!memory.current) {
    let storage = null;
    try {
      storage = window.localStorage;
    } catch {
      // Storage is optional; the server context remains usable.
    }
    const key = accessLevelStorageKey(window.aamReactBootstrap);
    memory.current = { storage, key, saved: readAccessLevel(storage, key) };
  }
  const [loading, setLoading] = useState(() =>
    shouldRestoreAccessLevel(
      location.search,
      memory.current.saved,
      window.aamReactBootstrap.subject,
    ),
  );
  const [message, setMessage] = useState(null);
  const [picker, setPicker] = useState(null);
  const [deleteRoleOpen, setDeleteRoleOpen] = useState(false);
  const [roleEditor, setRoleEditor] = useState(null);
  const [roleEditorLoading, setRoleEditorLoading] = useState(false);
  const navigation = useRef(null);
  const generation = useRef(0);
  const active = useRef(boot);
  const busyRef = useRef(false);
  active.current = boot;
  const markBusy = (v: boolean) => {
    busyRef.current = v;
    setBusy(v);
  };
  const changeUrl = (value: any, replace = false) => {
    const url = new URL(location.href);
    url.searchParams.set("aam_level", value.subject.type);
    if (value.subject.id !== null)
      url.searchParams.set("aam_subject", String(value.subject.id));
    else url.searchParams.delete("aam_subject");
    url.searchParams.set("aam_service", value.screen);
    url.hash = "";
    history[replace ? "replaceState" : "pushState"]({}, "", url);
  };
  const navigate = async (
    screen: string,
    subject = active.current.subject,
    pop = false,
    replace = false,
    restore = false,
  ) => {
    if (busyRef.current) {
      setMessage({
        status: "warning",
        text: t(
          "Wait for the current operation to finish before changing context.",
        ),
      });
      if (pop) changeUrl(active.current, true);
      return;
    }
    navigation.current?.abort();
    const controller = new AbortController();
    navigation.current = controller;
    const token = ++generation.current;
    setLoading(true);
    setMessage(null);
    try {
      const next = await loadContext({ ...subject }, screen, controller.signal);
      if (token !== generation.current) return;
      setBoot(next);
      setRoleEditor(null);
      rememberAccessLevel(
        memory.current.storage,
        memory.current.key,
        next.subject,
      );
      setPicker(null);
      if (!pop) changeUrl(next, replace);
      if (next.screen !== screen && !restore)
        setMessage({
          status: "info",
          text: t(
            "The previous service is unavailable at this access level. An available service has been selected.",
          ),
        });
      setTimeout(() => document.getElementById("ar-screen-title")?.focus(), 0);
      return true;
    } catch (e) {
      if (restore && e.name !== "AbortError") {
        if (e.status === 403)
          forgetAccessLevel(memory.current.storage, memory.current.key);
        changeUrl(active.current, true);
      } else if (e.name !== "AbortError")
        setMessage({
          status: "error",
          text: e.message || t("Unable to change context."),
        });
      return false;
    } finally {
      if (token === generation.current) setLoading(false);
    }
  };
  const refreshContext = async () => {
    const current = active.current;
    const next = await loadContext(current.subject, current.screen);
    setBoot(next);
  };
  const write = async (
    path: string,
    method: string,
    data: any,
    returnResponse = false,
  ) => {
    if (busyRef.current) return false;
    markBusy(true);
    const subject = { ...active.current.subject };
    try {
      const response = await mutate(path, method, data);
      if (response && response.success === false) {
        throw new Error(
          t(
            "The server did not confirm this change. Please review the settings and retry.",
          ),
        );
      }
      refreshAfterMutation(path);
      if (active.current.screen === "settings") await refreshContext();
      if (!returnResponse) {
        const description = mutationToast(path, method, data, subject);
        setMessage({
          status: "success",
          text: wp.i18n.sprintf(
            t(description.template),
            ...description.args.map((value: any) => t(plain(value))),
          ),
        });
      }
      return returnResponse ? response || true : true;
    } catch (e) {
      setMessage({
        status: "error",
        text:
          e.message || t("Save failed. Your change has not been confirmed."),
      });
      return false;
    } finally {
      markBusy(false);
    }
  };
  useEffect(() => {
    const saved = memory.current.saved;
    if (shouldRestoreAccessLevel(location.search, saved, boot.subject)) {
      navigate(boot.screen, { ...saved, name: "" }, false, true, true);
    } else {
      rememberAccessLevel(
        memory.current.storage,
        memory.current.key,
        boot.subject,
      );
      changeUrl(boot, true);
    }
    const pop = () => {
      const p = new URLSearchParams(location.search);
      const type = p.get("aam_level") || active.current.subject.type;
      const id = p.get("aam_subject");
      navigate(
        p.get("aam_service") || "admin_menu",
        { type, id: type === "user" ? Number(id) : id, name: "" },
        true,
      );
    };
    const unload = (e) => {
      if (busyRef.current) {
        e.preventDefault();
        e.returnValue = "";
      }
    };
    window.addEventListener("popstate", pop);
    window.addEventListener("beforeunload", unload);
    return () => {
      window.removeEventListener("popstate", pop);
      window.removeEventListener("beforeunload", unload);
      navigation.current?.abort();
    };
  }, []);
  const subject = boot.subject;
  const global = ["settings", "audit", "extensions", "roles", "users"].includes(
    boot.screen,
  );
  const levels = [
    { type: "role", label: "Roles", cap: "manage_roles" },
    { type: "user", label: "Users", cap: "manage_users" },
    { type: "visitor", label: "Visitors", cap: "manage_visitors" },
    { type: "default", label: "Default", cap: "manage_default" },
  ];
  const value = {
    boot,
    busy,
    navigate,
    write,
    setBusy: markBusy,
    setMessage,
    refreshContext,
  };
  return (
    <Workspace.Provider value={value}>
      <div className="ar-app">
        <header className="ar-header">
          <div className="ar-brand">
            <img className="ar-brand-mark" src={boot.brandLogoUrl} alt="" />
            <div>
              <h1>{t("Access Governance for WordPress")}</h1>
              <span>{t("Advanced Access Manager")}</span>
            </div>
          </div>
          <div className="ar-header-links">
            <Badge>{boot.siteName}</Badge>
            <a
              href="https://aamportal.com/documentation"
              target="_blank"
              rel="noreferrer"
            >
              {t("Documentation")} <span aria-hidden="true">↗</span>
            </a>
          </div>
        </header>
        <nav className="ar-topnav" aria-label={t("AAM sections")}>
          {[
            {
              id: "admin_menu",
              label: "Access Controls",
              show: true,
              active: !global || ["roles", "users"].includes(boot.screen),
            },
            {
              id: "audit",
              label: "Security Audit",
              show: boot.caps.trigger_audit,
            },
            {
              id: "settings",
              label: "Settings",
              show: boot.caps.manage_settings,
            },
            {
              id: "extensions",
              label: "Extensions",
              show: boot.caps.manage_addons,
            },
          ]
            .filter((x) => x.show)
            .map((x) => (
              <button
                key={x.id}
                className={x.active || boot.screen === x.id ? "is-active" : ""}
                aria-current={
                  x.active || boot.screen === x.id ? "page" : undefined
                }
                disabled={busy || loading}
                onClick={() => navigate(x.id)}
              >
                {t(x.label)}
              </button>
            ))}
          <span className="ar-version">{boot.version}</span>
        </nav>
        {!global && (
          <section className="ar-context" aria-label={t("Access context")}>
            <div className="ar-context-body">
              <div className="ar-selected" aria-live="polite">
                <span className="ar-selected-icon" aria-hidden="true">
                  <span
                    className={
                      "dashicons dashicons-" +
                      (subject.type === "user"
                        ? "admin-users"
                        : subject.type === "role"
                          ? "groups"
                          : "admin-site-alt3")
                    }
                  />
                </span>
                <div className="ar-selected-copy">
                  <span className="ar-selected-label">
                    {t("EDITING ACCESS FOR")}
                  </span>
                  <strong>{plain(subject.name)}</strong>
                  <span className="ar-selected-detail">
                    <span className="ar-selected-type">
                      {t(
                        subject.type === "role"
                          ? "Role"
                          : subject.type === "user"
                            ? "User"
                            : subject.type === "visitor"
                              ? "Visitors"
                              : "Default access",
                      )}
                    </span>
                    {(subject.type === "role" || subject.type === "user") && (
                      <span className="ar-selected-id">
                        {t("ID")}{" "}
                        <b>
                          {subject.type === "user" ? "#" : ""}
                          {plain(subject.id)}
                        </b>
                      </span>
                    )}
                    {subject.type === "user" && subject.email && (
                      <span
                        className="ar-selected-email"
                        title={plain(subject.email)}
                      >
                        <span
                          className="dashicons dashicons-email-alt"
                          aria-hidden="true"
                        />
                        <span>{plain(subject.email)}</span>
                      </span>
                    )}
                  </span>
                </div>
                {(subject.type === "role" || subject.type === "user") && (
                  <div className="ar-selected-actions">
                    {subject.type === "user" ? (
                      <Button
                        variant="secondary"
                        href={
                          boot.adminUrl +
                          "user-edit.php?user_id=" +
                          encodeURIComponent(subject.id)
                        }
                      >
                        <span
                          className="dashicons dashicons-edit"
                          aria-hidden="true"
                        />
                        {t("Edit profile")}
                      </Button>
                    ) : (
                      <>
                        <ActionTooltip
                          text={
                            boot.caps.edit_roles
                              ? t("Edit role")
                              : t("You do not have permission to edit roles.")
                          }
                          disabled={!boot.caps.edit_roles}
                        >
                          <Button
                            variant="secondary"
                            className="ar-selected-edit-role"
                            aria-label={t("Edit role")}
                            disabled={
                              busy ||
                              loading ||
                              roleEditorLoading ||
                              !boot.caps.edit_roles
                            }
                            onClick={async () => {
                              setRoleEditorLoading(true);
                              try {
                                const canListRoles = !!boot.caps.list_roles;
                                const fields = boot.roleParentSupported
                                  ? "permissions,user_count,parent"
                                  : "permissions,user_count";
                                const roles = canListRoles
                                  ? arr(
                                      await request(
                                        "/aam/v2/roles?fields=" + fields,
                                      ),
                                    )
                                  : (boot.roleOptions || []).map((role) => ({
                                      slug: role.value,
                                      name: role.label,
                                    }));
                                if (
                                  active.current.subject.type !== "role" ||
                                  active.current.subject.id !== subject.id
                                )
                                  return;
                                const current = canListRoles
                                  ? roles.find(
                                      (role) => role.slug === subject.id,
                                    )
                                  : {
                                      slug: subject.id,
                                      name: subject.name,
                                      user_count: subject.userCount,
                                      permissions: [
                                        "allow_edit",
                                        ...(Number(subject.userCount) === 0
                                          ? ["allow_slug_update"]
                                          : []),
                                      ],
                                    };
                                if (!current)
                                  throw new Error(
                                    t("This role is no longer available."),
                                  );
                                if (
                                  !current.permissions?.includes("allow_edit")
                                )
                                  throw new Error(
                                    t(
                                      "You do not have permission to edit this role.",
                                    ),
                                  );
                                setRoleEditor({
                                  initial: {
                                    ...current,
                                    original: current.slug,
                                  },
                                  roles,
                                  parentAvailable: canListRoles,
                                });
                              } catch (error) {
                                setMessage({
                                  status: "error",
                                  text:
                                    error.message ||
                                    t("Could not open the role editor."),
                                });
                              } finally {
                                setRoleEditorLoading(false);
                              }
                            }}
                          >
                            <span
                              className="dashicons dashicons-edit"
                              aria-hidden="true"
                            />
                          </Button>
                        </ActionTooltip>
                        <ActionTooltip
                          text={
                            !boot.caps.delete_roles
                              ? t("You do not have permission to delete roles.")
                              : Number(subject.userCount) > 0
                                ? t("This role has users assigned to it.")
                                : undefined
                          }
                          disabled={
                            !boot.caps.delete_roles ||
                            Number(subject.userCount) > 0
                          }
                        >
                          <Button
                            variant="secondary"
                            isDestructive
                            disabled={
                              busy ||
                              loading ||
                              roleEditorLoading ||
                              !boot.caps.delete_roles ||
                              Number(subject.userCount) > 0
                            }
                            onClick={() => setDeleteRoleOpen(true)}
                          >
                            <span
                              className="dashicons dashicons-trash"
                              aria-hidden="true"
                            />
                            {t("Delete role")}
                          </Button>
                        </ActionTooltip>
                      </>
                    )}
                  </div>
                )}
              </div>
              <div className="ar-context-switcher">
                <span className="ar-eyebrow">{t("SWITCH ACCESS LEVEL")}</span>
                <div
                  className="ar-levels"
                  role="group"
                  aria-label={t("Access levels")}
                >
                  {levels
                    .filter((x) => boot.caps[x.cap])
                    .map((level) => {
                      const selectable = ["role", "user"].includes(level.type);
                      return (
                        <Button
                          key={level.type}
                          aria-pressed={subject.type === level.type}
                          aria-label={
                            selectable
                              ? t(
                                  level.type === "role"
                                    ? "Choose a role"
                                    : "Choose a user",
                                )
                              : undefined
                          }
                          className={
                            subject.type === level.type ? "is-active" : ""
                          }
                          disabled={busy || loading}
                          onClick={() => {
                            if (selectable) setPicker(level.type);
                            else if (subject.type !== level.type)
                              navigate(boot.screen, {
                                type: level.type,
                                id: null,
                                name: t(level.label),
                              });
                          }}
                        >
                          <span
                            className={
                              "dashicons dashicons-" +
                              (level.type === "role"
                                ? "groups"
                                : level.type === "user"
                                  ? "admin-users"
                                  : level.type === "visitor"
                                    ? "admin-site-alt3"
                                    : "admin-generic")
                            }
                            aria-hidden="true"
                          />
                          <span>{t(level.label)}</span>
                          {selectable && (
                            <span
                              className="dashicons dashicons-arrow-down-alt2 ar-level-choice-arrow"
                              aria-hidden="true"
                            />
                          )}
                        </Button>
                      );
                    })}
                </div>
              </div>
              {boot.caps.manage_settings && (
                <div className="ar-context-reset">
                  <Reset
                    endpoint="/settings"
                    label={t("Reset access settings")}
                    serviceName={t("Access Settings")}
                    iconOnly
                  />
                </div>
              )}
            </div>
            {subject.type === "user" && subject.id && (
              <SelectedUserTools key={subject.id} userId={subject.id} />
            )}
          </section>
        )}
        <div
          className={"ar-workspace " + (global ? "ar-workspace-global" : "")}
        >
          {!global && (
            <aside className="ar-sidebar">
              <span className="ar-eyebrow">{t("ACCESS SERVICES")}</span>
              <nav aria-label={t("Access services")}>
                {boot.features.map((f) => (
                  <button
                    key={f.id}
                    disabled={busy || loading}
                    className={boot.screen === f.id ? "is-active" : ""}
                    aria-current={boot.screen === f.id ? "page" : undefined}
                    onClick={() => navigate(f.id)}
                  >
                    <span
                      className={
                        "dashicons dashicons-" +
                        (icons[f.id] || "admin-plugins")
                      }
                    />
                    <span>{plain(f.title)}</span>
                    {boot.screen === f.id && <span className="ar-nav-dot" />}
                  </button>
                ))}
              </nav>
              <div className="ar-sidebar-note">
                <span className="dashicons dashicons-shield" />
                <strong>{t("Least privilege. More control.")}</strong>
                <p>
                  {t(
                    "Start with what each identity needs. Review access regularly.",
                  )}
                </p>
              </div>
            </aside>
          )}
          <main className="ar-main" aria-busy={loading || busy}>
            {loading ? (
              <div className="ar-loading" role="status">
                <Spinner />
                {t("Loading access workspace…")}
              </div>
            ) : (
              <ErrorBoundary
                key={subject.type + ":" + subject.id + ":" + boot.screen}
              >
                <Screen
                  key={subject.type + ":" + subject.id + ":" + boot.screen}
                  id={boot.screen}
                />
              </ErrorBoundary>
            )}
          </main>
        </div>
        <footer className="ar-footer">
          <span>{t("Access governance for WordPress")}</span>
          <a href="https://aamportal.com" target="_blank" rel="noreferrer">
            aamportal.com
          </a>
        </footer>
      </div>
      {picker && (
        <Modal
          title={picker === "role" ? t("Select a role") : t("Select a user")}
          onRequestClose={() => setPicker(null)}
          className={`ar-modal ar-modal-picker ar-modal-picker-${picker}`}
        >
          <People
            type={picker}
            onSelect={(subject) => navigate(boot.screen, subject)}
            onRoleCreated={async (role) => {
              if (
                await navigate(boot.screen, {
                  type: "role",
                  id: role.slug,
                  name: role.name,
                })
              ) {
                setMessage({
                  status: "success",
                  text: t("New role created and preselected for you."),
                });
              }
            }}
          />
        </Modal>
      )}
      {roleEditor &&
        subject.type === "role" &&
        roleEditor.initial.slug === subject.id && (
          <RoleEditor
            initial={roleEditor.initial}
            roles={roleEditor.roles}
            parentAvailable={roleEditor.parentAvailable}
            onClose={() => setRoleEditor(null)}
            onSaved={async (updated) => {
              setRoleEditor(null);
              if (
                await navigate(
                  boot.screen,
                  { type: "role", id: updated.slug, name: updated.name },
                  false,
                  true,
                )
              )
                setMessage({
                  status: "success",
                  text: wp.i18n.sprintf(
                    t("Role %s updated."),
                    plain(updated.name),
                  ),
                });
            }}
          />
        )}
      {deleteRoleOpen && subject.type === "role" && (
        <Confirm
          title={t("Delete role")}
          confirmLabel={t("Delete role")}
          cancelLabel={t("Keep role")}
          impact={t(
            "This role will no longer be available. This change cannot be undone.",
          )}
          onClose={() => setDeleteRoleOpen(false)}
          onConfirm={async () => {
            let roles = boot.roleOptions || [];
            try {
              roles = arr(await request("/aam/v2/roles?fields=permissions"));
            } catch {
              // The current role list is enough to choose a fallback.
            }
            const nextRole = nextSelectableRole(roles, subject.id);
            const deleted = await write(
              "/aam/v2/role/" + encodeURIComponent(subject.id),
              "DELETE",
              undefined,
            );
            if (!deleted) return false;
            if (nextRole) {
              await navigate(boot.screen, nextRole);
            } else if (boot.caps.manage_default || boot.caps.manage_visitors) {
              const type = boot.caps.manage_default ? "default" : "visitor";
              await navigate(boot.screen, {
                type,
                id: null,
                name: t(type === "default" ? "Default" : "Visitors"),
              });
            }
            return true;
          }}
        >
          {t("Delete the role")} <strong>{plain(subject.name)}</strong>?
        </Confirm>
      )}
      {message && (
        <div className="ar-toast-region">
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
seed(window.aamReactBootstrap);
wp.element.createRoot(document.getElementById("aam-react-root")).render(
  <ErrorBoundary>
    <App />
  </ErrorBoundary>,
);
