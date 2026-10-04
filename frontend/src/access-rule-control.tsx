import { t } from "./core";
import { AccessOutcome } from "./resource-visuals";

export function AccessRuleControl({
  effect,
  customized,
  disabled,
  onChange,
  blockedByServer = false,
  native = true,
  label,
  disabledReason,
}: any) {
  return (
    <div className="ar-ability-rule">
      <AccessOutcome
        effect={blockedByServer ? "deny" : effect}
        customized={blockedByServer ? false : customized}
        native={native}
        source={blockedByServer ? t("MCP server") : undefined}
        detail={
          blockedByServer
            ? t(
                "This MCP server restricts the tool regardless of its own tool rule.",
              )
            : undefined
        }
      />
      <div
        className="ar-ability-rule-choices"
        role="group"
        aria-label={label || t("AAM access rule")}
        title={disabled ? disabledReason : undefined}
      >
        {[
          { value: "allow", label: t("Allow") },
          { value: "deny", label: t("Deny") },
          { value: "inherit", label: t("Inherit") },
        ].map((choice) => (
          <button
            key={choice.value}
            type="button"
            disabled={disabled}
            className={
              (customized && effect === choice.value) ||
              (!customized && choice.value === "inherit")
                ? "is-active " + (choice.value === "deny" ? "is-deny" : "")
                : ""
            }
            aria-pressed={
              customized ? effect === choice.value : choice.value === "inherit"
            }
            onClick={() => onChange(choice.value)}
          >
            {choice.label}
          </button>
        ))}
      </div>
    </div>
  );
}
