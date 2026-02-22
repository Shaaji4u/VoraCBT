# Disaster Recovery Plan

## Targets
- **RTO**: 60 minutes for critical exam platform restore.
- **RPO**: 15 minutes for exam/proctoring events; 24h worst-case for static uploads.

## Incident scenarios

### 1) DB corruption
- Detect via failed integrity checks or app DB errors.
- Action: isolate writes, restore latest clean backup, replay incremental logs if available.
- Validate: exam/session row counts, auth checks, integration queues.

### 2) Storage loss
- Restore `storage/uploads` and critical logs from weekly full backup.
- Rebuild cache/queue storage directories.

### 3) Integration failure (SMS/OAuth/provider outage)
- Switch to degraded mode (queue retries with backoff).
- Alert operations and provider contacts.
- Replay queued events when provider recovers.

### 4) Queue crash
- Shared hosting: cron auto-recovers next minute.
- VPS: supervisor auto-restart; if persistent failure, drain poison messages to failed queue.

### 5) Proctoring log recovery
- Recover from integration log backup and queue retry journal.
- Reconcile events per `exam_session_id` and `event_timestamp`.

## DR test cadence
- Monthly tabletop exercise.
- Quarterly full restore drill in staging.
