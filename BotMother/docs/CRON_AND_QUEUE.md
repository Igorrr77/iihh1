# CRON_AND_QUEUE

Implemented cron foundations:
- `cron/queue_worker.php` — picks pending jobs, marks running/completed/failed and writes failed_jobs.
- `cron/scheduler.php` — moves due scheduled_jobs into job_queue.
- `cron/retry_failed_jobs.php` — requeues failed jobs with attempts < max_attempts.
- `cron/cleanup.php` — clears expired locks and expires waiting states.
- `cron/compile_graphs.php` — validates/compiles process_versions and stores compiled json.
- `cron/stats_aggregate.php` — writes daily aggregated funnel metric.

Queue status lifecycle: `pending -> running -> completed|failed`.
