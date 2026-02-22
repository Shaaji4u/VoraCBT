# Shared Hosting Deployment Guide

## 1) Compatibility profile
- Runtime: PHP-FPM/CGI with no persistent daemon access.
- Queue backend: database-only (no Redis required).
- Worker model: cron-invoked short-lived queue worker.
- Public mapping: web root must point to `/public`.

## 2) Required layout
- Application root: `/home/<user>/cbt-app`
- Document root: `/home/<user>/cbt-app/public`
- Writable paths:
  - `storage/logs`
  - `storage/cache`
  - `storage/queue`
  - `storage/uploads`

## 3) Permission model
- Directories: `755`
- Writable storage directories: `775` (when host user/group split requires it)
- Files: `644`
- Never use `777` in production.

## 4) Deployment steps
1. Upload release to `/home/<user>/cbt-app/releases/<timestamp>`.
2. Copy `.env.production` to release `.env` and inject secrets from host secret manager.
3. Install dependencies (`composer install --no-dev --optimize-autoloader`).
4. Link persistent folders from shared path to current release:
   - `storage/logs`, `storage/cache`, `storage/queue`, `storage/uploads`.
5. Point `current` symlink to new release.
6. Ensure hosting control panel document root targets `.../current/public`.
7. Run migration in low-traffic window (see zero-downtime migration runbook).
8. Warm caches (config/routes/views cache if available in framework command set).
9. Verify `/health` endpoint and login page.

## 5) Queue handling on shared hosting
- Do not run long-lived workers.
- Run short queue jobs from cron each minute with memory and job caps.
- Prefer DB queue and periodic retries.

## 6) Upgrade threshold (shared hosting → VPS)
Upgrade to VPS when any of these persist:
- Peak exam concurrency causes queue delay > 60 seconds.
- Failed jobs exceed 1% over 24h.
- CPU throttling during exam windows.
- Backup job runtime exceeds cron window.
