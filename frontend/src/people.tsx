import {
  useState,
  useRef,
  useWorkspace,
  useResource,
  useWrite,
  Resource,
  ModalIntro,
  Title,
  ActionTooltip,
  Search,
  Pager,
  Badge,
  EffectToggle,
  TableEmptyRow,
  Button,
  Modal,
  SelectControl,
  TextControl,
  ToggleControl,
  Confirm,
  t,
  plain,
  arr,
  mutate,
} from "./core";
import { AccessOutcome, ResourceOutcome } from "./resource-visuals";
import { localDateTime } from "./date-time.mjs";
import { passwordlessPath, userExpirationPayload } from "./user-access.mjs";
import { ExpirationChoices, expirationChoices } from "./expiration-choices";
import { expirationPayload } from "./expiration.mjs";
import { copyText } from "./clipboard.mjs";
import { eligibleParentRoles, parentRoleCounts } from "./role-parent.mjs";
import { PremiumModeHint } from "./premium-mode-hint";
import { userListPath, nextSelectableRole } from "./people-query.mjs";
function UserExpirationBadge({ expiresAt }: any) {
  const date = new Date(expiresAt);
  const ended = date.getTime() <= Date.now();
  const message = `${t(ended ? "Access ended" : "Access ends")} ${date.toLocaleString()}`;
  return (
    <ActionTooltip text={message}>
      <span
        className={`ar-user-expiration-bubble${ended ? " is-expired" : ""}`}
        role="note"
        tabIndex={0}
        aria-label={message}
      >
        <span className="dashicons dashicons-clock" aria-hidden="true" />
      </span>
    </ActionTooltip>
  );
}
export function People({ type = "role", onSelect, onRoleCreated }: any) {
  const { boot, busy, navigate } = useWorkspace();
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(0);
  const [roleFilter, setRoleFilter] = useState("");
  const [statusFilter, setStatusFilter] = useState("");
  const [edit, setEdit] = useState(null);
  const [remove, setRemove] = useState(null);
  const [expiry, setExpiry] = useState(null);
  const [loginUser, setLoginUser] = useState(null);
  const write = useWrite();
  const r = useResource(
    type === "role"
      ? "/roles?fields=permissions,user_count" +
          (boot.roleParentSupported ? ",parent" : "")
      : userListPath({ page, search, role: roleFilter, status: statusFilter }),
    false,
  );
  const select = (row: any) => {
    const subject = {
      type,
      id: type === "role" ? row.slug : row.id,
      name: type === "role" ? row.name : row.display_name,
    };
    if (onSelect) onSelect(subject);
    else navigate("admin_menu", subject);
  };
  return (
    <>
      {onSelect && (
        <ModalIntro
          compact
          icon={type === "role" ? "groups" : "admin-users"}
          title={type === "role" ? t("Choose a role") : t("Choose a user")}
        >
          {type === "role"
            ? t("Select a role to view and change its access rules.")
            : t("Select a user to manage their individual access rules.")}
        </ModalIntro>
      )}
      {onSelect ? (
        <div className="ar-picker-toolbar">
          <div className="ar-picker-filters">
            <Search
              label={type === "role" ? t("Search roles") : t("Search users")}
              value={search}
              onChange={(v) => {
                setSearch(v);
                setPage(0);
              }}
            />
            {type === "user" && (
              <>
                <div className="ar-picker-filter ar-picker-role-filter">
                  <SelectControl
                    label={t("Role")}
                    value={roleFilter}
                    options={[
                      { label: t("All roles"), value: "" },
                      ...(boot.roleOptions || []),
                    ]}
                    onChange={(value) => {
                      setRoleFilter(value);
                      setPage(0);
                    }}
                  />
                </div>
                <div className="ar-picker-filter ar-picker-status-filter">
                  <SelectControl
                    label={t("Status")}
                    value={statusFilter}
                    options={[
                      { label: t("All statuses"), value: "" },
                      { label: t("Active"), value: "active" },
                      { label: t("Inactive"), value: "inactive" },
                    ]}
                    onChange={(value) => {
                      setStatusFilter(value);
                      setPage(0);
                    }}
                  />
                </div>
              </>
            )}
          </div>
          {type === "role" && boot.caps.create_roles && (
            <Button
              variant="primary"
              onClick={() => setEdit({ name: "", slug: "" })}
            >
              {t("Create role")}
            </Button>
          )}
          {type === "user" && boot.caps.create_users && (
            <Button variant="secondary" href={boot.adminUrl + "user-new.php"}>
              {t("Add user")}
            </Button>
          )}
        </div>
      ) : (
        <>
          <Title
            title={type === "role" ? t("Roles") : t("Users")}
            icon={type === "role" ? "groups" : "admin-users"}
            description={
              type === "user"
                ? t(
                    "Manage user access, temporary accounts, and passwordless sign-in links.",
                  )
                : t("Choose whose access you want to manage.")
            }
            controls={
              <Search
                compact
                label={type === "role" ? t("Search roles") : t("Search users")}
                value={search}
                onChange={(value) => {
                  setSearch(value);
                  setPage(0);
                }}
              />
            }
          >
            {type === "role" && boot.caps.create_roles && (
              <Button
                variant="primary"
                onClick={() => setEdit({ name: "", slug: "" })}
              >
                {t("Create role")}
              </Button>
            )}
            {type === "user" && boot.caps.create_users && (
              <Button variant="primary" href={boot.adminUrl + "user-new.php"}>
                {t("Add user")}
              </Button>
            )}
          </Title>
        </>
      )}
      <Resource resource={r}>
        {(data) => {
          let rows = arr(data);
          const parentCounts =
            type === "role" && boot.roleParentSupported
              ? parentRoleCounts(rows)
              : new Map();
          if (type === "role")
            rows = rows.filter((row) =>
              (row.name + " " + row.slug + " " + (row.parent?.name || ""))
                .toLowerCase()
                .includes(search.toLowerCase()),
            );
          return (
            <>
              <table
                className={onSelect ? "ar-table ar-picker-table" : "ar-table"}
              >
                <thead>
                  <tr>
                    <th>{t("Name")}</th>
                    {(!onSelect || type === "user") && (
                      <th>{type === "role" ? t("Users") : t("Status")}</th>
                    )}
                    <th>{t("Actions")}</th>
                  </tr>
                </thead>
                <tbody>
                  {rows.map((row) => (
                    <tr key={row.slug || row.id}>
                      <td>
                        <div className="ar-resource-name">
                          <span className="ar-user-avatar-wrap">
                            <span className="ar-avatar">
                              {plain(row.name || row.display_name)
                                .slice(0, 2)
                                .toUpperCase()}
                            </span>
                            {type === "user" && row.expiration?.expires_at && (
                              <UserExpirationBadge
                                expiresAt={row.expiration.expires_at}
                              />
                            )}
                          </span>
                          <div>
                            <strong>
                              {plain(row.name || row.display_name)}
                            </strong>
                            <small>{row.slug || row.user_login}</small>
                            {type === "role" && boot.roleParentSupported && (
                              <>
                                {row.parent && (
                                  <small className="ar-role-parent-line">
                                    <span
                                      className="dashicons dashicons-arrow-right-alt"
                                      aria-hidden="true"
                                    />
                                    {t("Inherits from")}{" "}
                                    {plain(row.parent.name)}
                                  </small>
                                )}
                                {parentCounts.get(row.slug) > 0 && (
                                  <small className="ar-role-parent-badge">
                                    <span
                                      className="dashicons dashicons-networking"
                                      aria-hidden="true"
                                    />
                                    {t("Parent of")}{" "}
                                    {parentCounts.get(row.slug)}{" "}
                                    {parentCounts.get(row.slug) === 1
                                      ? t("role")
                                      : t("roles")}
                                  </small>
                                )}
                              </>
                            )}
                          </div>
                        </div>
                      </td>
                      {(!onSelect || type === "user") && (
                        <td>
                          {type === "role" ? (
                            row.user_count
                          ) : (
                            <Badge
                              tone={
                                row.status === "inactive" ? "danger" : "success"
                              }
                            >
                              {onSelect
                                ? row.status === "inactive"
                                  ? t("Inactive")
                                  : t("Active")
                                : row.status}
                            </Badge>
                          )}
                        </td>
                      )}
                      <td>
                        <div className="ar-actions ar-user-actions">
                          <Button
                            variant="secondary"
                            aria-label={
                              onSelect
                                ? type === "role"
                                  ? t("Select role")
                                  : t("Select user")
                                : undefined
                            }
                            disabled={
                              busy || !row.permissions?.includes("allow_manage")
                            }
                            onClick={() => select(row)}
                          >
                            {onSelect ? t("Select") : t("Manage access")}
                          </Button>
                          {type === "role" &&
                            (onSelect ||
                              row.permissions?.includes("allow_edit")) && (
                              <ActionTooltip
                                text={
                                  onSelect &&
                                  !row.permissions?.includes("allow_edit")
                                    ? t(
                                        "You do not have permission to edit this role.",
                                      )
                                    : undefined
                                }
                                disabled={
                                  onSelect &&
                                  !row.permissions?.includes("allow_edit")
                                }
                              >
                                <Button
                                  variant="tertiary"
                                  aria-label={
                                    onSelect ? t("Edit role") : undefined
                                  }
                                  title={onSelect ? t("Edit role") : undefined}
                                  disabled={
                                    busy ||
                                    !row.permissions?.includes("allow_edit")
                                  }
                                  onClick={() =>
                                    setEdit({ ...row, original: row.slug })
                                  }
                                >
                                  {t("Edit")}
                                </Button>
                              </ActionTooltip>
                            )}
                          {type === "role" &&
                            !onSelect &&
                            boot.caps.create_roles && (
                              <Button
                                variant="tertiary"
                                onClick={() =>
                                  setEdit({
                                    name: row.name + " " + t("copy"),
                                    slug: "",
                                    clone_role: row.slug,
                                  })
                                }
                              >
                                {t("Clone")}
                              </Button>
                            )}
                          {type === "role" &&
                            (onSelect ||
                              row.permissions?.includes("allow_delete")) && (
                              <ActionTooltip
                                text={
                                  onSelect &&
                                  !row.permissions?.includes("allow_delete")
                                    ? !boot.caps.delete_roles
                                      ? t(
                                          "You do not have permission to delete roles.",
                                        )
                                      : Number(row.user_count) > 0
                                        ? t(
                                            "This role has users assigned to it.",
                                          )
                                        : t("This role cannot be deleted.")
                                    : undefined
                                }
                                disabled={
                                  onSelect &&
                                  !row.permissions?.includes("allow_delete")
                                }
                              >
                                <Button
                                  variant="tertiary"
                                  isDestructive
                                  aria-label={
                                    onSelect ? t("Delete role") : undefined
                                  }
                                  title={
                                    onSelect ? t("Delete role") : undefined
                                  }
                                  disabled={
                                    busy ||
                                    !row.permissions?.includes("allow_delete")
                                  }
                                  onClick={() => setRemove(row)}
                                >
                                  {t("Delete")}
                                </Button>
                              </ActionTooltip>
                            )}
                          {type === "user" && !onSelect && (
                            <>
                              <Button
                                variant="secondary"
                                disabled={
                                  busy ||
                                  !row.permissions?.includes("allow_edit")
                                }
                                onClick={() => setExpiry(row)}
                              >
                                {t("Temporary account")}
                              </Button>
                              <Button
                                variant="secondary"
                                disabled={
                                  busy ||
                                  !row.permissions?.includes("allow_manage") ||
                                  !boot.caps.manage_jwt
                                }
                                onClick={() => setLoginUser(row)}
                              >
                                {t("Passwordless link")}
                              </Button>
                            </>
                          )}
                          {type === "user" &&
                            !onSelect &&
                            row.permissions?.includes("allow_edit") && (
                              <Button
                                variant="tertiary"
                                href={
                                  boot.adminUrl +
                                  "user-edit.php?user_id=" +
                                  row.id
                                }
                              >
                                {t("Profile")}
                              </Button>
                            )}
                          {type === "user" &&
                            !onSelect &&
                            (row.permissions?.includes("allow_lock") ||
                              row.permissions?.includes("allow_unlock")) && (
                              <Button
                                variant="tertiary"
                                disabled={busy}
                                onClick={() =>
                                  write(
                                    "/user/" + row.id,
                                    "PATCH",
                                    {
                                      status:
                                        row.status === "inactive"
                                          ? "active"
                                          : "inactive",
                                    },
                                    false,
                                  )
                                }
                              >
                                {row.status === "inactive"
                                  ? t("Unlock")
                                  : t("Lock")}
                              </Button>
                            )}
                        </div>
                      </td>
                    </tr>
                  ))}
                  {!rows.length && (
                    <TableEmptyRow
                      colSpan={onSelect && type === "role" ? 2 : 3}
                      title={
                        type === "role"
                          ? t("No roles found")
                          : t("No users found")
                      }
                    >
                      {search || roleFilter || statusFilter
                        ? t("Try another search or filter.")
                        : t("There are no records to display.")}
                    </TableEmptyRow>
                  )}
                </tbody>
              </table>
              {type === "user" && (
                <Pager
                  page={page}
                  setPage={setPage}
                  total={data.summary?.filtered_count || 0}
                />
              )}
            </>
          );
        }}
      </Resource>
      {edit && (
        <RoleEditor
          initial={edit}
          roles={arr(r.data)}
          onClose={() => setEdit(null)}
          onSaved={
            onRoleCreated && !edit.original
              ? async (role) => {
                  setEdit(null);
                  await onRoleCreated(role);
                }
              : undefined
          }
        />
      )}
      {expiry && (
        <ExpirationEditor
          user={expiry}
          onClose={() => setExpiry(null)}
          onCreateLink={() => {
            setExpiry(null);
            setLoginUser(expiry);
          }}
        />
      )}
      {loginUser && (
        <PasswordlessEditor
          user={loginUser}
          onClose={() => setLoginUser(null)}
        />
      )}
      {remove && (
        <Confirm
          title={t("Delete role")}
          confirmLabel={t("Delete role")}
          cancelLabel={t("Keep role")}
          impact={t(
            "This role will no longer be available. This change cannot be undone.",
          )}
          onClose={() => setRemove(null)}
          onConfirm={async () => {
            const deleted = await write(
              "/role/" + encodeURIComponent(remove.slug),
              "DELETE",
              undefined,
              false,
            );
            if (!deleted) return false;
            if (
              boot.subject.type === "role" &&
              boot.subject.id === remove.slug
            ) {
              const nextRole = nextSelectableRole(arr(r.data), remove.slug);
              if (nextRole) {
                await navigate(boot.screen, nextRole);
              } else if (
                boot.caps.manage_default ||
                boot.caps.manage_visitors
              ) {
                const type = boot.caps.manage_default ? "default" : "visitor";
                await navigate(boot.screen, {
                  type,
                  id: null,
                  name: t(type === "default" ? "Default" : "Visitors"),
                });
              }
            }
            return true;
          }}
        >
          {t("Delete the role")} <strong>{plain(remove.name)}</strong>?
        </Confirm>
      )}
    </>
  );
}
export function SelectedUserTools({ userId }: any) {
  const user = useResource(
    "/user/" +
      encodeURIComponent(userId) +
      "?fields=display_name,user_login,status,expiration,permissions",
    false,
  );
  const { boot, busy } = useWorkspace();
  const [expiry, setExpiry] = useState(null);
  const [loginUser, setLoginUser] = useState(null);
  const details = user.data;
  const canEdit = details?.permissions?.includes("allow_edit");
  const editUnavailable = !canEdit
    ? t("You cannot edit this user.")
    : undefined;
  const loginUnavailable =
    editUnavailable ||
    (!boot.caps.manage_jwt
      ? t("Requires permission to manage JWT Tokens.")
      : undefined);
  return (
    <>
      <div
        className="ar-selected-user-tools"
        aria-label={t("User access tools")}
      >
        <div className="ar-user-tools-heading">
          <span
            className="dashicons dashicons-admin-users"
            aria-hidden="true"
          />
          <span className="ar-eyebrow">{t("USER TOOLS")}</span>
        </div>
        {user.loading ? (
          <span className="ar-user-tools-note">{t("Loading user tools…")}</span>
        ) : user.error ? (
          <>
            <span className="ar-user-tools-note">{user.error.message}</span>
            <Button variant="link" onClick={user.refresh}>
              {t("Retry loading user tools")}
            </Button>
          </>
        ) : (
          <>
            <div className="ar-user-tool-actions">
              <ActionTooltip text={editUnavailable} disabled={busy || !canEdit}>
                <Button
                  variant="secondary"
                  className="ar-user-tool"
                  disabled={busy || !canEdit}
                  onClick={() => setExpiry(details)}
                >
                  <span
                    className="ar-user-tool-icon dashicons dashicons-clock"
                    aria-hidden="true"
                  />
                  <span className="ar-user-tool-copy">
                    <strong>{t("Temporary account")}</strong>
                    <small>
                      {!canEdit
                        ? t("You cannot edit this user.")
                        : details.expiration?.expires_at
                          ? `${t("Access ends")} ${new Date(details.expiration.expires_at).toLocaleString()}`
                          : t("Set an account expiration date")}
                    </small>
                  </span>
                  <span className="ar-user-tool-arrow" aria-hidden="true">
                    →
                  </span>
                </Button>
              </ActionTooltip>
              <ActionTooltip
                text={loginUnavailable}
                disabled={busy || !canEdit || !boot.caps.manage_jwt}
              >
                <Button
                  variant="secondary"
                  className="ar-user-tool"
                  disabled={busy || !canEdit || !boot.caps.manage_jwt}
                  onClick={() => setLoginUser(details)}
                >
                  <span
                    className="ar-user-tool-icon dashicons dashicons-admin-links"
                    aria-hidden="true"
                  />
                  <span className="ar-user-tool-copy">
                    <strong>{t("Passwordless link")}</strong>
                    <small>
                      {!canEdit
                        ? t("You cannot edit this user.")
                        : !boot.caps.manage_jwt
                          ? t("Requires JWT Tokens access")
                          : t("Generate a sign-in URL")}
                    </small>
                  </span>
                  <span className="ar-user-tool-arrow" aria-hidden="true">
                    →
                  </span>
                </Button>
              </ActionTooltip>
            </div>
          </>
        )}
      </div>
      {expiry && (
        <ExpirationEditor
          user={expiry}
          onClose={() => setExpiry(null)}
          onCreateLink={() => {
            setExpiry(null);
            setLoginUser(expiry);
          }}
        />
      )}
      {loginUser && (
        <PasswordlessEditor
          user={loginUser}
          onClose={() => setLoginUser(null)}
        />
      )}
    </>
  );
}
export function RoleEditor({
  initial,
  roles,
  onClose,
  onSaved,
  parentAvailable = true,
}: any) {
  const [value, setValue] = useState(() => ({
    ...initial,
    parent_role: initial.parent?.slug || "",
  }));
  const write = useWrite();
  const { boot, busy } = useWorkspace();
  const roleOptions = roles.map((role: any) => ({
    value: role.slug,
    label: plain(role.name),
  }));
  const parentOptions = eligibleParentRoles(roles, initial.original).map(
    (role: any) => ({ value: role.slug, label: plain(role.name) }),
  );
  if (
    initial.parent &&
    !parentOptions.some((role: any) => role.value === initial.parent.slug)
  ) {
    parentOptions.unshift({
      value: initial.parent.slug,
      label: plain(initial.parent.name),
    });
  }
  const selectedParent = parentOptions.find(
    (role: any) => role.value === value.parent_role,
  );
  return (
    <Modal
      title={
        initial.original
          ? t("Edit role")
          : initial.clone_role
            ? t("Clone role")
            : t("Create role")
      }
      onRequestClose={() => !busy && onClose()}
      className="ar-modal"
    >
      <form
        onSubmit={async (e) => {
          e.preventDefault();
          const payload = initial.original
            ? {
                name: value.name,
                ...(value.slug !== initial.original
                  ? { new_slug: value.slug }
                  : {}),
                ...(boot.roleParentSupported &&
                (value.parent_role || "") !== (initial.parent?.slug || "")
                  ? { parent_role: value.parent_role || "" }
                  : {}),
              }
            : {
                name: value.name,
                ...(value.slug?.trim() ? { slug: value.slug.trim() } : {}),
                ...(value.clone_role
                  ? {
                      clone_role: value.clone_role,
                      clone_role_settings: !!value.clone_role_settings,
                    }
                  : {}),
                ...(boot.roleParentSupported && value.parent_role
                  ? { parent_role: value.parent_role }
                  : {}),
              };
          const result = await write(
            initial.original
              ? "/role/" + encodeURIComponent(initial.original)
              : "/roles",
            initial.original ? "PATCH" : "POST",
            payload,
            false,
            !!onSaved,
          );
          if (result) {
            if (onSaved)
              await onSaved({
                slug: result.slug || value.slug,
                name: result.name || value.name,
              });
            else onClose();
          }
        }}
      >
        <ModalIntro
          icon={initial.original ? "edit" : "groups"}
          title={
            initial.original
              ? t("Update this role")
              : initial.clone_role
                ? t("Start from an existing role")
                : t("Create a new access group")
          }
        >
          {initial.original
            ? t(
                "Update this role’s details. Its access rules remain available to its members.",
              )
            : t(
                "Roles group people under shared access rules. You can adjust those rules after creating the role.",
              )}
        </ModalIntro>
        <TextControl
          label={t("Role name")}
          value={value.name}
          required
          onChange={(v) => setValue({ ...value, name: v })}
        />
        <TextControl
          label={t("Role slug")}
          value={value.slug}
          required={!!initial.original}
          help={
            !initial.original
              ? t(
                  "Optional. Leave blank to generate a slug from the role name.",
                )
              : undefined
          }
          disabled={
            !!initial.original &&
            !initial.permissions?.includes("allow_slug_update")
          }
          onChange={(v) => setValue({ ...value, slug: v })}
        />
        {!initial.original && (
          <SelectControl
            label={t("Copy capabilities from")}
            value={value.clone_role || ""}
            options={[
              { value: "", label: t("Start with no capabilities") },
              ...roleOptions,
            ]}
            onChange={(clone_role) => setValue({ ...value, clone_role })}
          />
        )}
        {!initial.original && value.clone_role && (
          <ToggleControl
            className="ar-toggle"
            label={t("Also copy AAM access settings once")}
            checked={!!value.clone_role_settings}
            onChange={(v) => setValue({ ...value, clone_role_settings: v })}
          />
        )}
        {boot.roleParentSupported && parentAvailable && (
          <div className="ar-role-parent-editor">
            <div className="ar-role-parent-heading">
              <span
                className="dashicons dashicons-networking"
                aria-hidden="true"
              />
              <div>
                <strong>{t("Role inheritance")}</strong>
                <p>
                  {t(
                    "A child role inherits its parent's access settings, then applies its own rules.",
                  )}
                </p>
              </div>
            </div>
            <SelectControl
              label={t("Parent role")}
              value={value.parent_role || ""}
              options={[
                { value: "", label: t("No parent role") },
                ...parentOptions,
              ]}
              onChange={(parent_role) => setValue({ ...value, parent_role })}
            />
            <div className="ar-role-parent-preview" aria-live="polite">
              <span
                className="dashicons dashicons-arrow-right-alt"
                aria-hidden="true"
              />
              <span>
                {selectedParent
                  ? `${t("Will inherit access from")} ${selectedParent.label}`
                  : t("Will inherit from default access")}
              </span>
            </div>
          </div>
        )}
        <div className="ar-actions ar-role-editor-actions">
          <Button type="submit" variant="primary" disabled={busy}>
            {initial.original ? t("Save role") : t("Create role")}
          </Button>
          <Button
            type="button"
            variant="secondary"
            disabled={busy}
            onClick={onClose}
          >
            {t("Cancel")}
          </Button>
        </div>
      </form>
    </Modal>
  );
}
function ExpirationEditor({ user, onClose, onCreateLink }: any) {
  const currentTrigger =
    typeof user.expiration?.trigger === "string"
      ? user.expiration.trigger
      : user.expiration?.trigger?.type || "logout";
  const [date, setDate] = useState(
    user.expiration?.expires_at
      ? localDateTime(user.expiration.expires_at)
      : localDateTime(new Date(Date.now() + 24 * 60 * 60 * 1000)),
  );
  const [trigger, setTrigger] = useState(
    currentTrigger === "change-role" ? "change_role" : currentTrigger,
  );
  const [role, setRole] = useState(user.expiration?.trigger?.to_role || "");
  const [error, setError] = useState("");
  const [saved, setSaved] = useState(false);
  const write = useWrite();
  const { boot, busy } = useWorkspace();
  const resetExpiration = async () => {
    if (
      await write(
        "/user/" + user.id + "?reset=expiration",
        "DELETE",
        undefined,
        false,
      )
    ) {
      onClose();
    }
  };
  return (
    <Modal
      title={t("Temporary account") + " · " + plain(user.display_name)}
      onRequestClose={() => !busy && onClose()}
      className="ar-modal"
    >
      {saved ? (
        <div className="ar-user-access-result">
          <span className="dashicons dashicons-yes-alt" aria-hidden="true" />
          <div>
            <strong>{t("Temporary access is set")}</strong>
            <p>
              {t("Access ends")}: {new Date(date).toLocaleString()}
            </p>
          </div>
          <div className="ar-actions">
            <Button
              variant="secondary"
              disabled={busy}
              onClick={resetExpiration}
            >
              {t("Remove time limit")}
            </Button>
            {boot.caps.manage_jwt && (
              <Button variant="primary" onClick={onCreateLink}>
                {t("Create passwordless link")}
              </Button>
            )}
            <Button variant="secondary" onClick={onClose}>
              {t("Done")}
            </Button>
          </div>
        </div>
      ) : (
        <form
          onSubmit={async (e) => {
            e.preventDefault();
            const expires = new Date(date);
            if (
              !Number.isFinite(expires.getTime()) ||
              expires.getTime() <= Date.now()
            ) {
              setError(t("Choose a future date and time."));
              return;
            }
            if (trigger === "change_role" && !role) {
              setError(t("Choose the role to assign after expiration."));
              return;
            }
            setError("");
            if (
              await write(
                "/user/" + user.id,
                "PATCH",
                userExpirationPayload(expires, trigger, role),
                false,
              )
            )
              setSaved(true);
          }}
        >
          <ModalIntro icon="clock" title={t("Give this account a time limit")}>
            {t(
              "Set when access ends, then choose whether AAM logs the user out, locks the account, or changes their role. You can update this later.",
            )}
          </ModalIntro>
          {user.expiration?.expires_at && (
            <div className="ar-user-current-expiration">
              <span className="dashicons dashicons-clock" aria-hidden="true" />
              <div>
                <strong>
                  {new Date(user.expiration.expires_at).getTime() > Date.now()
                    ? t("Time limit is active")
                    : t("Time limit has expired")}
                </strong>
                <small>
                  {new Date(user.expiration.expires_at).getTime() > Date.now()
                    ? t("Access ends")
                    : t("Access ended")}{" "}
                  {new Date(user.expiration.expires_at).toLocaleString()}
                </small>
              </div>
              <Button
                type="button"
                variant="secondary"
                disabled={busy}
                onClick={resetExpiration}
              >
                {t("Remove time limit")}
              </Button>
            </div>
          )}
          <TextControl
            type="datetime-local"
            step={1}
            label={t("Access ends (your local time)")}
            value={date}
            required
            onChange={setDate}
          />
          <SelectControl
            label={t("After access ends")}
            value={trigger}
            onChange={setTrigger}
            options={[
              { value: "logout", label: t("Log out") },
              { value: "lock", label: t("Lock account") },
              { value: "change_role", label: t("Change role") },
            ]}
          />
          {trigger === "change_role" && (
            <SelectControl
              label={t("Change to role")}
              value={role}
              options={[
                { value: "", label: t("Choose a role") },
                ...(boot.roleOptions || []),
              ]}
              onChange={setRole}
            />
          )}
          {error && (
            <p className="ar-form-error" role="alert">
              {error}
            </p>
          )}
          <div className="ar-actions ar-user-access-footer">
            <Button type="submit" variant="primary" disabled={busy}>
              {user.expiration
                ? t("Update temporary access")
                : t("Set temporary access")}
            </Button>
          </div>
        </form>
      )}
    </Modal>
  );
}
function PasswordlessEditor({ user, onClose }: any) {
  const [expiration, setExpiration] = useState("day");
  const [customDate, setCustomDate] = useState(
    localDateTime(new Date(Date.now() + 24 * 60 * 60 * 1000)),
  );
  const [issuedExpiration, setIssuedExpiration] = useState(null as any);
  const [url, setUrl] = useState("");
  const [copied, setCopied] = useState(false);
  const [pending, setPending] = useState(false);
  const input = useRef(null);
  const { busy, setBusy, navigate, setMessage } = useWorkspace();
  const create = async () => {
    if (busy || pending) return;
    setPending(true);
    setBusy(true);
    try {
      const expiry = expirationPayload(expiration, customDate);
      const result = await mutate(passwordlessPath(user.id), "POST", {
        ...expiry,
        is_revocable: true,
        is_refreshable: false,
        description: t("Passwordless sign-in link"),
      });
      if (result?.success === false || !result?.signed_url) {
        throw new Error(
          result?.message || t("The server did not return a sign-in link."),
        );
      }
      setUrl(result.signed_url);
      setIssuedExpiration({ ...expiry, choice: expiration });
    } catch (e: any) {
      setMessage({
        status: "error",
        text: e.message || t("Could not create the sign-in link."),
      });
    } finally {
      setBusy(false);
      setPending(false);
    }
  };
  const copy = async () => {
    try {
      await copyText(url);
      setCopied(true);
    } catch (_) {
      input.current?.select();
      setMessage({
        status: "error",
        text: t("Copy is unavailable. Select and copy the link above."),
      });
    }
  };
  return (
    <Modal
      title={t("Passwordless link") + " · " + plain(user.display_name)}
      onRequestClose={() => !pending && onClose()}
      className="ar-modal"
    >
      {url ? (
        <div className="ar-login-link-result">
          <ModalIntro icon="yes-alt" title={t("Your sign-in link is ready")}>
            {t(
              "Copy and share it privately. Anyone with this link can sign in as this user until it expires or is revoked.",
            )}
          </ModalIntro>
          <label htmlFor="ar-passwordless-url">{t("Sign-in link")}</label>
          <div className="ar-login-link-copy">
            <input
              id="ar-passwordless-url"
              ref={input}
              type="text"
              value={url}
              readOnly
              onFocus={(e) => e.target.select()}
            />
            <Button variant="primary" onClick={copy}>
              {copied ? t("Copied") : t("Copy link")}
            </Button>
          </div>
          <p className="ar-user-access-note">
            {issuedExpiration?.expires_at
              ? `${t("The link expires on")} ${new Date(issuedExpiration.expires_at).toLocaleString()}.`
              : `${t("The link expires after")} ${expirationChoices.find((option) => option.id === issuedExpiration?.choice)?.label || t("24 hours")}.`}{" "}
            {t("The account’s own expiration still applies.")}
          </p>
          <div className="ar-actions ar-user-access-footer">
            <Button variant="secondary" onClick={onClose}>
              {t("Done")}
            </Button>
            <Button
              variant="tertiary"
              onClick={() =>
                navigate("jwt", {
                  type: "user",
                  id: user.id,
                  name: plain(user.display_name),
                })
              }
            >
              {t("Manage issued tokens")}
            </Button>
          </div>
        </div>
      ) : (
        <div>
          <ModalIntro
            icon="admin-links"
            title={t("Let this user sign in without a password")}
          >
            {t(
              "Create a private link with an expiration date. Anyone holding the link can use it until it expires or you revoke it.",
            )}
          </ModalIntro>
          {user.status === "inactive" && (
            <p className="ar-form-error">
              {t(
                "This account is locked. Unlock it before sharing a sign-in link.",
              )}
            </p>
          )}
          <section
            className="ar-passwordless-expiration"
            aria-labelledby="ar-passwordless-expiration-heading"
          >
            <div className="ar-jwt-section-heading">
              <span className="dashicons dashicons-clock" aria-hidden="true" />
              <div>
                <h3 id="ar-passwordless-expiration-heading">
                  {t("Link expiration")}
                </h3>
                <p>{t("Choose when this sign-in link stops working.")}</p>
              </div>
            </div>
            <ExpirationChoices
              choice={expiration}
              onChoice={setExpiration}
              customDate={customDate}
              onCustomDate={setCustomDate}
              label={t("Link expiration")}
              help={t("Select a future date and time for this link to expire.")}
            />
          </section>
          <p className="ar-user-access-note">
            {t("The link can be revoked from this user’s JWT Tokens service.")}
          </p>
          <div className="ar-actions ar-user-access-footer">
            <Button
              variant="primary"
              disabled={busy || pending || user.status === "inactive"}
              onClick={create}
            >
              {pending ? t("Creating…") : t("Create sign-in link")}
            </Button>
            <Button variant="secondary" disabled={pending} onClick={onClose}>
              {t("Cancel")}
            </Button>
          </div>
        </div>
      )}
    </Modal>
  );
}
export function Identity() {
  const { boot } = useWorkspace();
  const [kind, setKind] = useState("role");
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(0);
  const [edit, setEdit] = useState(null);
  const [defaultOpen, setDefaultOpen] = useState(false);
  const r = useResource(
    kind === "role"
      ? "/identity/roles"
      : "/identity/users?per_page=20&offset=" +
          page * 20 +
          "&search=" +
          encodeURIComponent(search),
  );
  return (
    <div className="ar-identity-screen">
      <header className="ar-identity-header">
        <div className="ar-identity-heading">
          <span
            className="ar-identity-heading-icon dashicons dashicons-networking"
            aria-hidden="true"
          />
          <div>
            <h2 tabIndex={-1} id="ar-screen-title">
              {t("Identity Governance")}
            </h2>
            <p>{t("Control visibility and management of roles and users.")}</p>
          </div>
        </div>
        <div
          className="ar-identity-kind"
          role="group"
          aria-label={t("Resource type")}
        >
          {[
            { value: "role", label: t("Roles"), icon: "groups" },
            { value: "user", label: t("Users"), icon: "admin-users" },
          ].map((option) => (
            <button
              type="button"
              key={option.value}
              className={kind === option.value ? "is-active" : ""}
              aria-pressed={kind === option.value}
              onClick={() => {
                setKind(option.value);
                setPage(0);
                setSearch("");
              }}
            >
              <span
                className={`dashicons dashicons-${option.icon}`}
                aria-hidden="true"
              />
              {option.label}
            </button>
          ))}
        </div>
        <div className="ar-identity-search">
          <span className="dashicons dashicons-search" aria-hidden="true" />
          <input
            type="search"
            value={search}
            aria-label={kind === "role" ? t("Search roles") : t("Search users")}
            placeholder={
              kind === "role" ? t("Search roles…") : t("Search users…")
            }
            onChange={(event) => {
              setSearch(event.target.value);
              setPage(0);
            }}
            onKeyDown={(event) => {
              if (event.key === "Enter") event.preventDefault();
            }}
          />
        </div>
        {boot.premiumUi?.identity && (
          <Button variant="secondary" onClick={() => setDefaultOpen(true)}>
            {kind === "role"
              ? t("Defaults for all roles")
              : t("Defaults for all users")}
          </Button>
        )}
      </header>
      {!boot.premiumUi?.identity && (
        <PremiumModeHint
          label="Defaults for identities"
          description={
            kind === "role"
              ? "Premium can set rules for all roles, with exceptions for individual roles."
              : "Premium can set rules for all users, with exceptions for individual users."
          }
        />
      )}
      <Resource resource={r}>
        {(data) => {
          const rows = arr(data).filter(
            (row) =>
              kind === "user" ||
              (row.name || "").toLowerCase().includes(search.toLowerCase()),
          );
          return (
            <>
              <table className="ar-table">
                <thead>
                  <tr>
                    <th>{t("Identity")}</th>
                    <th>{t("Actions")}</th>
                  </tr>
                </thead>
                <tbody>
                  {rows.map((row) => (
                    <tr key={row.id}>
                      <td>
                        <span className="ar-name-line">
                          <strong>{plain(row.name || row.display_name)}</strong>
                          <ResourceOutcome
                            permissions={row.permissions}
                            customized={row.is_customized}
                          />
                        </span>
                        <small className="ar-identity-meta">
                          {kind === "user" ? t("User ID") : t("Role slug")}
                          <code>{kind === "user" ? `#${row.id}` : row.id}</code>
                        </small>
                      </td>
                      <td>
                        <Button
                          variant="secondary"
                          onClick={() => setEdit(row)}
                        >
                          {t("Manage permissions")}
                        </Button>
                      </td>
                    </tr>
                  ))}
                  {!rows.length && (
                    <TableEmptyRow colSpan={2} title={t("No identities found")}>
                      {t("Try another search or switch identity type.")}
                    </TableEmptyRow>
                  )}
                </tbody>
              </table>
              {kind === "user" && (
                <Pager
                  page={page}
                  setPage={setPage}
                  total={data.summary?.filtered_count || 0}
                />
              )}
            </>
          );
        }}
      </Resource>
      {edit && (
        <IdentityEditor item={edit} kind={kind} onClose={() => setEdit(null)} />
      )}
      {defaultOpen && (
        <IdentityDefaultEditor
          kind={kind}
          onClose={() => setDefaultOpen(false)}
        />
      )}
    </div>
  );
}
function IdentityDefaultEditor({ kind, onClose }: any) {
  const resource = useResource(`/identity/${kind}s/default`);
  return (
    <Resource resource={resource}>
      {(data) => (
        <IdentityEditor
          item={{
            ...data,
            id: "*",
            name: kind === "role" ? t("All roles") : t("All users"),
          }}
          kind={kind}
          defaultScope
          onClose={onClose}
        />
      )}
    </Resource>
  );
}
function IdentityEditor({ item, kind, defaultScope = false, onClose }: any) {
  const write = useWrite();
  const { busy } = useWorkspace();
  const [permissions, setPermissions] = useState(item.permissions || {});
  const [dirty, setDirty] = useState({});
  const keys = [
    ...(kind === "role" ? ["list_role"] : []),
    "list_user",
    "edit_user",
    "promote_user",
    "change_user_password",
    "delete_user",
  ];
  const labels: any = {
    list_role: t("List roles"),
    list_user: t("List users"),
    edit_user: t("Edit users"),
    promote_user: t("Promote users"),
    change_user_password: t("Change user passwords"),
    delete_user: t("Delete users"),
  };
  const endpoint = defaultScope
    ? `/identity/${kind}s/default`
    : "/identity/" + kind + "/" + encodeURIComponent(item.id);
  return (
    <Modal
      title={plain(item.name || item.display_name)}
      onRequestClose={() => !busy && onClose()}
      className="ar-modal"
    >
      <ModalIntro
        icon="admin-network"
        title={t("Choose who can manage this identity")}
      >
        {t(
          defaultScope
            ? "These defaults apply across every identity of this type unless a more specific rule overrides them."
            : "These rules govern the selected subject’s access to this identity.",
        )}
      </ModalIntro>
      <div className="ar-permission-list">
        {keys.map((key) => (
          <div className="ar-permission-row" key={key}>
            <span className="ar-permission-name">
              {labels[key]}{" "}
              <AccessOutcome
                effect={permissions[key]?.effect}
                customized={!!item.explicit_permissions?.[key]}
                pending={!!dirty[key]}
              />
            </span>
            <EffectToggle
              label={labels[key]}
              value={permissions[key]?.effect || "allow"}
              onChange={(effect) => {
                setPermissions({ ...permissions, [key]: { effect } });
                setDirty({ ...dirty, [key]: true });
              }}
            />
          </div>
        ))}
      </div>
      <div className="ar-actions">
        <Button
          variant="primary"
          disabled={busy || !Object.keys(dirty).length}
          onClick={async () => {
            if (
              await write(endpoint, "PATCH", {
                permissions: Object.keys(dirty).map((permission) => ({
                  permission,
                  effect: permissions[permission]?.effect || "allow",
                })),
              })
            )
              onClose();
          }}
        >
          {t("Save permissions")}
        </Button>
        <Button
          variant="secondary"
          disabled={busy}
          onClick={async () => {
            if (await write(endpoint, "DELETE")) onClose();
          }}
        >
          {t("Reset to inherited")}
        </Button>
      </div>
    </Modal>
  );
}
