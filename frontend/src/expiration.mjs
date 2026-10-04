export function expirationPayload(choice, customDate, now = Date.now()) {
  if (choice !== "custom") {
    const choices = {
      hour: "1 hour",
      day: "24 hours",
      week: "7 days",
      month: "1 month",
    };
    if (!Object.hasOwn(choices, choice))
      throw new Error("Choose an expiration time.");
    return { expires_in: choices[choice] };
  }
  const date = new Date(customDate);
  if (!Number.isFinite(date.getTime()) || date.getTime() <= now)
    throw new Error("Choose a future date and time for expiration.");
  return { expires_at: date.toISOString() };
}
