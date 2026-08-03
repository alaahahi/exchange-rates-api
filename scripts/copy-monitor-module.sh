#!/usr/bin/env bash
set -euo pipefail

if [ $# -lt 1 ]; then
  echo "Usage: $0 /path/to/target-laravel-project"
  exit 1
fi

TARGET="$1"
SOURCE="$(cd "$(dirname "$0")/.." && pwd)"

copy_path() {
  local from="$SOURCE/$1"
  local to="$TARGET/$1"

  if [ ! -e "$from" ]; then
    echo "Skip missing: $1"
    return
  fi

  mkdir -p "$(dirname "$to")"
  if [ -d "$from" ]; then
    rm -rf "$to"
    cp -R "$from" "$to"
  else
    cp "$from" "$to"
  fi

  echo "Copied $1"
}

copy_path "app/Monitor"
copy_path "config/monitor.php"
copy_path "resources/views/monitor"
copy_path "docs/MONITOR_INSTALL.md"
copy_path "scripts/copy-monitor-module.ps1"
copy_path "scripts/copy-monitor-module.sh"
copy_path "tests/Unit/Monitor"
copy_path "tests/Feature/Monitor"

echo ""
echo "Done. Next steps:"
echo "1) Register App\\Monitor\\Providers\\MonitorServiceProvider"
echo "2) Wire ExceptionMonitor in app/Exceptions/Handler.php"
echo "3) Set MONITOR_PROJECT_NAME in .env"
echo "4) php artisan test tests/Unit/Monitor tests/Feature/Monitor"
