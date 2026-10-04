/** Describe the change that the REST API confirmed. Templates are translated in the UI. */
export function mutationToast(path, method, data = {}, subject = {}) {
  const url = new URL(path, "https://aam.invalid");
  const parts = url.pathname.replace(/^\/aam\/v2\/?/, "").split("/");
  let [resource, rawId] = parts;
  if (
    resource === "content" &&
    ["post", "post_type", "taxonomy", "term"].includes(rawId)
  ) {
    resource = rawId;
    rawId = parts[2];
  }
  const id = rawId ? decodeURIComponent(rawId) : "";
  const who = subject.name || "";
  const effect = data?.effect;
  const deleting = method === "DELETE";
  const result = (template, ...args) => ({ template, args });

  const mcpPrimitive =
    resource === "mcp-server" && parts[1] === "primitive" ? parts[2] : null;
  if (
    resource === "mcp-server" &&
    (parts[1] === "mode" || parts[3] === "mode")
  ) {
    if (mcpPrimitive) {
      const item =
        {
          tool: "MCP tools",
          resource: "MCP resources",
          prompt: "MCP prompts",
        }[mcpPrimitive] || "MCP primitives";
      return result(
        effect === "deny"
          ? "%1$s on %2$s restricted by default for %3$s."
          : "%1$s on %2$s allowed by default for %3$s.",
        item,
        url.searchParams.get("server_id") || "this server",
        who,
      );
    }
    return result(
      effect === "deny"
        ? "%1$s restricted by default for %2$s."
        : "%1$s allowed by default for %2$s.",
      "MCP servers",
      who,
    );
  }
  if (resource === "ability" || resource === "mcp-server") {
    const name = url.searchParams.get("resource") || id;
    const server = url.searchParams.get("server_id");
    const item =
      resource === "ability"
        ? "Ability"
        : mcpPrimitive
          ? `MCP ${mcpPrimitive}`
          : "MCP server";
    const reference = mcpPrimitive && server ? `${name} (${server})` : name;
    if (deleting)
      return result(
        "%1$s %2$s now inherits access for %3$s.",
        item,
        reference,
        who,
      );
    if (effect === "deny")
      return result("%1$s %2$s restricted for %3$s.", item, reference, who);
    return result("%1$s %2$s allowed for %3$s.", item, reference, who);
  }

  if (resource === "ability-access") {
    const item = "Abilities";
    return result(
      effect === "deny"
        ? "%1$s restricted by default for %2$s."
        : "%1$s allowed by default for %2$s.",
      item,
      who,
    );
  }

  if (
    [
      "abilities",
      "mcp-servers",
      "mcp-tools",
      "mcp-resources",
      "mcp-prompts",
    ].includes(resource) &&
    deleting
  ) {
    const item = {
      abilities: "Ability",
      "mcp-servers": "MCP server",
      "mcp-tools": "MCP tool",
      "mcp-resources": "MCP resource",
      "mcp-prompts": "MCP prompt",
    }[resource];
    return result("%1$s rules reset for %2$s.", item, who);
  }

  const access = {
    "backend-menu": "Backend menu",
    "admin-toolbar": "Admin toolbar",
    metabox: "Metabox",
    metaboxes: "Metabox",
    widget: "Widget",
    widgets: "Widget",
    "api-route": "API route",
    "api-routes": "API route",
  }[resource];
  if (access) {
    if (deleting) return result("%1$s rules reset for %2$s.", access, who);
    if (!id && effect === "deny")
      return result("%1$s restricted by default for %2$s.", access, who);
    if (!id && effect === "allow")
      return result("%1$s allowed by default for %2$s.", access, who);
    if (effect === "deny")
      return result("%1$s access restricted for %2$s.", access, who);
    if (effect === "allow")
      return result("%1$s access allowed for %2$s.", access, who);
    return result("%1$s settings saved for %2$s.", access, who);
  }

  if (resource === "role" || resource === "roles") {
    if (deleting) return result("Role %1$s deleted.", id);
    if (data?.add_capabilities?.length)
      return result("Capabilities granted to role %1$s.", id);
    if (data?.deprive_capabilities?.length || data?.remove_capabilities?.length)
      return result("Capabilities removed from role %1$s.", id);
    return result(
      resource === "roles" ? "Role %1$s created." : "Role %1$s updated.",
      data?.name || id,
    );
  }

  if (resource === "user" || resource === "users") {
    if (deleting && url.searchParams.get("reset") === "expiration")
      return result("Time limit removed for user #%1$s.", id);
    if (deleting) return result("Access reset for user #%1$s.", id);
    if (data?.status)
      return result(
        "User #%1$s %2$s.",
        id,
        data.status === "inactive" ? "deactivated" : "activated",
      );
    if (data?.expiration)
      return result("Temporary access set for user #%1$s.", id);
    if (data?.add_capabilities?.length)
      return result("Capabilities granted to user #%1$s.", id);
    if (data?.deprive_capabilities?.length || data?.remove_capabilities?.length)
      return result("Capabilities removed from user #%1$s.", id);
    return result("User #%1$s updated.", id);
  }

  if (resource === "capability" || resource === "capabilities") {
    if (deleting) {
      if (data?.globally) {
        return subject.type === "user"
          ? result("Capability %1$s removed from all roles and %2$s.", id, who)
          : result("Capability %1$s removed from all registered roles.", id);
      }
      return result("Capability %1$s removed from %2$s.", id, who);
    }
    if (resource === "capabilities")
      return result("Capability %1$s created.", data?.slug);
    return result("Capability %1$s renamed to %2$s.", id, data?.slug);
  }

  if (["post", "term", "post_type", "taxonomy"].includes(resource)) {
    const item = {
      post: "Post",
      term: "Term",
      post_type: "Post type",
      taxonomy: "Taxonomy",
    }[resource];
    if (deleting) return result("%1$s access rules reset for %2$s.", item, who);
    if (Array.isArray(data?.permissions) && data.permissions.length) {
      const changes = data.permissions
        .slice(0, 2)
        .map((entry) => `${entry.permission} ${entry.effect}`)
        .join(", ");
      const more =
        data.permissions.length > 2 ? ` +${data.permissions.length - 2}` : "";
      return result(
        "%1$s permissions saved (%2$s) for %3$s.",
        item,
        changes + more,
        who,
      );
    }
    return result("%1$s access rules reset for %2$s.", item, who);
  }

  if (resource === "config") {
    const key = id.split(".");
    const label =
      key[key.length - 1] === "enabled"
        ? key[key.length - 2]
        : key[key.length - 1];
    const setting = String(label || "setting").replace(/_/g, " ");
    if (typeof data?.value === "boolean") {
      if (key[0] === "service")
        return result(
          "%1$s service %2$s.",
          setting,
          data.value ? "enabled" : "disabled",
        );
      return result(
        "%1$s setting %2$s.",
        setting,
        data.value ? "enabled" : "disabled",
      );
    }
    return result("%1$s setting updated.", setting);
  }
  if (resource === "configs") return result("Configuration settings saved.");
  if (resource === "configpress") return result("ConfigPress settings saved.");
  if (resource === "premium" && id === "geo-settings")
    return result("Geo lookup settings saved.");
  if (resource === "settings")
    return result(
      deleting
        ? "Access settings reset for %1$s."
        : "Access settings saved for %1$s.",
      who,
    );
  if (resource === "core") {
    if (id === "reset") return result("All AAM settings reset.");
    if (id === "import") return result("AAM settings imported.");
  }

  if (resource === "redirect") {
    if (id === "access-denied") {
      const area = url.searchParams.get("area");
      if (area === "api")
        return result(
          deleting
            ? "API denial response reset for %s."
            : "API denial response saved for %s.",
          who,
        );
      return result(
        deleting
          ? "%1$s access denied redirect reset for %2$s."
          : "%1$s access denied redirect saved for %2$s.",
        area === "backend" ? "Backend" : "Frontend",
        who,
      );
    }
    const label =
      {
        "not-found": "Not found",
        login: "Login",
        logout: "Logout",
      }[id] || id.replace(/-/g, " ");
    const area = url.searchParams.get("area");
    if (area)
      return result(
        deleting
          ? "%1$s redirect reset in %2$s for %3$s."
          : "%1$s redirect saved in %2$s for %3$s.",
        label,
        area,
        who,
      );
    return result(
      deleting
        ? "%1$s redirect reset for %2$s."
        : "%1$s redirect saved for %2$s.",
      label,
      who,
    );
  }
  if (["login-redirect", "logout-redirect"].includes(resource))
    return result(
      deleting ? "%1$s reset for %2$s." : "%1$s saved for %2$s.",
      resource === "login-redirect" ? "Login redirect" : "Logout redirect",
      who,
    );

  if (resource === "url" || resource === "urls") {
    if (deleting)
      return result(
        resource === "url"
          ? "URL access rule removed for %1$s."
          : "URL access rules reset for %1$s.",
        who,
      );
    return result("URL access rule saved for %1$s.", who);
  }
  if (resource === "policy" || resource === "policies") {
    if (deleting)
      return resource === "policy"
        ? result("Policy deleted.")
        : result("Policy assignments reset for %1$s.", who);
    if (effect === "attach") return result("Policy attached to %1$s.", who);
    if (effect === "detach") return result("Policy detached from %1$s.", who);
    return result("Policy saved for %1$s.", who);
  }
  if (resource === "jwt" || resource === "jwts")
    return result(
      deleting
        ? resource === "jwt"
          ? "JWT token revoked."
          : "All JWT tokens revoked."
        : resource === "jwts"
          ? "JWT token created."
          : "JWT token updated.",
    );
  if (resource === "identity")
    return result(
      deleting
        ? "Identity rules reset for %1$s."
        : "Identity rules saved for %1$s.",
      who,
    );

  const label = resource.replace(/[-_]/g, " ");
  return result(
    deleting
      ? "%1$s settings reset for %2$s."
      : "%1$s settings saved for %2$s.",
    label,
    who,
  );
}
