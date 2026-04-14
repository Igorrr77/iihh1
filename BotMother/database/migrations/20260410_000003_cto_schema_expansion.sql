ALTER TABLE projects
  ADD COLUMN description TEXT NULL,
  ADD COLUMN status ENUM('active','paused','archived') NOT NULL DEFAULT 'active',
  ADD COLUMN settings_json JSON NULL,
  ADD COLUMN created_by BIGINT UNSIGNED NULL;

ALTER TABLE bots
  ADD COLUMN telegram_bot_id BIGINT NULL,
  ADD COLUMN telegram_username VARCHAR(190) NULL,
  ADD COLUMN webhook_url VARCHAR(255) NULL,
  ADD COLUMN webhook_status ENUM('not_set','set','error') NOT NULL DEFAULT 'not_set',
  ADD COLUMN start_command_enabled TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN default_parse_mode ENUM('none','html','markdown') NOT NULL DEFAULT 'html',
  ADD COLUMN bot_settings_json JSON NULL,
  ADD COLUMN last_webhook_at DATETIME NULL,
  ADD COLUMN last_error_at DATETIME NULL,
  ADD COLUMN created_by BIGINT UNSIGNED NULL;

ALTER TABLE processes
  ADD COLUMN description TEXT NULL,
  ADD COLUMN folder VARCHAR(190) NULL,
  ADD COLUMN category VARCHAR(190) NULL,
  ADD COLUMN start_mode ENUM('manual','triggered','scheduled','hybrid') NOT NULL DEFAULT 'triggered',
  ADD COLUMN is_template_based TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN created_by BIGINT UNSIGNED NULL;

ALTER TABLE process_versions
  ADD COLUMN validation_errors_json JSON NULL,
  ADD COLUMN editor_meta_json JSON NULL,
  ADD COLUMN published_at DATETIME NULL,
  ADD COLUMN created_by BIGINT UNSIGNED NULL;

