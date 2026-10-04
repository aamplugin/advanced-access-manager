import { TextControl, t } from "./core";

export const expirationChoices = [
  { id: "hour", label: t("1 hour") },
  { id: "day", label: t("24 hours") },
  { id: "week", label: t("7 days") },
  { id: "month", label: t("1 month") },
  { id: "custom", label: t("Choose date & time") },
];

export function ExpirationChoices({
  choice,
  onChoice,
  customDate,
  onCustomDate,
  label,
  help,
}: any) {
  return (
    <>
      <div
        className="ar-expiration-choice-list"
        role="group"
        aria-label={label}
      >
        {expirationChoices.map((option) => (
          <button
            key={option.id}
            type="button"
            className={`ar-expiration-choice ${choice === option.id ? "is-selected" : ""}`}
            aria-pressed={choice === option.id}
            onClick={() => onChoice(option.id)}
          >
            {option.id === "custom" && (
              <span
                className="dashicons dashicons-calendar-alt"
                aria-hidden="true"
              />
            )}
            {option.label}
          </button>
        ))}
      </div>
      {choice === "custom" && (
        <div className="ar-expiration-custom-date">
          <TextControl
            type="datetime-local"
            step={1}
            required
            label={t("Expires at (your local time)")}
            value={customDate}
            onChange={onCustomDate}
          />
          <p>{help || t("Select a future date and time.")}</p>
        </div>
      )}
    </>
  );
}
