export function eligibleParentRoles(roles, currentSlug) {
  if (!currentSlug) return roles;
  const bySlug = new Map(roles.map((role) => [role.slug, role]));

  return roles.filter((candidate) => {
    const visited = new Set();
    let slug = candidate.slug;

    while (slug && !visited.has(slug)) {
      if (slug === currentSlug) return false;
      visited.add(slug);
      slug = bySlug.get(slug)?.parent?.slug;
    }

    return true;
  });
}

export function parentRoleCounts(roles) {
  const counts = new Map();
  for (const role of roles) {
    const parent = role.parent?.slug;
    if (parent) counts.set(parent, (counts.get(parent) || 0) + 1);
  }
  return counts;
}
