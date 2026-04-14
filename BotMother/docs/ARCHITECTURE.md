# ARCHITECTURE

BotMother uses modular monolith architecture with strict layers:

- Core: bootstrapping, request/response, DI, DB, session, CSRF.
- Controllers: HTTP transport and endpoint orchestration.
- Services: business workflows (auth, bots, runtime, processes).
- Repositories: PDO access with prepared statements.
- Graph: validation and compilation of process JSON into runtime model.
- Runtime: webhook-triggered execution and wait-state resume.

Execution safety:
- inbound update dedup via unique `(bot_id, telegram_update_id)`.
- per-contact lock in `locks`.
- all critical paths log into `storage/logs/app.log`.
