# Production Go-Live Checklist

Deployment decision: **P0 completed in code**; proceed to staging signoff and remaining P1 validations.

## P0 (must pass before production)

- [x] Remove placeholder/static route behavior for authenticated web flows and wire controller actions for core web paths.
- [x] Replace static demo content in student/admin runtime pages with server-driven data for core dashboard/runtime surfaces.
- [x] Remove insecure JWT fallback defaults and fail fast when `JWT_SECRET` is missing.
- [x] Enforce baseline CSRF protection for browser session state-changing non-API requests.
- [x] Enforce HTTPS/HSTS at edge and app policy levels (Nginx redirect/HSTS + app-level enforcement toggle).
- [x] Run full CI quality gate (`composer install`, test suites, static checks) in a reproducible runner (GitHub Actions workflow added).

## P1 (strongly recommended before launch)

- [ ] Add explicit route-level RBAC enforcement for all admin/proctor/teacher endpoints (admin API routes now guarded; proctor/teacher routes pending).
- [x] Replace blocking browser dialogs (`alert`/`confirm`) with structured error and confirmation UX in exam runtime client.
- [ ] Add staging load tests for concurrent exam, autosave, submission, and result publication paths.
- [ ] Validate disaster recovery restore drill and RPO/RTO acceptance from the runbook.

## Evidence from current repository review

- Web routes for core auth/student/admin pages are now controller-wired in `routes/web.php`.
- Student and admin dashboards now fetch runtime data from API endpoints.
- `MonitoringController` now rejects auth when `JWT_SECRET` is missing (no default fallback).
- Existing internal audit report still tracks unresolved high-risk controls.
- Admin API routes now apply Auth + Role middleware checks before controller execution.

- Admin dashboard now loads recent events from `/api/admin/logs` at runtime.

- CI quality gate workflow is configured at `.github/workflows/ci.yml`.
