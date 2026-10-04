#!/usr/bin/env bash
set -euo pipefail

plugin_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$plugin_dir"

if [[ -n "${AAM_WP_CLI_PHAR:-}" ]]; then
  wp_cli=(php -d error_reporting=8191 -d display_errors=0 "$AAM_WP_CLI_PHAR")
elif command -v wp >/dev/null 2>&1; then
  wp_cli=(wp)
else
  echo "WP-CLI is required. Install wp or set AAM_WP_CLI_PHAR." >&2
  exit 1
fi

tmp_dir="$(mktemp -d)"
trap 'rm -rf "$tmp_dir"' EXIT

"${wp_cli[@]}" i18n make-pot . "$tmp_dir/php.pot" \
  --domain=advanced-access-manager --skip-js \
  --exclude=vendor,frontend,media,tests,old_tests --skip-audit
xgettext --language=PHP --keyword --keyword=preparePhrase:1 \
  --from-code=UTF-8 --output="$tmp_dir/phrases.pot" \
  application/Backend/Manager.php \
  application/Backend/Feature/Settings/Core.php \
  application/Backend/Feature/Settings/Security.php

(cd frontend && node i18n/extract.mjs)
msgcat --sort-output "$tmp_dir/php.pot" \
  "$tmp_dir/phrases.pot" lang/advanced-access-manager-frontend.pot \
  -o "$tmp_dir/combined.pot"
node frontend/i18n/merge-pot.mjs "$tmp_dir/php.pot" \
  "$tmp_dir/combined.pot" lang/advanced-access-manager.pot

for po in lang/advanced-access-manager-*.po; do
  msgmerge --no-fuzzy-matching --update --backup=none "$po" \
    lang/advanced-access-manager.pot
  msgfmt --check --check-format -o "${po%.po}.mo" "$po"
done

"${wp_cli[@]}" i18n make-json lang --no-purge
(cd frontend && node i18n/fix-json.mjs)
