import {
  useState,
  useWorkspace,
  useResource,
  useWrite,
  Resource,
  Button,
  Modal,
  ModalIntro,
  SelectControl,
  TextControl,
  TextareaControl,
  ToggleControl,
  t,
  plain,
  arr,
} from "./core";
import { RedirectFields } from "./rules";
import { needsPageSelection } from "./page-picker.mjs";
import { localDateTimeToUnix, unixToLocalDateTime } from "./date-time.mjs";
import { contentResourcePath } from "./content-query.mjs";
import { changedPermissions } from "./content-permissions.mjs";
import { visibleContentControls } from "./content-control-visibility.mjs";
import { ContentBrowser } from "./content-browser";
import { AccessOutcome } from "./resource-visuals";
import { ConditionalFields, conditionComplete } from "./premium-controls";
export function Content() {
  const types = useResource("/post_types");
  const taxonomies = useResource("/taxonomies");
  const [edit, setEdit] = useState(null);
  return (
    <>
      <Resource resource={types}>
        {(typeData) => (
          <Resource resource={taxonomies}>
            {(taxonomyData) => (
              <ContentBrowser
                types={arr(typeData)}
                taxonomies={arr(taxonomyData)}
                onEdit={setEdit}
              />
            )}
          </Resource>
        )}
      </Resource>
      {edit &&
        (edit.kind === "post" ? (
          <ContentEditor initial={edit.row} onClose={() => setEdit(null)} />
        ) : (
          <ScopedContentEditor target={edit} onClose={() => setEdit(null)} />
        ))}
    </>
  );
}
function ScopedContentEditor({ target, onClose }: any) {
  const endpoint = contentResourcePath(target);
  const resource = useResource(endpoint);
  const { busy } = useWorkspace();
  return (
    <Modal
      title={plain(target.row.title) || t("Content access")}
      onRequestClose={() => !busy && onClose()}
      className="ar-modal ar-modal-wide"
    >
      <Resource resource={resource}>
        {(data) => (
          <ScopedContentForm
            key={endpoint}
            target={target}
            endpoint={endpoint}
            initial={data}
            onClose={onClose}
          />
        )}
      </Resource>
    </Modal>
  );
}
export function ContentPremiumPrompt({ target, premium, onClose }: any) {
  const highlights =
    target.kind === "term"
      ? [
          "Hide terms from lists",
          "Control browsing",
          "Protect edit, delete and assignment",
        ]
      : target.kind === "taxonomy"
        ? [
            "Manage term visibility",
            "Limit browsing and creation",
            "Protect editing",
          ]
        : [
            "Control content visibility",
            "Restrict direct access",
            "Manage creation and publishing",
          ];
  const active = !!premium?.active;
  const enabled = !!premium?.enabled;
  const installed = !!premium?.installed;
  const title =
    active && !enabled
      ? t("Posts & Terms controls are paused")
      : installed && !active
        ? t("Your Premium add-on is ready to activate")
        : active
          ? t("No controls are available for this resource yet")
          : t("More control over this content, when you need it");
  const description =
    active && !enabled
      ? t(
          "The add-on is active, but its Posts & Terms service is turned off. Enable it in AAM Settings to manage this resource here.",
        )
      : installed && !active
        ? t(
            "Activate the installed add-on to manage visibility, browsing and editing for this resource.",
          )
        : active
          ? t(
              "AAM did not receive controls for this resource. Check that the premium Posts & Terms service is enabled, then reopen this screen.",
            )
          : t(
              "AAM Premium adds detailed access rules for post types, taxonomies and terms. The controls appear here as soon as the add-on is active.",
            );
  return (
    <div className="ar-content-premium-empty">
      <div className="ar-content-premium-art" aria-hidden="true">
        <span className="dashicons dashicons-shield" />
        <span className="dashicons dashicons-admin-network" />
      </div>
      <div className="ar-content-premium-copy">
        <span className="ar-content-premium-eyebrow">
          {active ? t("POSTS & TERMS") : t("AVAILABLE WITH AAM PREMIUM")}
        </span>
        <h3>{title}</h3>
        <p>{description}</p>
        {!active && (
          <div
            className="ar-content-premium-highlights"
            aria-label={t("Premium features")}
          >
            {highlights.map((feature) => (
              <span key={feature}>
                <span
                  className="dashicons dashicons-yes-alt"
                  aria-hidden="true"
                />
                {t(feature)}
              </span>
            ))}
          </div>
        )}
        <div className="ar-content-premium-actions">
          {active && !enabled && premium?.settingsUrl && (
            <Button variant="primary" href={premium.settingsUrl}>
              {t("Open AAM Settings")}
            </Button>
          )}
          {installed && !active && premium?.pluginsUrl && (
            <Button variant="primary" href={premium.pluginsUrl}>
              {t("Open Plugins to activate")}
            </Button>
          )}
          {!installed && !active && (
            <Button
              variant="primary"
              href={
                premium?.purchaseUrl ||
                "https://aamportal.com/premium?ref=content-access"
              }
              target="_blank"
              rel="noopener noreferrer"
            >
              {t("Explore Premium")}
            </Button>
          )}
          {onClose && (
            <Button type="button" variant="secondary" onClick={onClose}>
              {t("Maybe later")}
            </Button>
          )}
        </div>
        {!active && !installed && premium?.pluginsUrl && (
          <a className="ar-content-premium-footnote" href={premium.pluginsUrl}>
            {t("Already have Premium? Activate it in Plugins")}
          </a>
        )}
        {active && enabled && (
          <span className="ar-content-premium-footnote">
            {t(
              "This resource has no registered access controls in the current configuration.",
            )}
          </span>
        )}
      </div>
    </div>
  );
}
export function ScopedContentForm({
  target,
  endpoint,
  initial,
  onClose,
  onSaved = onClose,
  onDirtyChange,
  onResetRequest,
}: any) {
  const [permissions, setPermissions] = useState(initial.permissions || {});
  const [dirty, setDirty] = useState({});
  const write = useWrite();
  const { boot, busy, setMessage } = useWorkspace();
  const controls = visibleContentControls(
    Object.entries(initial.controls || {}),
    boot.subject.type,
    ([key]: any) => key,
  );
  wp.element.useEffect(() => {
    onDirtyChange?.(Object.keys(dirty).length > 0);
  }, [dirty]);
  const set = (key: string, value: any) => {
    setPermissions({ ...permissions, [key]: value });
    setDirty({ ...dirty, [key]: true });
  };
  if (!controls.length) {
    return (
      <>
        <ContentPremiumPrompt
          target={target}
          premium={boot.contentPremium}
          onClose={onClose}
        />
        {initial.is_customized && (
          <div className="ar-actions">
            <Button
              type="button"
              variant="secondary"
              disabled={busy}
              onClick={async () => {
                if (onResetRequest) onResetRequest();
                else if (await write(endpoint, "DELETE")) onSaved();
              }}
            >
              {t("Reset to inherited")}
            </Button>
          </div>
        )}
      </>
    );
  }
  return (
    <form
      onSubmit={async (e) => {
        e.preventDefault();
        if (
          permissions.read?.effect === "deny" &&
          permissions.read?.restriction_type === "redirect" &&
          needsPageSelection(permissions.read?.redirect)
        ) {
          setMessage({
            status: "error",
            text: t("Select a page before saving."),
          });
          return;
        }
        if (
          permissions.read?.effect === "deny" &&
          permissions.read?.restriction_type === "conditional" &&
          !conditionComplete(permissions.read.condition)
        ) {
          setMessage({
            status: "error",
            text: t("Complete the condition before saving."),
          });
          return;
        }
        const changes = changedPermissions(permissions, dirty);
        if (
          changes.length &&
          (await write(endpoint, "PATCH", { permissions: changes }))
        ) {
          onSaved();
        }
      }}
    >
      <ModalIntro icon="lock" title={t("Set access for this content")}>
        {t(
          "Choose which actions to deny for this access level. AAM saves only the permissions you change here.",
        )}
      </ModalIntro>
      <div className="ar-content-controls">
        {controls.map(([key, control]: any) => (
          <div className="ar-content-control" key={key}>
            <div>
              <h3>{plain(control.title) || key}</h3>
              {control.description && <p>{plain(control.description)}</p>}
              <AccessOutcome
                effect={permissions[key]?.effect}
                customized={!!initial.explicit_permissions?.[key]}
                pending={!!dirty[key]}
              />
            </div>
            <ToggleControl
              className="ar-toggle"
              label={t("Deny")}
              checked={permissions[key]?.effect === "deny"}
              onChange={(checked) =>
                set(key, {
                  ...(permissions[key] || {}),
                  effect: checked ? "deny" : "allow",
                  ...(key === "list" && checked && !permissions.list?.on?.length
                    ? { on: ["frontend", "backend", "api"] }
                    : {}),
                })
              }
            />
          </div>
        ))}
      </div>
      <ContentCustomizations
        permissions={permissions}
        set={set}
        showList={!!initial.controls?.list}
        showRead={!!initial.controls?.read}
        premium={boot.premiumUi?.content ? boot.premiumUi : null}
        authorExceptions={
          ["default", "role"].includes(boot.subject.type) &&
          (target.kind === "post_type" ||
            (target.kind === "term" && !!target.postType))
        }
        authorPermissionKeys={controls.map(([key]) => key)}
      />
      <div className="ar-actions">
        <Button
          type="submit"
          variant="primary"
          disabled={busy || !Object.keys(dirty).length}
        >
          {t("Save changes")}
        </Button>
        {initial.is_customized && (
          <Button
            type="button"
            variant="secondary"
            disabled={busy}
            onClick={async () => {
              if (onResetRequest) onResetRequest();
              else if (await write(endpoint, "DELETE")) onSaved();
            }}
          >
            {t("Reset to inherited")}
          </Button>
        )}
      </div>
    </form>
  );
}
export function ContentCustomizations({
  permissions,
  set,
  showList = true,
  showRead = true,
  requireInputs = true,
  premium = null,
  authorExceptions = false,
  authorPermissionKeys = [],
}: any) {
  const list = permissions.list || {};
  const read = permissions.read || {};
  const authorRestrictionLabels: Record<string, string> = {
    comment: t("Restrict comments"),
    edit: t("Restrict editing"),
    publish: t("Restrict publishing"),
    delete: t("Restrict deletion"),
  };
  return (
    <>
      {showList && list.effect === "deny" && (
        <fieldset className="ar-fieldset">
          <legend>{t("Hide from")}</legend>
          {[
            { id: "frontend", label: t("Frontend") },
            { id: "backend", label: t("Admin area") },
            { id: "api", label: t("REST API") },
          ].map((area) => (
            <ToggleControl
              key={area.id}
              className="ar-toggle"
              label={area.label}
              checked={(list.on?.length
                ? list.on
                : ["frontend", "backend", "api"]
              ).includes(area.id)}
              onChange={(checked) => {
                const existing = list.on?.length
                  ? list.on
                  : ["frontend", "backend", "api"];
                const on = checked
                  ? [...existing, area.id]
                  : existing.filter((x) => x !== area.id);
                set("list", {
                  ...list,
                  effect: on.length ? "deny" : "allow",
                  on,
                });
              }}
            />
          ))}
          {premium && authorExceptions && (
            <ToggleControl
              className="ar-toggle"
              label={t("Keep visible to its author")}
              checked={!!list.exclude_authors}
              onChange={(exclude_authors) =>
                set("list", { ...list, exclude_authors })
              }
            />
          )}
        </fieldset>
      )}
      {showRead && read.effect === "deny" && (
        <fieldset className="ar-fieldset">
          <legend>{t("Direct access restriction")}</legend>
          <SelectControl
            label={t("Restriction type")}
            value={read.restriction_type || "default"}
            options={[
              { value: "default", label: t("Access denied") },
              { value: "teaser_message", label: t("Teaser message") },
              { value: "redirect", label: t("Redirect") },
              { value: "password_protected", label: t("Password protected") },
              { value: "expire", label: t("Expire access") },
              ...(premium
                ? [
                    {
                      value: "conditional",
                      label: t("Conditional restriction"),
                    },
                  ]
                : []),
            ]}
            onChange={(restriction_type) =>
              set("read", {
                effect: "deny",
                restriction_type,
                exclude_authors: !!read.exclude_authors,
              })
            }
          />
          {read.restriction_type === "teaser_message" && (
            <TextareaControl
              label={t("Teaser message")}
              value={read.message || ""}
              onChange={(message) => set("read", { ...read, message })}
            />
          )}
          {read.restriction_type === "password_protected" && (
            <TextControl
              type="password"
              autoComplete="new-password"
              label={t("Content password")}
              value={read.password || ""}
              onChange={(password) => set("read", { ...read, password })}
            />
          )}
          {read.restriction_type === "expire" && (
            <TextControl
              type="datetime-local"
              step={1}
              required={requireInputs}
              label={t("Expiration date and time (your local time)")}
              value={unixToLocalDateTime(read.expires_after)}
              onChange={(v) =>
                set("read", {
                  ...read,
                  expires_after: localDateTimeToUnix(v),
                })
              }
            />
          )}
          {read.restriction_type === "redirect" && (
            <RedirectFields
              value={read.redirect || { type: "default" }}
              onChange={(redirect) => set("read", { ...read, redirect })}
              required={requireInputs}
            />
          )}
          {read.restriction_type === "conditional" && premium && (
            <ConditionalFields
              value={read.condition}
              options={premium.conditional}
              required={requireInputs}
              onChange={(condition) => set("read", { ...read, condition })}
            />
          )}
          {premium && authorExceptions && (
            <ToggleControl
              className="ar-toggle"
              label={t("Allow the author to read")}
              checked={!!read.exclude_authors}
              onChange={(exclude_authors) =>
                set("read", { ...read, exclude_authors })
              }
            />
          )}
        </fieldset>
      )}
      {premium &&
        authorExceptions &&
        ["comment", "edit", "publish", "delete"]
          .filter(
            (key) =>
              authorPermissionKeys.includes(key) &&
              permissions[key]?.effect === "deny",
          )
          .map((key) => (
            <fieldset className="ar-fieldset" key={key}>
              <legend>{authorRestrictionLabels[key]}</legend>
              <ToggleControl
                className="ar-toggle"
                label={t("Allow the author")}
                checked={!!permissions[key]?.exclude_authors}
                onChange={(exclude_authors) =>
                  set(key, { ...permissions[key], exclude_authors })
                }
              />
            </fieldset>
          ))}
    </>
  );
}
function ContentEditor({ initial, onClose }: any) {
  const [permissions, setPermissions] = useState(initial.permissions || {});
  const [dirty, setDirty] = useState({});
  const write = useWrite();
  const { boot, busy, setMessage } = useWorkspace();
  const controls = visibleContentControls([
    {
      key: "list",
      title: t("Hidden"),
      description: t(
        "Hide from lists and menus. Direct links remain available.",
      ),
    },
    {
      key: "read",
      title: t("Restricted"),
      description: t("Limit direct access to read or download this content."),
    },
    {
      key: "comment",
      title: t("Leave comments"),
      description: t("Prevent comments on this content."),
    },
    {
      key: "edit",
      title: t("Edit"),
      description: t("Prevent changes through the admin area and REST API."),
    },
    {
      key: "publish",
      title: t("Publish"),
      description: t("Prevent publishing new versions of this content."),
    },
    {
      key: "delete",
      title: t("Delete"),
      description: t("Prevent trashing or deleting this content."),
    },
  ], boot.subject.type, (control: any) => control.key);
  const set = (key: string, value: any) => {
    setPermissions({ ...permissions, [key]: value });
    setDirty({ ...dirty, [key]: true });
  };
  const list = permissions.list || {};
  return (
    <Modal
      title={plain(initial.title) || t("Content access")}
      onRequestClose={() => !busy && onClose()}
      className="ar-modal ar-modal-wide"
    >
      <form
        onSubmit={async (e) => {
          e.preventDefault();
          if (
            permissions.read?.effect === "deny" &&
            permissions.read?.restriction_type === "redirect" &&
            needsPageSelection(permissions.read?.redirect)
          ) {
            setMessage({
              status: "error",
              text: t("Select a page before saving."),
            });
            return;
          }
          if (
            permissions.read?.effect === "deny" &&
            permissions.read?.restriction_type === "conditional" &&
            !conditionComplete(permissions.read.condition)
          ) {
            setMessage({
              status: "error",
              text: t("Complete the condition before saving."),
            });
            return;
          }
          const changes = changedPermissions(permissions, dirty);
          if (
            changes.length &&
            (await write("/post/" + initial.id, "POST", {
              permissions: changes,
            }))
          )
            onClose();
        }}
      >
        <ModalIntro icon="lock" title={t("Set access for this content")}>
          {t(
            "Choose which actions to deny for this access level. AAM saves only the permissions you change here.",
          )}
        </ModalIntro>
        <div className="ar-content-controls">
          {controls.map((control) => (
            <div className="ar-content-control" key={control.key}>
              <div>
                <h3>{control.title}</h3>
                <p>{control.description}</p>
                <AccessOutcome
                  effect={permissions[control.key]?.effect}
                  customized={!!initial.explicit_permissions?.[control.key]}
                  pending={!!dirty[control.key]}
                />
              </div>
              <ToggleControl
                className="ar-toggle"
                label={t("Deny")}
                checked={permissions[control.key]?.effect === "deny"}
                onChange={(checked) =>
                  set(control.key, {
                    ...(permissions[control.key] || {}),
                    effect: checked ? "deny" : "allow",
                    ...(control.key === "list" && checked && !list.on?.length
                      ? { on: ["frontend", "backend", "api"] }
                      : {}),
                  })
                }
              />
            </div>
          ))}
        </div>
        <ContentCustomizations
          permissions={permissions}
          set={set}
          premium={boot.premiumUi?.content ? boot.premiumUi : null}
          authorExceptions={["default", "role"].includes(boot.subject.type)}
          authorPermissionKeys={controls.map((control) => control.key)}
        />
        <div className="ar-actions">
          <Button
            type="submit"
            variant="primary"
            disabled={busy || !Object.keys(dirty).length}
          >
            {t("Save changes")}
          </Button>
          {initial.is_customized && (
            <Button
              variant="secondary"
              type="button"
              disabled={busy}
              onClick={async () => {
                if (await write("/post/" + initial.id, "DELETE")) onClose();
              }}
            >
              {t("Reset to inherited")}
            </Button>
          )}
        </div>
      </form>
    </Modal>
  );
}
