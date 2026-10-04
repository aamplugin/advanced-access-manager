import { useWorkspace, t } from "./core";

export function PremiumModeHint({ label, description }: any) {
  const { boot } = useWorkspace();
  const premium = boot.premiumAvailability;
  if (!premium || premium.active) return null;
  const activate = premium.installed && premium.pluginsUrl;
  const url = activate ? premium.pluginsUrl : premium.purchaseUrl;
  return (
    <div className="ar-premium-mode-hint" role="note">
      <span className="dashicons dashicons-lock" aria-hidden="true" />
      <span className="ar-premium-mode-hint-copy">
        <strong>{t(label)}</strong>
        <span>{t(description)}</span>
      </span>
      {url && (
        <a href={url} target={activate ? undefined : "_blank"}
          rel={activate ? undefined : "noopener noreferrer"}>
          {activate ? t("Activate Premium") : t("About Premium")}
          <span className="dashicons dashicons-arrow-right-alt" aria-hidden="true" />
        </a>
      )}
    </div>
  );
}
