import {
  useState,
  useWorkspace,
  useResource,
  useWrite,
  Resource,
  Title,
  ActionTooltip,
  Search,
  Pager,
  TableEmptyRow,
  Button,
  SelectControl,
  t,
  plain,
  arr,
} from "./core";
import { contentItemsPath } from "./content-query.mjs";
import { ResourceIcon, ResourceOutcome } from "./resource-visuals";

function ContentItems({ active, search, page, setPage, onEdit }: any) {
  const { boot } = useWorkspace();
  const write = useWrite();
  const isPosts = active.kind === "posts";
  const resource = useResource(contentItemsPath(active, page, search));
  return (
    <Resource resource={resource}>
      {(data) => (
        <>
          <table className="ar-table ar-content-items-table">
            <thead>
              <tr>
                <th>{isPosts ? t("Post") : t("Term")}</th>
                <th>{t("Actions")}</th>
              </tr>
            </thead>
            <tbody>
              {arr(data).map((row: any) => {
                const lockedDefault =
                  row.is_default &&
                  row.taxonomy === "category" &&
                  Number(row.id) === boot.premiumUi?.defaultCategory;
                const defaultActionLabel = lockedDefault
                  ? t("WordPress default category cannot be changed.")
                  : row.is_default
                    ? t("Remove default")
                    : t("Set as default");
                const editLabel = isPosts
                  ? t("Edit post in WordPress")
                  : t("Edit term in WordPress");
                return (
                  <tr key={row.id}>
                    <td>
                      <div className="ar-content-entry">
                        <ResourceIcon
                          icon={isPosts ? row.icon : null}
                          fallback={
                            isPosts
                              ? "admin-post"
                              : row.is_hierarchical
                                ? "category"
                                : "tag"
                          }
                        />
                        <div className="ar-content-entry-copy">
                          <span className="ar-name-line">
                            <strong>
                              {plain(row.title) || t("(Untitled)")}
                            </strong>
                            {!isPosts && row.is_default && (
                              <span
                                className="ar-default-term"
                                title={t("Default term for this post type")}
                              >
                                <span
                                  className="dashicons dashicons-star-filled"
                                  aria-hidden="true"
                                />
                                {t("Default")}
                              </span>
                            )}
                            <ResourceOutcome
                              permissions={row.permissions}
                              customized={row.is_customized}
                            />
                          </span>
                          <small>#{row.id}</small>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div className="ar-content-actions ar-content-item-actions">
                        {!isPosts &&
                          boot.premiumUi?.content &&
                          active.postType && (
                            <ActionTooltip
                              text={defaultActionLabel}
                              disabled={lockedDefault}
                            >
                              <Button
                                variant="tertiary"
                                className={`ar-content-action-icon ar-content-default-action${row.is_default ? " is-active" : ""}`}
                                aria-label={defaultActionLabel}
                                aria-pressed={!!row.is_default}
                                disabled={lockedDefault}
                                onClick={async () => {
                                  const saved = await write(
                                    "/content/default-term",
                                    "PATCH",
                                    {
                                      term_id: row.id,
                                      taxonomy: active.taxonomy,
                                      post_type: active.postType,
                                      is_default: !row.is_default,
                                    },
                                  );
                                  if (saved) resource.refresh();
                                }}
                              >
                                <span
                                  className={`dashicons dashicons-star-${row.is_default ? "filled" : "empty"}`}
                                  aria-hidden="true"
                                />
                              </Button>
                            </ActionTooltip>
                          )}
                        {isPosts ? (
                          <Button
                            variant="secondary"
                            onClick={() => onEdit({ kind: "post", row })}
                          >
                            {t("Manage access")}
                          </Button>
                        ) : (
                          <Button
                            variant="secondary"
                            onClick={() =>
                              onEdit({
                                kind: "term",
                                row,
                                postType: active.postType,
                              })
                            }
                          >
                            {t("Manage access")}
                          </Button>
                        )}
                        <ActionTooltip text={editLabel}>
                          <Button
                            variant="secondary"
                            className="ar-content-action-icon ar-content-edit-action"
                            aria-label={editLabel}
                            href={
                              isPosts
                                ? boot.adminUrl +
                                  "post.php?post=" +
                                  row.id +
                                  "&action=edit"
                                : boot.adminUrl +
                                  "term.php?taxonomy=" +
                                  encodeURIComponent(row.taxonomy) +
                                  "&tag_ID=" +
                                  row.id
                            }
                            target="_blank"
                            rel="noopener noreferrer"
                          >
                            <span
                              className="dashicons dashicons-edit"
                              aria-hidden="true"
                            />
                          </Button>
                        </ActionTooltip>
                      </div>
                    </td>
                  </tr>
                );
              })}
              {!arr(data).length && (
                <TableEmptyRow colSpan={2} title={t("No matching content")}>
                  {t("Try another search or browse a different section.")}
                </TableEmptyRow>
              )}
            </tbody>
          </table>
          <Pager
            page={page}
            setPage={setPage}
            total={data.summary?.filtered_count || 0}
          />
        </>
      )}
    </Resource>
  );
}

