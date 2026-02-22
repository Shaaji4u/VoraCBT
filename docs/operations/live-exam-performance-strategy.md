# Live Exam Performance Strategy

This document defines baseline controls for concurrent exam traffic (e.g., 500+ students).

## Controls implemented

1. **Global API rate limiting**
   - Applied at HTTP entrypoint for `/api/*` requests.
   - Configurable via:
     - `API_RATE_LIMIT` (default: `120` requests)
     - `API_RATE_LIMIT_WINDOW` (default: `60` seconds)
   - Uses file cache backend by default (`storage/cache`).

2. **Monitoring-oriented index coverage**
   - Indexes for high-volume log filtering on security/proctoring/integration paths.

3. **Async grading path protection**
   - Existing queue-backed grading components continue to prevent blocking request flows.

## Tuning guidance

- For exam windows, set stricter limits on public endpoints exposed to brute-force behavior.
- If using Redis in production, migrate cache driver for rate-limit storage to reduce file IO contention.
- Keep DB connection pool and queue workers sized based on expected candidate concurrency.

## Operational checklist before live exam

- Confirm `/health` reports DB/cache/storage as healthy.
- Confirm queue backlog is near zero.
- Confirm `API_RATE_LIMIT*` values are set explicitly in environment.
- Dry-run with staged load test traffic before production exam windows.
