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
