# VPS Deployment Guide (Ubuntu + Nginx + PHP-FPM)

## 1) Baseline stack
- Ubuntu 22.04+
- Nginx
- PHP-FPM 8.2+
- MySQL/MariaDB
- Redis (queue/cache)
- Supervisor (queue workers)
- Cron (scheduler/maintenance)

## 2) Directory strategy
- App root: `/var/www/voracbt`
- Releases: `/var/www/voracbt/releases/<timestamp>`
- Symlink: `/var/www/voracbt/current`
- Shared writable: `/var/www/voracbt/shared/storage/{logs,cache,queue,uploads}`

## 3) Server hardening baseline
- Disable directory listing in Nginx (`autoindex off`).
- Block access to dotfiles and `.env`.
- Enforce HTTPS and redirect HTTP→HTTPS.
- Enable HSTS (`max-age=31536000; includeSubDomains`).
- Disable `display_errors` in production.
- Rate-limit critical API paths.
- Install fail2ban/ufw and restrict SSH by IP.

## 4) Deployment workflow (release-based)
1. Build artifact in CI.
2. Upload and extract release.
3. Install prod dependencies.
4. Copy `.env.production` template and inject runtime secrets.
5. Re-link shared storage folders into release.
6. Switch `current` symlink atomically.
7. Run zero-downtime migration flow.
8. Reload PHP-FPM and Nginx if needed.
9. Restart supervisor workers gracefully.
10. Validate `/health` and key synthetic exam flow.

## 5) Nginx and Supervisor samples
- See `deploy/vps/nginx/voracbt.conf`.
- See `deploy/vps/supervisor/queue-worker.conf`.
