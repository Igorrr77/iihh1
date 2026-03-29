ALTER TABLE channel_queue
    ADD COLUMN last_error_code VARCHAR(64) NULL AFTER attempts,
    ADD COLUMN last_error_reason VARCHAR(255) NULL AFTER last_error_code,
    ADD INDEX idx_channel_queue_dlq (status, channel, updated_at);

ALTER TABLE crm_dispatches
    MODIFY status ENUM('pending','sent','failed','dlq') NOT NULL DEFAULT 'pending',
    ADD COLUMN next_retry_at DATETIME NULL AFTER attempts,
    ADD COLUMN last_error_code VARCHAR(64) NULL AFTER last_error,
    ADD INDEX idx_crm_dispatch_retry (status, provider, next_retry_at);
