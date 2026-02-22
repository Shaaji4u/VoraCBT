# Offline / Network Failure Handling

This runbook defines behavior when candidate connectivity is unstable during live exams.

## Implemented controls

1. **Server-backed autosave checkpoints**
   - Endpoint: `POST /api/exam/autosave`
   - Requires `session_id` + active `token`
   - Stores last question marker + draft payload + checksum + client/server timestamps in `exam_session_checkpoints`.

2. **Resume state recovery**
   - Endpoint: `GET /api/exam/resume-state?session_id=...&token=...`
   - Returns:
     - session status
     - remaining server-authoritative time
     - latest checkpoint payload

3. **Integrity validation**
   - Both endpoints validate latest active session token before accepting reads/writes.

## Failure semantics

- If timer has expired, autosave is rejected (`Session expired`).
- If token is invalidated (device/tab conflict), autosave/resume is denied.
- Resume always uses server-side remaining time, not client-side timer.

## Client guidance

- Autosave every 10-20 seconds and on visibility/network changes.
- On reconnect, call `resume-state` first, then patch local draft from checkpoint if checksums differ.