CREATE TABLE IF NOT EXISTS account_users (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, account_id BIGINT UNSIGNED, user_id BIGINT UNSIGNED, role_code VARCHAR(64), permissions_json JSON NULL, status ENUM('active','disabled') DEFAULT 'active', created_at DATETIME, updated_at DATETIME, UNIQUE KEY uq_account_user(account_id,user_id));
CREATE TABLE IF NOT EXISTS roles (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, code VARCHAR(64) UNIQUE, name VARCHAR(128), is_system TINYINT(1), created_at DATETIME, updated_at DATETIME);
CREATE TABLE IF NOT EXISTS permissions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, code VARCHAR(128) UNIQUE, name VARCHAR(190), group_name VARCHAR(128), created_at DATETIME, updated_at DATETIME);
CREATE TABLE IF NOT EXISTS role_permissions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, role_id BIGINT UNSIGNED, permission_id BIGINT UNSIGNED, UNIQUE KEY uq_role_permission(role_id,permission_id));
CREATE TABLE IF NOT EXISTS user_sessions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED, session_token_hash VARCHAR(255), ip_address VARCHAR(64), user_agent VARCHAR(255), last_seen_at DATETIME, expires_at DATETIME, created_at DATETIME);
CREATE TABLE IF NOT EXISTS password_resets (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED, token_hash VARCHAR(255), expires_at DATETIME, used_at DATETIME NULL, created_at DATETIME);
CREATE TABLE IF NOT EXISTS bot_commands (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, bot_id BIGINT UNSIGNED, command VARCHAR(64), description VARCHAR(255), language_code VARCHAR(16) NULL, created_at DATETIME, updated_at DATETIME, UNIQUE KEY uq_bot_command(bot_id,command,language_code));
CREATE TABLE IF NOT EXISTS bot_webhooks (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, bot_id BIGINT UNSIGNED, url VARCHAR(255), secret_token VARCHAR(128), status ENUM('active','failed','disabled'), last_result_code INT NULL, last_result_body TEXT NULL, installed_at DATETIME NULL, last_checked_at DATETIME NULL, created_at DATETIME, updated_at DATETIME);
CREATE TABLE IF NOT EXISTS process_validation_errors (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, process_version_id BIGINT UNSIGNED, node_uuid CHAR(36) NULL, edge_uuid CHAR(36) NULL, severity ENUM('error','warning'), code VARCHAR(64), message TEXT, meta_json JSON NULL, created_at DATETIME);
CREATE TABLE IF NOT EXISTS contact_fields (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, account_id BIGINT UNSIGNED, project_id BIGINT UNSIGNED, code VARCHAR(128), name VARCHAR(190), field_type ENUM('text','textarea','number','boolean','date','datetime','json','enum'), settings_json JSON NULL, is_system TINYINT(1) DEFAULT 0, created_at DATETIME, updated_at DATETIME, UNIQUE KEY uq_contact_field(project_id,code));
CREATE TABLE IF NOT EXISTS contact_custom_values (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, contact_id BIGINT UNSIGNED, field_id BIGINT UNSIGNED, value_text TEXT NULL, value_number DECIMAL(18,4) NULL, value_bool TINYINT(1) NULL, value_date DATE NULL, value_datetime DATETIME NULL, value_json JSON NULL, updated_at DATETIME, UNIQUE KEY uq_contact_custom(contact_id,field_id));
CREATE TABLE IF NOT EXISTS tags (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, account_id BIGINT UNSIGNED, project_id BIGINT UNSIGNED, code VARCHAR(128), name VARCHAR(190), color VARCHAR(32) NULL, created_at DATETIME, updated_at DATETIME, UNIQUE KEY uq_tags(project_id,code));
CREATE TABLE IF NOT EXISTS contact_tags (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, contact_id BIGINT UNSIGNED, tag_id BIGINT UNSIGNED, created_at DATETIME, created_by BIGINT UNSIGNED NULL, UNIQUE KEY uq_contact_tag(contact_id,tag_id));
CREATE TABLE IF NOT EXISTS outbound_messages (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, bot_id BIGINT UNSIGNED, contact_id BIGINT UNSIGNED, chat_id BIGINT UNSIGNED, execution_id BIGINT UNSIGNED NULL, node_uuid CHAR(36) NULL, telegram_message_id BIGINT NULL, message_type VARCHAR(64), request_json JSON, response_json JSON NULL, send_status ENUM('pending','sent','failed'), error_code VARCHAR(64) NULL, error_message TEXT NULL, sent_at DATETIME NULL, created_at DATETIME, updated_at DATETIME);
CREATE TABLE IF NOT EXISTS scheduled_jobs (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, account_id BIGINT UNSIGNED, project_id BIGINT UNSIGNED, bot_id BIGINT UNSIGNED NULL, contact_id BIGINT UNSIGNED NULL, execution_id BIGINT UNSIGNED NULL, job_type VARCHAR(64), payload_json JSON, run_at DATETIME, status ENUM('scheduled','queued','completed','cancelled','failed') DEFAULT 'scheduled', created_at DATETIME, updated_at DATETIME);
CREATE TABLE IF NOT EXISTS broadcasts (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, account_id BIGINT UNSIGNED, project_id BIGINT UNSIGNED, bot_id BIGINT UNSIGNED, name VARCHAR(190), segment_json JSON, message_payload_json JSON, status ENUM('draft','scheduled','running','paused','completed','cancelled','failed') DEFAULT 'draft', scheduled_at DATETIME NULL, started_at DATETIME NULL, finished_at DATETIME NULL, created_by BIGINT UNSIGNED, created_at DATETIME, updated_at DATETIME);
CREATE TABLE IF NOT EXISTS broadcast_recipients (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, broadcast_id BIGINT UNSIGNED, contact_id BIGINT UNSIGNED, status ENUM('pending','sent','failed','skipped') DEFAULT 'pending', error_message TEXT NULL, sent_at DATETIME NULL, created_at DATETIME, updated_at DATETIME, UNIQUE KEY uq_broadcast_recipient(broadcast_id,contact_id));
CREATE TABLE IF NOT EXISTS process_template_library (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, account_id BIGINT UNSIGNED, project_id BIGINT UNSIGNED NULL, name VARCHAR(190), slug VARCHAR(190), description TEXT NULL, category_id BIGINT UNSIGNED NULL, graph_json JSON, compiled_graph_json JSON NULL, meta_json JSON NULL, status ENUM('draft','published','archived') DEFAULT 'draft', version_number INT DEFAULT 1, created_by BIGINT UNSIGNED, created_at DATETIME, updated_at DATETIME);
CREATE TABLE IF NOT EXISTS template_usages (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, account_id BIGINT UNSIGNED, project_id BIGINT UNSIGNED, entity_type ENUM('message_template','reusable_block','process_template'), entity_id BIGINT UNSIGNED, entity_version_id BIGINT UNSIGNED NULL, used_in_type ENUM('process','message_node','marketplace_item'), used_in_id BIGINT UNSIGNED, insertion_mode ENUM('linked','copied'), created_at DATETIME);
CREATE TABLE IF NOT EXISTS funnel_step_events (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, funnel_id BIGINT UNSIGNED, step_id BIGINT UNSIGNED, contact_id BIGINT UNSIGNED, event_type VARCHAR(64), event_payload_json JSON NULL, created_at DATETIME);
CREATE TABLE IF NOT EXISTS daily_stats (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, account_id BIGINT UNSIGNED, project_id BIGINT UNSIGNED NULL, bot_id BIGINT UNSIGNED NULL, stat_date DATE, metric_code VARCHAR(128), metric_value DECIMAL(18,4), meta_json JSON NULL, created_at DATETIME, UNIQUE KEY uq_daily_stats(account_id,project_id,bot_id,stat_date,metric_code));
CREATE TABLE IF NOT EXISTS deal_notes (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, deal_id BIGINT UNSIGNED, author_user_id BIGINT UNSIGNED, note_text TEXT, created_at DATETIME, updated_at DATETIME);
CREATE TABLE IF NOT EXISTS deal_tasks (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, deal_id BIGINT UNSIGNED, assigned_user_id BIGINT UNSIGNED NULL, title VARCHAR(190), description TEXT NULL, due_at DATETIME NULL, status ENUM('open','completed','cancelled') DEFAULT 'open', completed_at DATETIME NULL, created_by BIGINT UNSIGNED, created_at DATETIME, updated_at DATETIME);
CREATE TABLE IF NOT EXISTS deal_activities (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, deal_id BIGINT UNSIGNED, activity_type VARCHAR(64), payload_json JSON NULL, created_by BIGINT UNSIGNED NULL, created_at DATETIME);
CREATE TABLE IF NOT EXISTS deal_status_history (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, deal_id BIGINT UNSIGNED, from_stage_id BIGINT UNSIGNED NULL, to_stage_id BIGINT UNSIGNED NULL, old_status VARCHAR(64) NULL, new_status VARCHAR(64) NULL, comment TEXT NULL, changed_by BIGINT UNSIGNED NULL, created_at DATETIME);
CREATE TABLE IF NOT EXISTS marketplace_item_versions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, marketplace_item_id BIGINT UNSIGNED, version_number INT, package_json JSON, compatibility_json JSON NULL, changelog TEXT NULL, status ENUM('draft','published','archived') DEFAULT 'draft', created_at DATETIME, updated_at DATETIME);
CREATE TABLE IF NOT EXISTS marketplace_installs (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, marketplace_item_id BIGINT UNSIGNED, marketplace_item_version_id BIGINT UNSIGNED, account_id BIGINT UNSIGNED, project_id BIGINT UNSIGNED NULL, installed_entity_type VARCHAR(64), installed_entity_id BIGINT UNSIGNED, installed_at DATETIME, created_at DATETIME);
CREATE TABLE IF NOT EXISTS marketplace_categories (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, code VARCHAR(128), name VARCHAR(190), item_type VARCHAR(64) NULL, sort_order INT DEFAULT 0, created_at DATETIME, updated_at DATETIME);
CREATE TABLE IF NOT EXISTS api_tokens (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, account_id BIGINT UNSIGNED, project_id BIGINT UNSIGNED NULL, name VARCHAR(190), token_hash VARCHAR(255), scopes_json JSON, expires_at DATETIME NULL, last_used_at DATETIME NULL, created_by BIGINT UNSIGNED, created_at DATETIME, updated_at DATETIME);
CREATE TABLE IF NOT EXISTS webhook_logs (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, bot_id BIGINT UNSIGNED, endpoint_type ENUM('telegram_in','external_in','external_out'), request_headers_json JSON NULL, request_body_json JSON NULL, response_code INT NULL, response_body TEXT NULL, status ENUM('ok','error'), created_at DATETIME);
CREATE TABLE IF NOT EXISTS http_request_logs (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, account_id BIGINT UNSIGNED, project_id BIGINT UNSIGNED, execution_id BIGINT UNSIGNED NULL, node_uuid CHAR(36) NULL, method VARCHAR(16), url VARCHAR(500), request_headers_json JSON NULL, request_body_json JSON NULL, response_code INT NULL, response_headers_json JSON NULL, response_body TEXT NULL, duration_ms INT NULL, status ENUM('success','error','timeout'), created_at DATETIME);
CREATE TABLE IF NOT EXISTS uploaded_files (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, account_id BIGINT UNSIGNED, project_id BIGINT UNSIGNED NULL, uploader_user_id BIGINT UNSIGNED NULL, original_name VARCHAR(255), stored_name VARCHAR(255), mime_type VARCHAR(128), size_bytes BIGINT, storage_path VARCHAR(255), sha256 CHAR(64), created_at DATETIME);
CREATE TABLE IF NOT EXISTS media_library (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, account_id BIGINT UNSIGNED, project_id BIGINT UNSIGNED, title VARCHAR(190), type VARCHAR(64), file_id BIGINT UNSIGNED, tags_json JSON NULL, meta_json JSON NULL, created_at DATETIME, updated_at DATETIME);
