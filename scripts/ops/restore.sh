#!/usr/bin/env bash
set -euo pipefail

if [[ $# -lt 1 ]]; then
  echo "Usage: $0 <backup_dir>" >&2
  exit 1
fi

BACKUP_DIR="$1"
APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_DATABASE:-}"
DB_USER="${DB_USERNAME:-}"
DB_PASS="${DB_PASSWORD:-}"

if [[ ! -f "$BACKUP_DIR/db.sql.gz" ]]; then
  echo "Missing db.sql.gz in backup directory" >&2
  exit 1
fi

if [[ -z "$DB_NAME" || -z "$DB_USER" ]]; then
  echo "DB_DATABASE and DB_USERNAME must be set" >&2
  exit 1
fi

zcat "$BACKUP_DIR/db.sql.gz" | MYSQL_PWD="$DB_PASS" mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME"

if [[ -f "$BACKUP_DIR/uploads.tar.gz" ]]; then
  tar -xzf "$BACKUP_DIR/uploads.tar.gz" -C "$APP_ROOT/storage"
fi

if [[ -f "$BACKUP_DIR/integration.log" ]]; then
  cp "$BACKUP_DIR/integration.log" "$APP_ROOT/storage/logs/integration.log.restored"
fi

echo "Restore completed from: $BACKUP_DIR"
