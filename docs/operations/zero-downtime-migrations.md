# Zero-Downtime Migration Procedure

## Goals
- Protect active exam sessions.
- Avoid blocking writes during peak exam windows.

## Procedure
1. Select deploy window outside peak exams.
2. Enable maintenance mode for admin-only actions if migration is risky.
3. Deploy code to new release path.
4. Run backward-compatible migrations first.
5. Warm caches in new release.
6. Switch symlink atomically to new release.
7. Restart queue workers (VPS supervisor `reread/update/restart`; shared hosting waits for next cron cycle).
8. Run smoke checks (`/health`, login, exam session open).
9. Disable maintenance mode.

## Safety rules
- Never drop/rename columns used by running code in same deploy.
- Split destructive DB changes into later maintenance release.
- Keep old code able to run with new schema during cutover.
