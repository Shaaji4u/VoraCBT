# Queue Worker Configuration

## Shared hosting profile
- Driver: `database`
- Execution model: cron every minute
- Command template:
  - `php /home/<user>/cbt-app/current/bin/queue-work-db --max-jobs=100 --max-time=50 --memory=128 --sleep=1`
- Key controls:
  - `--max-jobs` prevents memory drift.
  - `--max-time` ensures process exits before next cron overlap.
  - Keep runtime below 55s.

## VPS profile
- Driver: `redis`
- Execution model: persistent workers under Supervisor
- Worker pools:
  - `queue-default`: general background jobs
  - `queue-exam-critical`: proctoring/integration callbacks
- Start with one worker per CPU core for `default`, two for `exam-critical`, then tune by queue lag.

## Failure policy
- Retry with bounded backoff.
- Move poison jobs to failed_jobs after max attempts.
- Alert when failed jobs > 20 in 5 minutes.

## Scaling policy
- If backlog age > 30s during active exams, scale workers up.
- If CPU sustained > 80%, scale vertically before adding extra worker processes.
