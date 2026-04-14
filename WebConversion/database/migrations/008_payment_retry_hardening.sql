ALTER TABLE payments
    ADD COLUMN retry_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER status,
    ADD COLUMN last_error_code VARCHAR(64) NULL AFTER retry_count,
    ADD COLUMN next_retry_at DATETIME NULL AFTER last_error_code,
    ADD INDEX idx_payments_retry_queue (status, retry_count, next_retry_at);
