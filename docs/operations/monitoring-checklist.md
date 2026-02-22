# Monitoring Checklist

## Core service metrics
- CPU usage
- Memory usage
- Disk usage and inode usage
- PHP-FPM active/max children
- DB connection saturation

## Application metrics
- Queue backlog size
- Oldest queued job age
- Failed job count
- Integration failure rate (SMS/identity/proctoring callbacks)
- Active exam session count

## Endpoint monitoring
- `/health` every 60s
- Synthetic exam flow probe every 5 min (login → load exam page)

## Shared hosting monitoring
- Health endpoint + error log watch from control panel.
- External uptime checks (UptimeRobot).

## VPS monitoring
- Prometheus-compatible node/app metrics endpoint (optional).
- Alert thresholds:
  - CPU > 85% for 10m
  - Memory > 90% for 5m
  - Disk > 80%
  - Queue lag > 30s during exam window
