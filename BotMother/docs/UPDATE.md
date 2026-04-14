# UPDATE

`/update/index.php` executes pending SQL migrations via `database/migrate.php`, then appends operation record into `storage/logs/update.log`.

Recommended flow:
1. Backup database.
2. Open `/update/index.php`.
3. Re-run `cron/compile_graphs.php`.
4. Re-run `cron/stats_aggregate.php`.
