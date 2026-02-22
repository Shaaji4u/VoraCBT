# Cron Configuration Examples

## Shared hosting crontab
```cron
* * * * * php /home/user/cbt-app/current/bin/queue-work-db --max-jobs=100 --max-time=50 --memory=128 --sleep=1 >> /home/user/cbt-app/shared/storage/logs/queue.log 2>&1
*/5 * * * * php /home/user/cbt-app/current/bin/schedule-run >> /home/user/cbt-app/shared/storage/logs/scheduler.log 2>&1
0 2 * * * /home/user/cbt-app/current/scripts/ops/backup.sh >> /home/user/cbt-app/shared/storage/logs/backup.log 2>&1
```

## VPS crontab
```cron
*/5 * * * * php /var/www/voracbt/current/bin/schedule-run >> /var/www/voracbt/shared/storage/logs/scheduler.log 2>&1
0 2 * * * /var/www/voracbt/current/scripts/ops/backup.sh >> /var/www/voracbt/shared/storage/logs/backup.log 2>&1
0 3 * * 0 /var/www/voracbt/current/scripts/ops/backup.sh --full >> /var/www/voracbt/shared/storage/logs/backup.log 2>&1
```

## Cron hygiene
- Use absolute paths.
- Redirect stdout/stderr to log files.
- Keep queue task single-instance on shared hosting (lock file recommended).
