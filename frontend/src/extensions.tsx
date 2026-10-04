import { useWorkspace, Title, Badge, Button, t } from "./core";

export function Extensions() {
  const { boot } = useWorkspace();
  const premium = boot.premium || {};
  const hasLicense = !!premium.license;

  return (
    <>
      <Title
        title={t("Extensions")}
        description={t("Your AAM Premium Add-On and license in one place.")}
        icon="admin-plugins"
      />
      <section className="ar-extension-card">
        <span
          className="ar-extension-mark dashicons dashicons-shield-alt"
          aria-hidden="true"
        />
        <div className="ar-extension-body">
          <div className="ar-extension-heading">
            <span className="ar-eyebrow">{t("AAM PREMIUM ADD-ON")}</span>
            <Badge tone={hasLicense ? "success" : "purple"}>
              {hasLicense
                ? premium.installed
                  ? t("Installed")
                  : t("License registered")
                : premium.installed
                  ? t("License needed")
                  : t("Available")}
            </Badge>
          </div>

          {hasLicense ? (
            <>
              <h3>
                {premium.installed
                  ? t("Your Premium Add-On is ready")
                  : t("Your premium license is registered")}
              </h3>
              <p>
                {premium.installed
                  ? t(
                      "Manage your subscription, site activations, and add-on downloads from your license page.",
                    )
                  : t(
                      "Install and activate the Premium Add-On to use this license on your site.",
                    )}
              </p>
              <div className="ar-extension-license">
                <span>{t("License key")}</span>
                <code>{premium.license}</code>
              </div>
              {premium.version && (
                <p className="ar-extension-version">
                  {t("Installed version")}: {premium.version}
                </p>
              )}
              <div className="ar-extension-actions">
                <Button
                  variant="primary"
                  href={premium.manageUrl}
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  {t("Manage license")} ↗
                </Button>
                {!premium.installed && (
                  <Button
                    variant="secondary"
                    href="https://aamportal.com/question/how-to-install-premium-complete-package-addon"
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    {t("Installation guide")}
                  </Button>
                )}
              </div>
            </>
          ) : (
            <>
              <h3>
                {premium.installed
                  ? t("Your Premium Add-On needs a license")
                  : t("Expand what AAM can manage")}
              </h3>
              <p>
                {premium.installed
                  ? t(
                      "The add-on is installed, but this site has no registered license key. Check your license setup or explore the Premium plans.",
                    )
                  : t(
                      "The Premium Add-On adds broader content controls, role inheritance, and advanced access rules.",
                    )}
              </p>
              {!premium.installed && (
                <ul className="ar-extension-perks">
                  <li>{t("Default access for post types and terms")}</li>
                  <li>{t("Multi-level role management")}</li>
                  <li>{t("Wildcard URL access rules")}</li>
                </ul>
              )}
              <div className="ar-extension-actions">
                <Button
                  variant="primary"
                  href={
                    premium.purchaseUrl ||
                    "https://aamportal.com/premium?ref=plugin"
                  }
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  {t("Explore Premium")} ↗
                </Button>
                {premium.installed && (
                  <Button
                    variant="secondary"
                    href="https://aamportal.com/question/how-to-resolve-unlicensed-aam-complete-package-detected-message"
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    {t("License setup help")}
                  </Button>
                )}
              </div>
            </>
          )}
        </div>
      </section>
    </>
  );
}
