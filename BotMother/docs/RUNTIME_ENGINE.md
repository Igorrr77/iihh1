# RUNTIME_ENGINE

Implemented runtime flow now includes:

1. Webhook update ingestion with dedup in `inbound_updates`.
2. Per-contact lock acquisition using `locks` table.
3. Contact upsert by Telegram user id.
4. Wait-state resume path:
   - finds active `waiting_states`
   - resolves state
   - logs `execution_steps`
   - marks execution completed
5. Command trigger path (`/start`):
   - loads published process with active compiled graph
   - creates execution
   - runs compiled nodes sequentially (foundation supports `send_text`, `wait_input`)
   - writes execution step logs
   - creates wait state for input nodes

Current runtime node coverage: `start`, `send_text`, `wait_input`.
