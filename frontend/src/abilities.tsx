import {
  useState,
  useEffect,
  useWorkspace,
  useResource,
  useWrite,
  Resource,
  Title,
  Search,
  Pager,
  Reset,
  Button,
  Empty,
  TableEmptyRow,
  ToggleControl,
  t,
  plain,
  arr,
} from "./core";
import { PremiumModeHint } from "./premium-mode-hint";
import { AccessRuleControl } from "./access-rule-control";
import { mcpUnavailable as isMcpUnavailable } from "./mcp-availability.mjs";

function DefaultModes({ kind, restricted, busy, onChange }: any) {
  const abilities = kind === "ability";

  return (
    <section
      className="ar-premium-mode ar-ability-default-mode"
      aria-label={t("Default access mode")}
    >
      <span
        className="ar-premium-mode-icon dashicons dashicons-lock"
        aria-hidden="true"
      />
      <div className="ar-premium-mode-copy">
        <strong>{t("Default access mode")}</strong>
        <small>
          {abilities
            ? restricted
              ? t(
                  "Abilities need an explicit Allow rule; native checks still apply.",
                )
              : t(
                  "Abilities remain available unless an individual rule denies them.",
                )
            : restricted
              ? t(
                  "Servers and their primitives need an explicit server Allow rule.",
                )
              : t(
                  "Servers remain available unless an individual rule denies them.",
                )}
        </small>
      </div>
      <ToggleControl
        className="ar-toggle"
        label={
          abilities
            ? t("Restrict abilities by default")
            : t("Restrict MCP servers by default")
        }
        checked={restricted}
        disabled={busy}
        onChange={(checked: boolean) => onChange(kind, checked)}
      />
    </section>
  );
}

