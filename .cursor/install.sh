#!/usr/bin/env bash
set -euo pipefail

if command -v php >/dev/null 2>&1; then
  php -v | head -1
  exit 0
fi

if ! command -v sudo >/dev/null 2>&1; then
  echo "php-cli is not installed and sudo is unavailable." >&2
  exit 1
fi

sudo DEBIAN_FRONTEND=noninteractive apt-get install -y -qq php-cli
php -v | head -1
