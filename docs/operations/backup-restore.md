# Backup & Restore Procedures

## Backup policy
- Daily: database dump (encrypted, compressed).
- Weekly: full backup (database + storage/uploads + integration logs).
- Retention:
  - Daily backups: 14 days
  - Weekly backups: 8 weeks
- Optional VPS offsite: S3-compatible object storage replication.

## Scripts
- `scripts/ops/backup.sh`
- `scripts/ops/restore.sh`

## Restore runbook
1. Announce incident and freeze writes (maintenance mode if available).
2. Identify target restore point (timestamp + checksum validated).
3. Restore database from SQL dump.
4. Restore `storage/uploads` and `storage/logs/integration.log` archive.
5. Run integrity checks (row counts, checksum tables, sample login, `/health`).
6. Resume queue workers.
7. Disable maintenance mode.
8. Publish incident postmortem and update RPO/RTO metrics.

## Shared hosting method
- Use cron + `mysqldump` to local backup folder, then copy to secondary storage endpoint available from host.

## VPS method
- Automated backup script + optional offsite sync (`aws s3 sync` or compatible).

## Pre-open exam snapshot backups
- On lifecycle transition to `open`, the platform now writes an immutable JSON snapshot of:
  - `exam_templates` row
  - all `exam_sections`
  - mapped `exam_questions` and question payloads
- Snapshots are stored under `storage/app/exam_snapshots/` and indexed in `exam_snapshot_backups` with SHA-256 hash.
- If snapshot creation fails, transition to `open` must be rejected.