export function Abilities() {
  const { boot, busy } = useWorkspace();
  const [tab, setTab] = useState("abilities");
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(0);
  const [expanded, setExpanded] = useState({} as Record<string, boolean>);
  const [primitive, setPrimitive] = useState({} as Record<string, string>);
  const [modeState, setModeState] = useState(null as any);
  const abilitiesResource = useResource("/abilities");
  const serversResource = useResource("/mcp-servers");
  const toolsResource = useResource("/mcp-tools");
  const resourcesResource = useResource("/mcp-resources");
  const promptsResource = useResource("/mcp-prompts");
  const abilities = arr(abilitiesResource.data);
  const tools = arr(toolsResource.data);
  const mcpResources = arr(resourcesResource.data);
  const prompts = arr(promptsResource.data);
  const registeredServers = arr(serversResource.data);
  const mcpCollectionsLoaded =
    serversResource.loaded &&
    toolsResource.loaded &&
    resourcesResource.loaded &&
    promptsResource.loaded;
  const mcpUnavailable = isMcpUnavailable(
    boot.mcpAdapterAvailable,
    serversResource,
    toolsResource,
    resourcesResource,
    promptsResource,
  );
  const data = {
    abilities,
    servers: registeredServers.map((server: any) => ({
      ...server,
      tools: tools.filter((tool: any) => tool.server_id === server.id),
      resources: mcpResources.filter(
        (item: any) => item.server_id === server.id,
      ),
      prompts: prompts.filter((item: any) => item.server_id === server.id),
    })),
  };
  const resource = {
    data,
    loading:
      tab === "abilities"
        ? abilitiesResource.loading
        : serversResource.loading ||
          toolsResource.loading ||
          resourcesResource.loading ||
          promptsResource.loading,
    loaded:
      tab === "abilities" ? abilitiesResource.loaded : mcpCollectionsLoaded,
    error:
      tab === "abilities"
        ? abilitiesResource.error
        : serversResource.error ||
          toolsResource.error ||
          resourcesResource.error ||
          promptsResource.error,
    refresh: () => {
      abilitiesResource.refresh();
      serversResource.refresh();
      toolsResource.refresh();
      resourcesResource.refresh();
      promptsResource.refresh();
    },
  };
  const write = useWrite();
  useEffect(() => setModeState(null), [boot.subject?.type, boot.subject?.id]);
  const abilityCount = abilitiesResource.loaded ? abilities.length : null;
  const serverCount = serversResource.loaded ? registeredServers.length : null;

  const change = (
    kind: string,
    id: string,
    effect: string,
    serverId?: string,
  ) => {
    const endpoint =
      kind === "ability"
        ? "/ability"
        : kind === "server"
          ? "/mcp-server"
          : "/mcp-server/primitive/" + kind;
    const params = new URLSearchParams({ resource: id });
    if (serverId) params.set("server_id", serverId);
    return write(
      endpoint + "?" + params.toString(),
      effect === "inherit" ? "DELETE" : "PATCH",
      effect === "inherit" ? undefined : { effect },
    );
  };
  const changeMode = async (
    kind: string,
    restricted: boolean,
    serverId?: string,
  ) => {
    const endpoint =
      kind === "ability"
        ? "/ability-access/mode"
        : kind === "server"
          ? "/mcp-server/mode"
          : "/mcp-server/primitive/" +
            kind +
            "/mode?server_id=" +
            encodeURIComponent(serverId || "");
    const saved = await write(endpoint, "PATCH", {
      effect: restricted ? "deny" : "allow",
    });
    if (saved) {
      setModeState((current: any) => {
        const modes = current || boot.premiumUi?.modes?.ability || {};
        if (serverId) {
          return {
            ...modes,
            primitive: {
              ...modes.primitive,
              [serverId]: {
                ...modes.primitive?.[serverId],
                [kind]: restricted,
              },
            },
          };
        }
        return { ...modes, [kind]: restricted };
      });
      resource.refresh();
    }
  };

  return (
    <>
      <Title
        title={t("Abilities & MCP")}
        description={
          tab === "abilities"
            ? t("Manage WordPress ability access.")
            : mcpUnavailable
              ? undefined
              : t("Manage MCP servers, tools, resources, and prompts.")
        }
        icon="rest-api"
        className="ar-page-title-ability"
        controls={
          <>
            <div
              className="ar-ability-tabs"
              role="tablist"
              aria-label={t("Ability and MCP views")}
            >
              <button
                type="button"
                role="tab"
                aria-selected={tab === "abilities"}
                className={tab === "abilities" ? "is-active" : ""}
                onClick={() => {
                  setTab("abilities");
                  setPage(0);
                }}
              >
                <span
                  className="dashicons dashicons-admin-generic"
                  aria-hidden="true"
                />
                {t("Abilities")}
                {abilityCount !== null && (
                  <span className="ar-ability-tab-count">{abilityCount}</span>
                )}
              </button>
              <button
                type="button"
                role="tab"
                aria-selected={tab === "mcp"}
                className={tab === "mcp" ? "is-active" : ""}
                onClick={() => {
                  setTab("mcp");
                  setPage(0);
                }}
              >
                <span
                  className="dashicons dashicons-networking"
                  aria-hidden="true"
                />
                {t("MCP")}
                {serverCount !== null && (
                  <span className="ar-ability-tab-count">{serverCount}</span>
                )}
              </button>
            </div>
            {(tab === "abilities" || !mcpUnavailable) && (
              <Search
                compact
                value={search}
                onChange={(value) => {
                  setSearch(value);
                  setPage(0);
                }}
              />
            )}
            {tab === "abilities" ? (
              <Reset
                endpoint="/abilities"
                label={t("Reset abilities")}
                serviceName={t("Abilities")}
                iconOnly
              />
            ) : !mcpUnavailable ? (
              <Reset
                endpoint="/mcp-servers"
                additionalEndpoints={[
                  "/mcp-tools",
                  "/mcp-resources",
                  "/mcp-prompts",
                ]}
                label={t("Reset MCP settings")}
                serviceName={t("MCP")}
                iconOnly
              />
            ) : null}
          </>
        }
      />
      {tab === "mcp" && mcpUnavailable ? (
        <Empty title={t("MCP functionality is not available on this site.")}>
          {boot.mcpAdapterAvailable
            ? t("No MCP servers or primitives are registered on this site.")
            : t("Activate the WordPress MCP Adapter to manage MCP access.")}
        </Empty>
      ) : (
        <Resource resource={resource}>
          {(data: any) => {
            if (tab === "abilities" && !boot.abilityApiAvailable) {
              return (
                <Empty title={t("WordPress Abilities API is unavailable")}>
                  {t("This service requires WordPress 6.9 or newer.")}
                </Empty>
              );
            }

            const defaultModes = modeState || boot.premiumUi?.modes?.ability;
            const modePanel = defaultModes ? (
              <DefaultModes
                kind={tab === "abilities" ? "ability" : "server"}
                restricted={
                  !!defaultModes[tab === "abilities" ? "ability" : "server"]
                }
                busy={busy}
                onChange={changeMode}
              />
            ) : (
              <PremiumModeHint
                label={
                  tab === "abilities"
                    ? "Default ability access"
                    : "Default MCP server access"
                }
                description={
                  tab === "abilities"
                    ? "Premium can require an explicit Allow rule for abilities."
                    : "Premium can restrict servers by default and allow trusted ones."
                }
              />
            );

            if (tab === "abilities") {
              const rows = arr(data.abilities).filter((item: any) =>
                [item.name, item.label, item.description, item.category]
                  .join(" ")
                  .toLowerCase()
                  .includes(search.toLowerCase()),
              );

              return (
                <>
                  {modePanel}
                  <div className="ar-table-wrap">
                    <table className="ar-table ar-ability-table">
                      <thead>
                        <tr>
                          <th>{t("Ability")}</th>
                          <th>{t("Actions")}</th>
                        </tr>
                      </thead>
                      <tbody>
                        {rows
                          .slice(page * 20, page * 20 + 20)
                          .map((item: any) => (
                            <tr key={item.name}>
                              <td>
                                <div className="ar-resource-name">
                                  <span
                                    className="ar-ability-glyph dashicons dashicons-admin-generic"
                                    aria-hidden="true"
                                  />
                                  <div>
                                    <strong>
                                      {plain(item.label || item.name)}
                                    </strong>
                                    <small className="ar-ability-slug">
                                      {item.name}
                                    </small>
                                    {item.description && (
                                      <small>{plain(item.description)}</small>
                                    )}
                                    <div className="ar-ability-tags">
                                      <span>{plain(item.category)}</span>
                                      {item.rest_exposed && (
                                        <span>{t("REST")}</span>
                                      )}
                                      {item.mcp_exposed && (
                                        <span>{t("MCP")}</span>
                                      )}
                                    </div>
                                  </div>
                                </div>
                              </td>
                              <td>
                                <AccessRuleControl
                                  effect={item.effect}
                                  customized={item.customized}
                                  disabled={busy}
                                  onChange={(effect: string) =>
                                    change("ability", item.name, effect)
                                  }
                                />
                              </td>
                            </tr>
                          ))}
                        {!rows.length && (
                          <TableEmptyRow
                            colSpan={2}
                            title={t("No abilities found")}
                          >
                            {t("Try another search.")}
                          </TableEmptyRow>
                        )}
                      </tbody>
                    </table>
                  </div>
                  <Pager page={page} setPage={setPage} total={rows.length} />
                </>
              );
            }

            const servers = arr(data.servers).filter((server: any) =>
              [
                server.id,
                server.name,
                server.description,
                ...arr(server.tools).map((tool: any) => tool.name),
                ...arr(server.resources).map(
                  (item: any) => `${item.name} ${item.uri}`,
                ),
                ...arr(server.prompts).map((item: any) => item.name),
              ]
                .join(" ")
                .toLowerCase()
                .includes(search.toLowerCase()),
            );

            return (
              <>
                {modePanel}
                <div className="ar-mcp-servers">
                  {servers.map((server: any) => {
                    const open = expanded[server.id] ?? !!search;
                    const query = search.toLowerCase();
                    const suggested =
                      query &&
                      ["tools", "resources", "prompts"].find((kind) =>
                        arr(server[kind]).some((item: any) =>
                          [item.name, item.uri, item.description]
                            .join(" ")
                            .toLowerCase()
                            .includes(query),
                        ),
                      );
                    const selected =
                      primitive[server.id] || suggested || "tools";
                    const selectedItems = arr(server[selected]);
                    return (
                      <section className="ar-mcp-server" key={server.id}>
                        <div className="ar-mcp-server-head">
                          <span
                            className="ar-mcp-server-icon dashicons dashicons-networking"
                            aria-hidden="true"
                          />
                          <div className="ar-mcp-server-copy">
                            <strong>{plain(server.name)}</strong>
                            <small>{plain(server.description)}</small>
                            <code>{server.route}</code>
                          </div>
                          <AccessRuleControl
                            effect={server.effect}
                            customized={server.customized}
                            disabled={busy}
                            onChange={(effect: string) =>
                              change("server", server.id, effect)
                            }
                          />
                          <Button
                            variant="tertiary"
                            aria-expanded={open}
                            onClick={() =>
                              setExpanded((current) => ({
                                ...current,
                                [server.id]: !open,
                              }))
                            }
                          >
                            {open ? t("Hide details") : t("View primitives")}
                          </Button>
                        </div>
                        {open && (
                          <div className="ar-table-wrap ar-mcp-table-wrap">
                            <table className="ar-table ar-ability-table">
                              <thead>
                                <tr>
                                  <th>
                                    <div
                                      className="ar-mcp-primitive-switch"
                                      role="tablist"
                                      aria-label={`${plain(server.name)} ${t("primitives")}`}
                                    >
                                      {(
                                        [
                                          "tools",
                                          "resources",
                                          "prompts",
                                        ] as const
                                      ).map((kind) => (
                                        <button
                                          key={kind}
                                          type="button"
                                          role="tab"
                                          aria-selected={selected === kind}
                                          className={
                                            selected === kind ? "is-active" : ""
                                          }
                                          onClick={() =>
                                            setPrimitive((current) => ({
                                              ...current,
                                              [server.id]: kind,
                                            }))
                                          }
                                        >
                                          {kind === "tools"
                                            ? t("Tools")
                                            : kind === "resources"
                                              ? t("Resources")
                                              : t("Prompts")}
                                          <span>
                                            {arr(server[kind]).length}
                                          </span>
                                        </button>
                                      ))}
                                    </div>
                                  </th>
                                  <th className="ar-mcp-default-heading">
                                    {defaultModes ? (
                                      <ToggleControl
                                        className="ar-toggle"
                                        label={
                                          selected === "tools"
                                            ? t("Restrict tools by default")
                                            : selected === "resources"
                                              ? t(
                                                  "Restrict resources by default",
                                                )
                                              : t("Restrict prompts by default")
                                        }
                                        checked={
                                          !!defaultModes.primitive?.[
                                            server.id
                                          ]?.[
                                            selected === "tools"
                                              ? "tool"
                                              : selected === "resources"
                                                ? "resource"
                                                : "prompt"
                                          ]
                                        }
                                        disabled={busy}
                                        onChange={(checked: boolean) =>
                                          changeMode(
                                            selected === "tools"
                                              ? "tool"
                                              : selected === "resources"
                                                ? "resource"
                                                : "prompt",
                                            checked,
                                            server.id,
                                          )
                                        }
                                      />
                                    ) : (
                                      <span>{t("Default access")}</span>
                                    )}
                                  </th>
                                </tr>
                              </thead>
                              <tbody>
                                {selectedItems.map((item: any) => (
                                  <tr key={item.uri || item.name}>
                                    <td>
                                      <strong>
                                        {plain(item.name || item.uri)}
                                      </strong>
                                      {item.uri && (
                                        <small className="ar-ability-slug">
                                          {item.uri}
                                        </small>
                                      )}
                                      {item.description && (
                                        <small>{plain(item.description)}</small>
                                      )}
                                    </td>
                                    <td>
                                      <AccessRuleControl
                                        effect={item.effect}
                                        customized={item.customized}
                                        blockedByServer={
                                          server.effect === "deny" &&
                                          item.effect !== "deny"
                                        }
                                        disabled={busy}
                                        onChange={(effect: string) =>
                                          change(
                                            selected === "tools"
                                              ? "tool"
                                              : selected === "resources"
                                                ? "resource"
                                                : "prompt",
                                            item.uri || item.name,
                                            effect,
                                            server.id,
                                          )
                                        }
                                      />
                                    </td>
                                  </tr>
                                ))}
                                {!selectedItems.length && (
                                  <TableEmptyRow
                                    colSpan={2}
                                    title={
                                      selected === "tools"
                                        ? t("No tools registered")
                                        : selected === "resources"
                                          ? t("No resources registered")
                                          : t("No prompts registered")
                                    }
                                  >
                                    {selected === "tools"
                                      ? t(
                                          "This server has no registered tools.",
                                        )
                                      : selected === "resources"
                                        ? t(
                                            "This server has no registered resources.",
                                          )
                                        : t(
                                            "This server has no registered prompts.",
                                          )}
                                  </TableEmptyRow>
                                )}
                              </tbody>
                            </table>
                          </div>
                        )}
                      </section>
                    );
                  })}
                </div>
                {!servers.length && (
                  <Empty>{t("No MCP servers match this search.")}</Empty>
                )}
              </>
            );
          }}
        </Resource>
      )}
    </>
  );
}
