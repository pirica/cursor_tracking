#!/usr/bin/env bash
set -euo pipefail

# Ensures the PHP runtime and required extensions for the dashboard are present.
# The app uses mbstring (mb_strlen/mb_substr) in lib/transcript_parse.php, so
# php-mbstring is required in addition to php-cli. Idempotent.

need_install=0
command -v php >/dev/null 2>&1 || need_install=1
php -m 2>/dev/null | grep -qi '^mbstring$' || need_install=1

if [ "$need_install" -eq 1 ]; then
  if ! command -v sudo >/dev/null 2>&1; then
    echo "php-cli/php-mbstring are missing and sudo is unavailable." >&2
    exit 1
  fi
  sudo DEBIAN_FRONTEND=noninteractive apt-get update -qq || true
  sudo DEBIAN_FRONTEND=noninteractive apt-get install -y -qq php-cli php-mbstring
fi

php -v | head -1
if php -m | grep -qi '^mbstring$'; then
  echo "mbstring extension: OK"
else
  echo "WARNING: mbstring extension not loaded." >&2
fi
