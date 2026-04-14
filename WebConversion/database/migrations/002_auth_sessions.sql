CREATE TABLE IF NOT EXISTS auth_refresh_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    refresh_token VARCHAR(128) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_refresh_user_active (user_id, revoked_at, expires_at),
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE
);

ALTER TABLE api_tokens
    ADD COLUMN IF NOT EXISTS revoked_at DATETIME NULL,
    ADD COLUMN IF NOT EXISTS refreshed_from_token_id BIGINT UNSIGNED NULL,
    ADD INDEX IF NOT EXISTS idx_api_tokens_active (user_id, revoked_at, expires_at);
