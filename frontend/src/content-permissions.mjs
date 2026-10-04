export function changedPermissions(permissions, dirty) {
  return Object.keys(dirty).map((permission) => ({
    permission,
    ...permissions[permission],
  }));
}
