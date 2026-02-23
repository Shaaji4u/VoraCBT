# Staging Load Test Plan (P1)

## Goal
Validate concurrent exam readiness for autosave, proctoring heartbeat/events, and resume-state recovery under production-like traffic.

## Tooling
- `k6` script: `scripts/perf/exam-runtime-load.js`
- Environment variables:
  - `BASE_URL`
  - `AUTH_TOKEN`
  - `EXAM_SESSION_ID`
  - `EXAM_SESSION_TOKEN`
  - Optional VU overrides (`AUTOSAVE_VUS`, `PROCTORING_VUS`, `RESUME_VUS`)

## Execution
```bash
k6 run scripts/perf/exam-runtime-load.js
```

## Pass criteria
- `http_req_failed < 1%`
- `p95(http_req_duration) < 1000ms`
- No sustained queue growth during test window.
- No critical errors in admin logs for exam/proctoring paths.

## Evidence to capture
- k6 summary output + JSON export.
- DB/queue backlog snapshots before and after test.
- `/health` output before and after test.
- Incident notes for any threshold breach and remediation action.
