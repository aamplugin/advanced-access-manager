const listeners = new Set();

function group(path) {
  const parts = new URL(path, "https://aam.invalid").pathname.split("/");
  const name = parts[3];
  if (name === "content") {
    return (
      {
        post: "posts",
        post_type: "post_types",
        taxonomy: "taxonomies",
        term: "terms",
      }[parts[4]] || "content"
    );
  }
  if (name === "mcp-server") {
    return parts[4] === "primitive"
      ? {
          tool: "mcp-tools",
          resource: "mcp-resources",
          prompt: "mcp-prompts",
        }[parts[5]] || "mcp-servers"
      : "mcp-servers";
  }
  return (
    {
      post: "posts",
      term: "terms",
      post_type: "post_types",
      taxonomy: "taxonomies",
      role: "roles",
      user: "users",
      url: "urls",
      policy: "policies",
      jwt: "jwts",
      capability: "capabilities",
      metabox: "metaboxes",
      widget: "widgets",
      "api-route": "api-routes",
      ability: "abilities",
    }[name] || name
  );
}

export function shouldRefreshResource(resourcePath, mutationPath) {
  const resource = new URL(resourcePath, "https://aam.invalid");
  const mutation = new URL(mutationPath, "https://aam.invalid");
  const mutationGroup = group(mutationPath);
  if (
    mutationGroup !== "settings" &&
    mutationGroup !== "core" &&
    group(resourcePath) !== mutationGroup
  ) {
    return false;
  }

  const resourceLevel = resource.searchParams.get("access_level");
  const mutationLevel = mutation.searchParams.get("access_level");
  if (resourceLevel && mutationLevel) {
    if (resourceLevel !== mutationLevel) return false;
    if (resourceLevel === "role" || resourceLevel === "user") {
      const key = resourceLevel === "role" ? "role_id" : "user_id";
      return resource.searchParams.get(key) === mutation.searchParams.get(key);
    }
  }
  return true;
}

export function subscribeResourceRefresh(listener) {
  listeners.add(listener);
  return () => listeners.delete(listener);
}

export function refreshAfterMutation(path) {
  for (const listener of listeners) listener(path);
}
