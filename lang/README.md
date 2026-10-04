# AAM translations

The source catalog is `advanced-access-manager.pot`. The ten locale catalogs
are `advanced-access-manager-{locale}.po`; WordPress loads their compiled `.mo`
files for PHP and the hashed `.json` files for JavaScript. The supported locales
are `de_DE`, `es_ES`, `fr_FR`, `it_IT`, `ja`, `nl_NL`, `pl_PL`, `pt_BR`, `ru_RU`,
and `zh_CN`.

## Update after changing UI text

Install WP-CLI, GNU gettext (`msgcat`, `msgmerge`, `msgfmt`, and `xgettext`),
Node.js, and the dependencies in `frontend/package.json`. Then run from the
plugin root:

```sh
npm --prefix frontend install
scripts/update-translations.sh
```

If WP-CLI is available only as a PHAR, set `AAM_WP_CLI_PHAR` to its path.
The script extracts PHP strings and the React `t()` strings, merges them into
the POT, updates each PO, compiles MO files, and generates the JSON files that
`wp_set_script_translations()` expects. It reports dynamic `t()` calls for
review. Add any new dynamically translated labels to the extractor or make
their source strings static before publishing.

Edit translations in the PO files, then rerun the script to rebuild MO and
JSON. Run `npm --prefix frontend test` to check the JavaScript catalog path.

## Translation sources and review

The initial approved translations came from the
[WordPress.org translation project](https://translate.wordpress.org/projects/wp-plugins/advanced-access-manager/).
Missing entries were filled with local Argos Translate drafts. Each draft has
a `Machine translation draft (Argos Translate); native review needed.` comment
in its PO entry. These are starting points, not reviewed translations. Native
speakers should check terminology, access control language, and context before
release, especially security related labels. The draft script preserves
existing translations and checks formatting tokens; it needs Python packages
`argostranslate` and `polib` plus the appropriate English to target Argos model.
To draft newly added strings locally, run
`python scripts/draft-translations.py fr_FR` (substitute the locale), then rerun
`scripts/update-translations.sh`.

WordPress.org language packs may take precedence over bundled PHP catalogs
when installed. Submit reviewed strings to the WordPress.org translation
project so users receiving language packs get the same improvements.
