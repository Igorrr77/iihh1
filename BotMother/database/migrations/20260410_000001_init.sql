CREATE TABLE IF NOT EXISTS inbound_updates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bot_id BIGINT UNSIGNED NOT NULL,
  telegram_update_id BIGINT NOT NULL,
  update_type VARCHAR(64) NOT NULL,
  payload_json JSON NOT NULL,
  payload_hash CHAR(64) NOT NULL,
  received_at DATETIME NOT NULL,
  processed_at DATETIME NULL,
  status ENUM('received','processed','ignored','failed') NOT NULL DEFAULT 'received',
  process_result_json JSON NULL,
  UNIQUE KEY uq_bot_update (bot_id, telegram_update_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS locks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lock_key VARCHAR(190) NOT NULL,
  owner_token VARCHAR(190) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_lock_key (lock_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS job_queue (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  account_id BIGINT UNSIGNED NULL,
  project_id BIGINT UNSIGNED NULL,
  queue_name VARCHAR(64) NOT NULL,
  job_type VARCHAR(64) NOT NULL,
  payload_json JSON NOT NULL,
  unique_key VARCHAR(190) NULL,
  status ENUM('pending','reserved','running','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  attempts INT NOT NULL DEFAULT 0,
  max_attempts INT NOT NULL DEFAULT 5,
  available_at DATETIME NOT NULL,
  reserved_at DATETIME NULL,
  completed_at DATETIME NULL,
  failed_at DATETIME NULL,
  last_error TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_job_unique_key (unique_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS failed_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_queue_id BIGINT UNSIGNED NOT NULL,
  job_type VARCHAR(64) NOT NULL,
  payload_json JSON NOT NULL,
  attempts INT NOT NULL,
  error_code VARCHAR(64) NULL,
  error_message TEXT NOT NULL,
  stack_trace TEXT NULL,
  failed_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
