export function passwordlessPath(userId) {
  const id = Number(userId);
  if (!Number.isSafeInteger(id) || id <= 0) {
    throw new Error("A valid user ID is required.");
  }
  return `/aam/v2/jwts?user_id=${id}&fields=signed_url`;
}

export function userExpirationPayload(date, trigger, role) {
  return {
    expiration: {
      expires_at: new Date(date).toISOString(),
      trigger:
        trigger === "change_role" ? { type: trigger, to_role: role } : trigger,
    },
  };
}
