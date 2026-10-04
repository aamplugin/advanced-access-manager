export function userListPath({
  page = 0,
  search = "",
  role = "",
  status = "",
}) {
  const params = new URLSearchParams({
    fields: "display_name,user_login,roles,status,permissions,expiration",
    per_page: "20",
    offset: String(page * 20),
    search,
  });
  if (role) params.set("role", role);
  if (status) params.set("status", status);
  return "/users?" + params.toString();
}

export function nextSelectableRole(roles, deletedSlug) {
  if (!roles.length) return null;
  const current = roles.findIndex(
    (role) => (role.slug ?? role.value) === deletedSlug,
  );
  for (let step = 1; step <= roles.length; step++) {
    const role = roles[(current + step) % roles.length];
    const slug = role.slug ?? role.value;
    if (
      slug !== deletedSlug &&
      (!role.permissions || role.permissions.includes("allow_manage"))
    ) {
      return { type: "role", id: slug, name: role.name ?? role.label };
    }
  }
  return null;
}
