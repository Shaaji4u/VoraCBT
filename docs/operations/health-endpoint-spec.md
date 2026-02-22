# Health & Status Endpoint Specification

## Endpoint
- `GET /health`
- Response type: `application/json`
- No auth for basic uptime checks; return only non-sensitive status.

## Response contract
```json
{
  "status": "ok|degraded|down",
  "timestamp": "2026-01-01T12:00:00Z",
  "checks": {
    "db": {"status": "ok", "latency_ms": 12},
    "cache": {"status": "ok", "driver": "redis|file|none"},
    "queue": {"status": "ok", "backlog": 3, "failed_jobs": 0},
    "storage": {"status": "ok", "writable": true}
  }
}
```

## Check behavior
- DB: simple read (`SELECT 1`) timeout 1s.
- Cache: ping for redis/file check.
- Queue: pending + failed job counts (or `unsupported` when no queue table available).
- Storage: write-test in `storage/cache` using temp file.

## HTTP status mapping
- `200` when all critical checks are `ok`.
- `503` when DB is down or storage is not writable.
- `206` when degraded but serving (e.g., cache unavailable).
