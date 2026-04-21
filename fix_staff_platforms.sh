#!/usr/bin/env bash
set -Eeuo pipefail

cd ~/public_html

STAMP="$(date +%Y%m%d_%H%M%S)"
BACKUP_DIR="$HOME/public_html/_backup_staff_platforms_$STAMP"
mkdir -p "$BACKUP_DIR"

FILES=(
  public/staff/platforms/index.php
  public/staff/platforms/_platform_template.php
)

HEADER_FILE="$(mktemp)"
cat > "$HEADER_FILE" <<'PHP'
<?php
declare(strict_types=1);

require_once __DIR__ . '/../../_init.php';
mk_require_staff_login();

/**
 * Staff: Platforms
 * Canonical bootstrap + auth gate
 */

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

if (!function_exists('h')) {
  function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
  }
}

if (!function_exists('redirect_to')) {
  function redirect_to(string $location): void {
    $location = str_replace(["\r", "\n"], '', $location);
    header('Location: ' . $location, true, 302);
    exit;
  }
}

if (!function_exists('pf__u')) {
  function pf__u(string $path): string {
    return function_exists('url_for') ? url_for($path) : $path;
  }
}

PHP

normalize_file() {
  local f="$1"
  local body tmp
  body="$(mktemp)"
  tmp="$(mktemp)"

  mkdir -p "$BACKUP_DIR/$(dirname "$f")"
  cp -a "$f" "$BACKUP_DIR/$f"

  awk '
    {
      lines[NR] = $0
    }
    END {
      i = 1

      if (i in lines && lines[i] ~ /^<\?php[[:space:]]*$/) i++
      if (i in lines && lines[i] ~ /^declare\(strict_types=1\);[[:space:]]*$/) i++
      while (i in lines && lines[i] ~ /^[[:space:]]*$/) i++

      for (j = i; j <= NR; j++) {
        s = lines[j]
        gsub(/^[[:space:]]+|[[:space:]]+$/, "", s)

        if ((j - i) < 120) {
          if (s == "require_once __DIR__ . '\''/../../_init.php'\'';") continue
          if (s == "require_once __DIR__ . \"/../../_init.php\";") continue
          if (s == "mk_require_staff_login();") continue
          if (s == "if (function_exists('\''require_staff_login'\'')) { require_staff_login(); }") continue
          if (s == "if (function_exists(\"require_staff_login\")) { require_staff_login(); }") continue
          if (s == "if (function_exists('\''require_staff'\'')) { require_staff(); }") continue
          if (s == "if (function_exists(\"require_staff\")) { require_staff(); }") continue
        }

        print lines[j]
      }
    }
  ' "$f" > "$body"

  cat "$HEADER_FILE" "$body" > "$tmp"
  mv "$tmp" "$f"
  rm -f "$body"

  echo "UPDATED $f"
}

echo "Backup dir: $BACKUP_DIR"

for f in "${FILES[@]}"; do
  if [ -f "$f" ]; then
    normalize_file "$f"
  else
    echo "SKIP missing: $f"
  fi
done

echo
echo "Linting..."
for f in "${FILES[@]}"; do
  if [ -f "$f" ]; then
    php -l "$f"
  fi
done

rm -f "$HEADER_FILE"

echo
echo "Done."
echo "Backups stored in: $BACKUP_DIR"
