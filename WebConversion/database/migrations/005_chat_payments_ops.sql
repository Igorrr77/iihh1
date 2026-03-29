CREATE TABLE IF NOT EXISTS chat_mutes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webinar_id BIGINT UNSIGNED NOT NULL,
    lead_token VARCHAR(64) NOT NULL,
    muted_until DATETIME NOT NULL,
    reason VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_chat_mute (webinar_id, lead_token),
    FOREIGN KEY (webinar_id) REFERENCES webinars(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS chat_delivery_metrics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webinar_id BIGINT UNSIGNED NOT NULL,
    transport VARCHAR(32) NOT NULL,
    latency_ms INT UNSIGNED NOT NULL,
    is_error TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_chat_metrics_webinar_created (webinar_id, created_at),
    FOREIGN KEY (webinar_id) REFERENCES webinars(id) ON DELETE CASCADE
);
