CREATE TABLE IF NOT EXISTS channel_queue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    channel ENUM('email','sms','voice') NOT NULL,
    lead_token VARCHAR(64) NOT NULL,
    template_key VARCHAR(120) NOT NULL,
    payload_json JSON NOT NULL,
    status ENUM('queued','sent','failed','dlq') NOT NULL DEFAULT 'queued',
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    next_retry_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_channel_queue_status_retry (status, next_retry_at)
);

CREATE TABLE IF NOT EXISTS crm_dispatches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idempotency_key VARCHAR(128) NOT NULL UNIQUE,
    provider VARCHAR(64) NOT NULL,
    lead_token VARCHAR(64) NOT NULL,
    payload_json JSON NOT NULL,
    status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    last_error VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS messenger_cuid_map (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lead_token VARCHAR(64) NOT NULL,
    messenger ENUM('telegram','whatsapp','viber','vk','facebook') NOT NULL,
    cuid VARCHAR(120) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_messenger_cuid (messenger, cuid),
    UNIQUE KEY uniq_lead_messenger (lead_token, messenger)
);

CREATE TABLE IF NOT EXISTS utm_spend (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webinar_id BIGINT UNSIGNED NOT NULL,
    utm_source VARCHAR(120) NOT NULL,
    utm_medium VARCHAR(120) NOT NULL,
    utm_campaign VARCHAR(120) NOT NULL,
    spend_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_utm_spend (webinar_id, utm_source, utm_medium, utm_campaign),
    FOREIGN KEY (webinar_id) REFERENCES webinars(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS insight_monitoring (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webinar_id BIGINT UNSIGNED NOT NULL,
    finished_at DATETIME NOT NULL,
    insight_ready_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (webinar_id) REFERENCES webinars(id) ON DELETE CASCADE
);
