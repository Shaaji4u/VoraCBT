#!/usr/bin/env bash
set -euo pipefail

MODE="daily"
if [[ "${1:-}" == "--full" ]]; then
  MODE="weekly"
fi

APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
BACKUP_ROOT="${BACKUP_ROOT:-$APP_ROOT/storage/backups}"
STAMP="$(date +%F_%H%M%S)"
TARGET_DIR="$BACKUP_ROOT/$MODE/$STAMP"
mkdir -p "$TARGET_DIR"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_DATABASE:-}"
DB_USER="${DB_USERNAME:-}"
DB_PASS="${DB_PASSWORD:-}"

if [[ -z "$DB_NAME" || -z "$DB_USER" ]]; then
  echo "DB_DATABASE and DB_USERNAME must be set" >&2
  exit 1
fi

MYSQL_PWD="$DB_PASS" mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" | gzip > "$TARGET_DIR/db.sql.gz"

if [[ "$MODE" == "weekly" ]]; then
  tar -czf "$TARGET_DIR/uploads.tar.gz" -C "$APP_ROOT/storage" uploads
  [[ -f "$APP_ROOT/storage/logs/integration.log" ]] && cp "$APP_ROOT/storage/logs/integration.log" "$TARGET_DIR/integration.log"
fi

find "$BACKUP_ROOT/daily" -mindepth 1 -maxdepth 1 -type d -mtime +14 -exec rm -rf {} + 2>/dev/null || true
find "$BACKUP_ROOT/weekly" -mindepth 1 -maxdepth 1 -type d -mtime +56 -exec rm -rf {} + 2>/dev/null || true

echo "Backup completed: $TARGET_DIR"
