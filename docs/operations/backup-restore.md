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