export function ContentBrowser({ types, taxonomies, onEdit }: any) {
  const { boot } = useWorkspace();
  const premium = boot.contentPremium;
  const nudgeUrl = premium?.active
    ? premium.settingsUrl
    : premium?.installed
      ? premium.pluginsUrl
      : premium?.purchaseUrl;
  const nudgeKey = `aam:content-premium-nudge:${boot.blogId}:${boot.viewerId}`;
  const [nudgeHidden, setNudgeHidden] = useState(() => {
    try {
      return window.sessionStorage.getItem(nudgeKey) === "hidden";
    } catch {
      return false;
    }
  });
  const [path, setPath] = useState([
    { kind: "post_types", label: t("Post Types") },
  ]);
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(0);
  const active: any = path[path.length - 1];
  const navigate = (next: any[]) => {
    setPath(next);
    setSearch("");
    setPage(0);
  };
  const open = (item: any) => navigate([...path, item]);
  const isRoot = active.kind === "post_types" || active.kind === "taxonomies";
  const rootItems = active.kind === "post_types" ? types : taxonomies;
  const visibleItems = isRoot
    ? rootItems.filter((item: any) =>
        (item.title || item.slug).toLowerCase().includes(search.toLowerCase()),
      )
    : [];
  const openGroup = (item: any) => {
    open(
      active.kind === "post_types"
        ? {
            kind: "posts",
            label: item.title,
            postType: item.slug,
            icon: item.icon,
          }
        : {
            kind: "terms",
            label: item.title,
            taxonomy: item.slug,
            isHierarchical: item.is_hierarchical,
          },
    );
  };

  return (
    <>
      <Title
        title={t("Posts & Terms")}
        description={t("Manage access to content and terms.")}
        icon="admin-post"
        className="ar-page-title-content"
        controls={
          <div className="ar-content-header-controls">
            <nav
              className="ar-content-breadcrumb"
              aria-label={t("Content location")}
            >
              <div
                className="ar-content-root-switch"
                role="group"
                aria-label={t("Browse content")}
              >
                <span className="ar-content-switch-label" aria-hidden="true">
                  {t("Browse")}
                </span>
                {[
                  {
                    kind: "post_types",
                    label: t("Post Types"),
                    browseLabel: t("Browse Post Types"),
                    backLabel: t("Back to Post Types"),
                    icon: "admin-post",
                  },
                  {
                    kind: "taxonomies",
                    label: t("Taxonomies"),
                    browseLabel: t("Browse Taxonomies"),
                    backLabel: t("Back to Taxonomies"),
                    icon: "category",
                  },
                ].map((option) => {
                  const selected = path[0].kind === option.kind;
                  const tooltip =
                    selected && path.length > 1
                      ? option.backLabel
                      : option.browseLabel;
                  return (
                    <ActionTooltip text={tooltip} key={option.kind}>
                      <button
                        type="button"
                        className={`ar-content-switch-button${selected ? " is-active" : ""}`}
                        aria-label={tooltip}
                        aria-pressed={selected}
                        onClick={() => {
                          if (!selected || path.length > 1) {
                            navigate([
                              { kind: option.kind, label: option.label },
                            ]);
                          }
                        }}
                      >
                        <span
                          className={`dashicons dashicons-${option.icon}`}
                          aria-hidden="true"
                        />
                      </button>
                    </ActionTooltip>
                  );
                })}
              </div>
              {path.slice(1).map((item: any, index: number) => {
                const pathIndex = index + 1;
                const icon =
                  item.kind === "post_types"
                    ? "dashicons-admin-post"
                    : item.kind === "taxonomies"
                      ? "dashicons-category"
                      : item.icon;
                const fallback =
                  item.kind === "terms"
                    ? item.isHierarchical
                      ? "category"
                      : "tag"
                    : "admin-post";
                const current = pathIndex === path.length - 1;
                return (
                  <span className="ar-breadcrumb-step" key={pathIndex}>
                    <span
                      className="dashicons dashicons-arrow-right-alt2 ar-breadcrumb-separator"
                      aria-hidden="true"
                    />
                    {current ? (
                      <span
                        className="ar-breadcrumb-current"
                        aria-current="page"
                      >
                        <ResourceIcon icon={icon} fallback={fallback} />
                        <strong>{plain(item.label)}</strong>
                      </span>
                    ) : (
                      <button
                        type="button"
                        className="ar-breadcrumb-link"
                        onClick={() => navigate(path.slice(0, pathIndex + 1))}
                      >
                        <ResourceIcon icon={icon} fallback={fallback} />
                        {plain(item.label)}
                      </button>
                    )}
                  </span>
                );
              })}
            </nav>
            <div className="ar-content-toolbar">
              {active.kind === "posts" &&
                taxonomies.some((item: any) =>
                  item.post_types?.includes(active.postType),
                ) && (
                  <div className="ar-inline-picker">
                    <span aria-hidden="true">{t("Related")}</span>
                    <SelectControl
                      label={t("Show related content")}
                      hideLabelFromVision
                      value="posts"
                      options={[
                        { value: "posts", label: t("Posts") },
                        ...taxonomies
                          .filter((item: any) =>
                            item.post_types?.includes(active.postType),
                          )
                          .map((item: any) => ({
                            value: item.slug,
                            label: plain(item.title),
                          })),
                      ]}
                      onChange={(slug) => {
                        const taxonomy = taxonomies.find(
                          (item: any) => item.slug === slug,
                        );
                        if (taxonomy) {
                          open({
                            kind: "terms",
                            label: taxonomy.title,
                            taxonomy: taxonomy.slug,
                            postType: active.postType,
                            isHierarchical: taxonomy.is_hierarchical,
                          });
                        }
                      }}
                    />
                  </div>
                )}
            </div>
            <Search
              compact
              value={search}
              onChange={(value) => {
                setSearch(value);
                setPage(0);
              }}
            />
          </div>
        }
      />
      {!premium?.enabled && !nudgeHidden && (
        <div className="ar-content-premium-nudge" role="note">
          <span className="dashicons dashicons-shield" aria-hidden="true" />
          <span>
            {premium?.active
              ? t(
                  "Premium Posts & Terms controls are paused. Enable the service to manage taxonomy and term rules.",
                )
              : premium?.installed
                ? t(
                    "Premium is installed. Activate it to unlock taxonomy and term access rules.",
                  )
                : t(
                    "Premium adds post type, taxonomy and term access rules when you need them.",
                  )}
          </span>
          {nudgeUrl && (
            <a
              href={nudgeUrl}
              target={
                !premium?.active && !premium?.installed ? "_blank" : undefined
              }
              rel={
                !premium?.active && !premium?.installed
                  ? "noopener noreferrer"
                  : undefined
              }
            >
              {premium?.active
                ? t("Open settings")
                : premium?.installed
                  ? t("Activate")
                  : t("Explore Premium")}
              <span
                className="dashicons dashicons-arrow-right-alt"
                aria-hidden="true"
              />
            </a>
          )}
          <button
            type="button"
            aria-label={t("Dismiss premium suggestion")}
            title={t("Dismiss premium suggestion")}
            onClick={() => {
              setNudgeHidden(true);
              try {
                window.sessionStorage.setItem(nudgeKey, "hidden");
              } catch {
                /* Optional. */
              }
            }}
          >
            <span className="dashicons dashicons-no-alt" aria-hidden="true" />
          </button>
        </div>
      )}
      {isRoot ? (
        <>
          <table className="ar-table">
            <thead>
              <tr>
                <th>
                  {active.kind === "post_types"
                    ? t("Post type")
                    : t("Taxonomy")}
                </th>
                <th>{t("Actions")}</th>
              </tr>
            </thead>
            <tbody>
              {visibleItems.map((item: any) => (
                <tr key={item.slug}>
                  <td>
                    <div className="ar-content-entry ar-content-entry-root">
                      <ResourceIcon
                        icon={active.kind === "post_types" ? item.icon : null}
                        fallback={
                          active.kind === "post_types"
                            ? "admin-post"
                            : item.is_hierarchical
                              ? "category"
                              : "tag"
                        }
                      />
                      <div className="ar-content-entry-copy">
                        <span className="ar-name-line">
                          <button
                            type="button"
                            className="ar-content-entry-link"
                            onClick={() => openGroup(item)}
                          >
                            {plain(item.title)}
                          </button>
                          <ResourceOutcome
                            permissions={item.permissions}
                            customized={item.is_customized}
                          />
                        </span>
                        <small className="ar-content-entry-slug">
                          {item.slug}
                        </small>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div className="ar-content-actions">
                      <Button
                        variant="secondary"
                        onClick={() => openGroup(item)}
                      >
                        {active.kind === "post_types"
                          ? t("Browse posts")
                          : t("Browse terms")}
                      </Button>
                      <Button
                        variant="secondary"
                        onClick={() =>
                          onEdit({
                            kind:
                              active.kind === "post_types"
                                ? "post_type"
                                : "taxonomy",
                            row: item,
                          })
                        }
                      >
                        {t("Manage access")}
                      </Button>
                    </div>
                  </td>
                </tr>
              ))}
              {!visibleItems.length && (
                <TableEmptyRow colSpan={2} title={t("No matching items")}>
                  {t("Try another search or browse a different section.")}
                </TableEmptyRow>
              )}
            </tbody>
          </table>
        </>
      ) : (
        <ContentItems
          active={active}
          search={search}
          page={page}
          setPage={setPage}
          onEdit={onEdit}
        />
      )}
    </>
  );
}
