#!/usr/bin/env bash
set -euo pipefail

APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_DATABASE:-}"
DB_USER="${DB_USERNAME:-}"
DB_PASS="${DB_PASSWORD:-}"

if [[ -z "$DB_NAME" || -z "$DB_USER" ]]; then
  echo "DB_DATABASE and DB_USERNAME must be set" >&2
  exit 1
fi

RETENTION_SECURITY_LOG_DAYS="${RETENTION_SECURITY_LOG_DAYS:-180}"
RETENTION_INTEGRATION_LOG_DAYS="${RETENTION_INTEGRATION_LOG_DAYS:-180}"
RETENTION_IDENTITY_LOG_DAYS="${RETENTION_IDENTITY_LOG_DAYS:-365}"
RETENTION_PROCTORING_LOG_DAYS="${RETENTION_PROCTORING_LOG_DAYS:-90}"
RETENTION_CHECKPOINT_DAYS="${RETENTION_CHECKPOINT_DAYS:-30}"
RETENTION_SNAPSHOT_DAYS="${RETENTION_SNAPSHOT_DAYS:-365}"

DRY_RUN=false
if [[ "${1:-}" == "--dry-run" ]]; then
  DRY_RUN=true
fi

run_count() {
  local sql="$1"
  MYSQL_PWD="$DB_PASS" mysql -N -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" -e "$sql"
}

run_exec() {
  local sql="$1"
  MYSQL_PWD="$DB_PASS" mysql -N -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" -e "$sql"
}

echo "[retention] dry_run=$DRY_RUN"

# SQL pruning
read -r -d '' SQL_SECURITY <<EOF || true
DELETE FROM security_logs
WHERE created_at < (NOW() - INTERVAL ${RETENTION_SECURITY_LOG_DAYS} DAY);
EOF

read -r -d '' SQL_INTEGRATION <<EOF || true
DELETE FROM integration_logs
WHERE created_at < (NOW() - INTERVAL ${RETENTION_INTEGRATION_LOG_DAYS} DAY);
EOF

read -r -d '' SQL_IDENTITY <<EOF || true
DELETE FROM identity_logs
WHERE timestamp < (NOW() - INTERVAL ${RETENTION_IDENTITY_LOG_DAYS} DAY);
EOF

read -r -d '' SQL_PROCTOR <<EOF || true
DELETE FROM proctoring_logs
WHERE created_at < (NOW() - INTERVAL ${RETENTION_PROCTORING_LOG_DAYS} DAY);
EOF

read -r -d '' SQL_CHECKPOINTS <<EOF || true
DELETE c FROM exam_session_checkpoints c
INNER JOIN exam_sessions s ON s.id = c.exam_session_id
WHERE c.server_timestamp < (NOW() - INTERVAL ${RETENTION_CHECKPOINT_DAYS} DAY)
  AND s.status IN ('submitted', 'graded', 'published');
EOF

if $DRY_RUN; then
  echo "security_logs: $(run_count "SELECT COUNT(*) FROM security_logs WHERE created_at < (NOW() - INTERVAL ${RETENTION_SECURITY_LOG_DAYS} DAY)")"
  echo "integration_logs: $(run_count "SELECT COUNT(*) FROM integration_logs WHERE created_at < (NOW() - INTERVAL ${RETENTION_INTEGRATION_LOG_DAYS} DAY)")"
  echo "identity_logs: $(run_count "SELECT COUNT(*) FROM identity_logs WHERE timestamp < (NOW() - INTERVAL ${RETENTION_IDENTITY_LOG_DAYS} DAY)")"
  echo "proctoring_logs: $(run_count "SELECT COUNT(*) FROM proctoring_logs WHERE created_at < (NOW() - INTERVAL ${RETENTION_PROCTORING_LOG_DAYS} DAY)")"
  echo "exam_session_checkpoints: $(run_count "SELECT COUNT(*) FROM exam_session_checkpoints c INNER JOIN exam_sessions s ON s.id = c.exam_session_id WHERE c.server_timestamp < (NOW() - INTERVAL ${RETENTION_CHECKPOINT_DAYS} DAY) AND s.status IN ('submitted','graded','published')")"
else
  run_exec "$SQL_SECURITY"
  run_exec "$SQL_INTEGRATION"
  run_exec "$SQL_IDENTITY"
  run_exec "$SQL_PROCTOR"
  run_exec "$SQL_CHECKPOINTS"
fi

# Filesystem snapshot pruning
SNAPSHOT_DIR="$APP_ROOT/storage/app/exam_snapshots"
if [[ -d "$SNAPSHOT_DIR" ]]; then
  if $DRY_RUN; then
    echo "snapshot_files: $(find "$SNAPSHOT_DIR" -type f -name '*.json' -mtime +"$RETENTION_SNAPSHOT_DAYS" | wc -l | tr -d ' ')"
  else
    find "$SNAPSHOT_DIR" -type f -name '*.json' -mtime +"$RETENTION_SNAPSHOT_DAYS" -delete
  fi
fi

echo "[retention] completed"
