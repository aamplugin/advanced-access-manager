#!/bin/sh

set -eu

plugin_dir=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
cd "$plugin_dir"

if [ -n "${AAM_PCOV_EXTENSION:-}" ]; then
    extension_option="-dextension=$AAM_PCOV_EXTENSION"
elif php -r 'exit(extension_loaded("pcov") || extension_loaded("xdebug") ? 0 : 1);'; then
    extension_option=
else
    echo 'Code coverage requires PCOV or Xdebug. Set AAM_PCOV_EXTENSION to a PCOV module path if needed.' >&2
    exit 1
fi

export XDEBUG_MODE=coverage
mkdir -p build/coverage

# An empty option is ignored by the shell through the explicit branch.
if [ -n "$extension_option" ]; then
    exec php "$extension_option" -d pcov.enabled=1 -d "pcov.directory=$plugin_dir/application" \
        phpunit --configuration phpunit.xml.dist \
        --coverage-clover build/coverage/clover.xml \
        --coverage-html build/coverage/html --coverage-text "$@"
fi

exec php -d pcov.enabled=1 -d "pcov.directory=$plugin_dir/application" \
    phpunit --configuration phpunit.xml.dist \
    --coverage-clover build/coverage/clover.xml \
    --coverage-html build/coverage/html --coverage-text "$@"
