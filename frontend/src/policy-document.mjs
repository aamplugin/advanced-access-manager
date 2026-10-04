function resourceWarnings(document, known) {
  const warnings = new Set();
  const statements = Array.isArray(document.Statement)
    ? document.Statement
    : document.Statement
      ? [document.Statement]
      : [];

  for (const statement of statements) {
    if (!statement || typeof statement !== "object") continue;
    const resources = Array.isArray(statement.Resource)
      ? statement.Resource
      : [statement.Resource];

    for (const resource of resources) {
      if (typeof resource !== "string") continue;
      if (resource === "*") {
        if (known && !known.wildcards) warnings.add(resource);
        continue;
      }
      const relevant = /^(Ability|MCPServer|MCPTool|MCPPrompt|MCPResource):/i.test(resource);
      if (!relevant) continue;
      const normalized = resource.toLowerCase();
      const actions = statement.Action === undefined
        ? []
        : Array.isArray(statement.Action)
          ? statement.Action
          : [statement.Action];
      if (actions.some((action) =>
        typeof action !== "string" || action.toLowerCase() !== "access"
      )) {
        warnings.add(resource);
        continue;
      }

      try {
        if (normalized.startsWith("ability:")) {
          const name = resource.slice("Ability:".length);
          if (name === "*") {
            if (known && !known.wildcards) warnings.add(resource);
            continue;
          }
          if (name.includes("*")) {
            if (!known?.wildcards || !/^[a-z0-9*-]+\/[a-z0-9*-]+$/.test(name)) {
              warnings.add(resource);
            }
            continue;
          }
          if (!/^[a-z0-9-]+\/[a-z0-9-]+$/.test(name)
            || (Array.isArray(known?.abilities)
              && !known.abilities.includes(name))) {
            warnings.add(resource);
          }
        } else if (normalized.startsWith("mcpserver:")) {
          const id = decodeURIComponent(resource.slice("MCPServer:".length));
          if (id === "*") {
            if (known && !known.wildcards) warnings.add(resource);
            continue;
          }
          if (id.includes("*")) {
            if (!known?.wildcards || id.length > 200) warnings.add(resource);
            continue;
          }
          if (!id || id.length > 200
            || (Array.isArray(known?.servers)
              && !known.servers.includes(id))) {
            warnings.add(resource);
          }
        } else {
          const kind = normalized.startsWith("mcpresource:")
            ? "resources"
            : normalized.startsWith("mcpprompt:")
              ? "prompts"
              : "tools";
          const value = resource.slice(kind === "resources" ? "MCPResource:".length : kind === "prompts" ? "MCPPrompt:".length : "MCPTool:".length);
          let serverValue;
          let toolValue;
          if (statement.Server !== undefined) {
            serverValue = statement.Server;
            toolValue = value;
          } else {
            const slash = value.indexOf("/");
            const separator = slash >= 0 ? slash : value.indexOf(":");
            serverValue = separator >= 0 ? value.slice(0, separator) : "";
            toolValue = separator >= 0 ? value.slice(separator + 1) : "";
          }
          if (typeof serverValue !== "string" || typeof toolValue !== "string") {
            warnings.add(resource);
            continue;
          }
          const server = decodeURIComponent(serverValue);
          const name = decodeURIComponent(toolValue);
          const wildcard = server.includes("*") || name.includes("*");
          if (!server || !name || server.length > 200 || name.length > (kind === "resources" ? 2048 : 200)
            || (wildcard && !known?.wildcards)
            || (!wildcard && Array.isArray(known?.[kind])
              && !known[kind].some((item) =>
                item.server === server && item.name === name))) {
            warnings.add(resource);
          }
        }
      } catch {
        warnings.add(resource);
      }
    }
  }

  return [...warnings];
}

export function validatePolicyJson(source, knownResources = null) {
  if (!source.trim()) {
    return {
      valid: false,
      message: "Enter a JSON policy document.",
      line: 1,
      column: 1,
    };
  }

  let document;
  try {
    document = JSON.parse(source);
  } catch (error) {
    const message = error.message || "Invalid JSON.";
    const location = /line (\d+) column (\d+)/i.exec(message);
    let line = location ? Number(location[1]) : null;
    let column = location ? Number(location[2]) : null;
    if (!location) {
      const position = /position (\d+)/i.exec(message);
      if (position) {
        const before = source.slice(0, Number(position[1]));
        line = before.split("\n").length;
        column = before.length - before.lastIndexOf("\n");
      }
    }
    return { valid: false, message, line, column };
  }

  if (!document || typeof document !== "object" || Array.isArray(document)) {
    return {
      valid: false,
      message: "An access policy must be a JSON object.",
      line: 1,
      column: 1,
    };
  }

  const statement = document.Statement;
  const count = Array.isArray(statement) ? statement.length : statement ? 1 : 0;
  const incomplete = Array.isArray(statement)
    ? statement.some(
        (item) =>
          !item ||
          !item.Resource ||
          (Array.isArray(item.Resource) && !item.Resource.length),
      )
    : statement &&
      (!statement.Resource ||
        (Array.isArray(statement.Resource) && !statement.Resource.length));

  return {
    valid: true,
    document,
    count,
    incomplete: Boolean(incomplete),
    resourceWarnings: resourceWarnings(document, knownResources),
  };
}

export function formatPolicyJson(source) {
  const result = validatePolicyJson(source);
  if (!result.valid) throw new Error(result.message);
  return JSON.stringify(result.document, null, 2);
}
