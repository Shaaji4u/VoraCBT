# Log Rotation Policy

## Log files
- `storage/logs/application.log`
- `storage/logs/security.log`
- `storage/logs/integration.log`
- `storage/logs/queue.log`
- `storage/logs/error.log`

## Rotation standard
- Rotate daily or at 100MB (whichever occurs first).
- Keep 14 compressed rotations for application/queue logs.
- Keep 30 compressed rotations for security/integration logs.
- Use copytruncate only if process cannot reopen file descriptors.

## Flood protection
- Enable app-side log level downgrades during peak exams.
- Deduplicate repeated errors (same signature within 60s).
- Cap per-minute identical log events to prevent disk exhaustion.

## VPS implementation
- See `deploy/vps/logrotate/voracbt`.
