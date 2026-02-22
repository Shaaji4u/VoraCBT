# Data Retention Policy

This document defines default retention windows and automated pruning for operational data.

## Default retention windows

- `security_logs`: 180 days
- `integration_logs`: 180 days
- `identity_logs`: 365 days
- `proctoring_logs`: 90 days
- `exam_session_checkpoints`: 30 days (only for completed/submitted/graded/published sessions)
- `exam_snapshots` JSON files: 365 days

## Pruning tool

Script: `scripts/ops/retention-prune.sh`

Modes:

- Dry run: `scripts/ops/retention-prune.sh --dry-run`
- Execute: `scripts/ops/retention-prune.sh`

Required DB environment variables:

- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

Retention knobs:

- `RETENTION_SECURITY_LOG_DAYS`
- `RETENTION_INTEGRATION_LOG_DAYS`
- `RETENTION_IDENTITY_LOG_DAYS`
- `RETENTION_PROCTORING_LOG_DAYS`
- `RETENTION_CHECKPOINT_DAYS`
- `RETENTION_SNAPSHOT_DAYS`

## Compliance notes

- Legal/academic hold requests should suspend prune jobs for affected tenants/exams.
- Align retention with jurisdiction and school policy for minors.
- Keep backup retention and application retention policies coordinated.
