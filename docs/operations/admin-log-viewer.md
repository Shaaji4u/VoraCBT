# Admin Log Viewer

This endpoint provides a central audit stream for operational dispute resolution and monitoring.

## Endpoint

`GET /api/admin/logs`

Authentication: Bearer JWT with `role` of `admin` or `super_admin`.

## Supported sources

- `security` (`security_logs`)
- `identity` (`identity_logs`)
- `proctoring` (`proctoring_logs` + `proctoring_sessions`)
- `integration` (`integration_logs`)
- `result_lock` (`result_lock_audit_logs`)
- `exam_lifecycle` (`exam_lifecycle_logs`)

## Query filters

- `from` (datetime string)
- `to` (datetime string)
- `user_id`
- `exam_template_id`
- `event_source`
- `limit` (1..500, default `100`)

Example:

```http
GET /api/admin/logs?from=2026-01-01%2000:00:00&to=2026-01-31%2023:59:59&exam_template_id=<exam-id>&event_source=proctoring&limit=200
Authorization: Bearer <jwt>
```

## Response shape

```json
{
  "data": [
    {
      "id": "...",
      "event_source": "proctoring",
      "event_name": "tab_switch",
      "user_id": "...",
      "exam_template_id": "...",
      "severity": "warning",
      "summary": "tab_switch",
      "details": {"count": 3},
      "occurred_at": "2026-01-20 11:39:10"
    }
  ],
  "count": 1,
  "filters": {"...": "..."}
}
```

## Operational notes

- This API is intentionally read-only.
- Filters are designed for school incident review workflows: by date, student/staff, and exam.
- Ensure retention policies include all source tables for required legal windows.
