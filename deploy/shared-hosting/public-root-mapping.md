# Shared Hosting Public Root Mapping

1. Upload code to `/home/<user>/cbt-app/current`.
2. In hosting panel, set document root to `/home/<user>/cbt-app/current/public`.
3. Confirm `index.php` resolves and `.env` is outside public web path.
4. Ensure `storage/*` writable directories are outside direct web exposure.
