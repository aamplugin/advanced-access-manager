import { t } from "./core";

export function ResourceIcon({
  icon,
  fallback = "admin-post",
  child = false,
}: any) {
  const value = String(icon || "").trim();
  const dashicon = /^dashicons-[a-z0-9-]+$/.test(value) ? value : "";
  const image =
    /^https?:\/\//i.test(value) ||
    /^\/(?!\/)/.test(value) ||
    /^data:image\/svg\+xml(?:;[^,]*)?,/i.test(value);
  return (
    <span
      className={`ar-resource-icon${child ? " ar-resource-icon-child" : ""}`}
      aria-hidden="true"
    >
      {child ? (
        <span className="dashicons dashicons-arrow-right-alt2" />
      ) : dashicon ? (
        <span className={`dashicons ${dashicon}`} />
      ) : image ? (
        <img src={value} alt="" />
      ) : (
        <span className={`dashicons dashicons-${fallback}`} />
      )}
    </span>
  );
}

const states: any = {
  customized: { icon: "edit", label: "Customized access rules" },
  restricted: { icon: "lock", label: "Access restricted" },
  allowed: { icon: "yes-alt", label: "Access not restricted" },
  attached: { icon: "admin-links", label: "Policy attached" },
  valid: { icon: "yes-alt", label: "Token valid" },
  invalid: { icon: "warning", label: "Token expired or invalid" },
};

export function StateIcon({ state }: { state: string }) {
  const item = states[state];
  if (!item) return null;
  return (
    <span
      className={`ar-state-icon ar-state-icon-${state}`}
      role="img"
      aria-label={t(item.label)}
      title={t(item.label)}
    >
      <span className={`dashicons dashicons-${item.icon}`} aria-hidden="true" />
    </span>
  );
}

/** A resolved AAM result and whether this level owns the rule. */
export function AccessOutcome({
  effect,
  customized,
  pending = false,
  native = false,
  source,
  detail,
}: {
  effect?: string | null;
  customized?: boolean;
  pending?: boolean;
  native?: boolean;
  source?: string;
  detail?: string;
}) {
  const state =
    effect === "deny"
      ? "deny"
      : effect === "allow"
        ? "allow"
        : effect === "mixed"
          ? "mixed"
          : "none";
  const outcome =
    state === "deny"
      ? t("Restricted")
      : state === "allow"
        ? native
          ? t("AAM does not restrict")
          : t("Not restricted")
        : state === "mixed"
          ? t("Mixed controls")
          : t("No AAM rule");
  const origin =
    state === "none"
      ? ""
      : pending
        ? t("unsaved")
        : source
          ? source
          : customized === true
            ? t("set here")
            : customized === false
              ? t("inherited")
              : "";
  const explanation =
    detail
      ? detail
      : customized === false && state !== "none"
          ? t(
            "This resource has no direct override at the selected access level. A broader rule or access policy supplies this result.",
          )
        : native && state !== "deny"
          ? t(
              "AAM does not block this resource. WordPress or its provider may still deny access.",
            )
          : outcome;
  const icon =
    state === "deny"
      ? "lock"
      : state === "allow"
        ? "yes-alt"
        : state === "mixed"
          ? "randomize"
          : "minus";

  return (
    <span
      className={`ar-access-outcome is-${state}`}
      title={explanation}
      role="img"
      aria-label={`${outcome}${origin ? `, ${origin}` : ""}. ${explanation}`}
    >
      <span className={`dashicons dashicons-${icon}`} aria-hidden="true" />
      <span>{outcome}</span>
      {origin && <span className="ar-access-origin">· {origin}</span>}
    </span>
  );
}

export function ResourceOutcome({
  permissions,
  customized,
  native = false,
}: {
  permissions?: Record<string, any>;
  customized?: boolean;
  native?: boolean;
}) {
  const effects = Object.values(permissions || {})
    .map((rule: any) => rule?.effect)
    .filter((effect) => effect === "allow" || effect === "deny");
  const effect = !effects.length
    ? null
    : effects.every((value) => value === effects[0])
      ? effects[0]
      : "mixed";

  return (
    <AccessOutcome effect={effect} customized={customized} native={native} />
  );
}
