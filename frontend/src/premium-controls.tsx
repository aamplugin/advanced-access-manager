import { SelectControl, TextControl, t } from "./core";

export const conditionComplete = (condition: any) =>
  !!condition?.criteria &&
  !!condition?.type &&
  !!String(condition?.value || "").trim();

export function ConditionalFields({
  value = {},
  onChange,
  options,
  required = true,
}: any) {
  if (!options) return null;
  return (
    <div className="ar-condition-fields">
      <SelectControl
        label={t("Match")}
        value={value.criteria || ""}
        options={[
          { value: "", label: t("Choose an attribute") },
          ...Object.entries(options.criteria_types || {}).map(
            ([key, label]) => ({
              value: key,
              label: String(label),
            }),
          ),
        ]}
        required={required}
        onChange={(criteria) => onChange({ ...value, criteria })}
      />
      <SelectControl
        label={t("Comparison")}
        value={value.type || ""}
        options={[
          { value: "", label: t("Choose a comparison") },
          ...Object.entries(options.condition_types || {}).map(
            ([key, label]) => ({
              value: key,
              label: String(label),
            }),
          ),
        ]}
        required={required}
        onChange={(type) => onChange({ ...value, type })}
      />
      <TextControl
        label={t("Value")}
        value={value.value || ""}
        required={required}
        placeholder={t("Enter a value to compare")}
        onChange={(next) => onChange({ ...value, value: next })}
      />
    </div>
  );
}
