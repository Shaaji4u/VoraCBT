# Environment Configuration Strategy

## Environment files
- `.env.local` for developer machines.
- `.env.staging` for pre-production validation.
- `.env.production` for production defaults.

Only templates are committed (no secrets).

## Secret management
- Never commit credentials, JWT secrets, OAuth secrets, or API tokens.
- Inject secrets through deployment pipeline or host secret manager.
- Restrict read access to `.env` to app user only.

## Rotation policy
- JWT secret: rotate every 90 days, dual-key grace period for active sessions.
- DB credentials: rotate every 180 days or immediately after suspicion.
- SMS OAuth client secret: rotate every 90 days and after provider incident.


## Runtime security toggles
- `ENFORCE_HTTPS=true` enables HTTPS-only redirect (HTTP -> HTTPS, 308) and sends HSTS (`Strict-Transport-Security`).
- `JWT_SECRET` is required in production boot path and must be injected via secret manager/pipeline.
- Browser web responses expose `X-CSRF-Token` generated from server session; state-changing non-API requests must include this token via `X-CSRF-Token` header or `_csrf` form field.
