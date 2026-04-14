CREATE TABLE webinars (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    external_id VARCHAR(64) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    format ENUM('live', 'on_demand', 'auto') NOT NULL DEFAULT 'live',
    timezone VARCHAR(64) NOT NULL,
    access_mode ENUM('open_link', 'password', 'name', 'name_email', 'name_email_phone') NOT NULL DEFAULT 'name_email_phone',
    status ENUM('draft', 'scheduled', 'running', 'finished') NOT NULL DEFAULT 'draft',
    start_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE webinar_scenarios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webinar_id BIGINT UNSIGNED NOT NULL,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    scenario_json JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (webinar_id) REFERENCES webinars(id) ON DELETE CASCADE
);

CREATE TABLE webinar_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webinar_id BIGINT UNSIGNED NOT NULL,
    second_from_start INT UNSIGNED NOT NULL,
    event_type VARCHAR(64) NOT NULL,
    payload_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_webinar_time (webinar_id, second_from_start),
    FOREIGN KEY (webinar_id) REFERENCES webinars(id) ON DELETE CASCADE
);

CREATE TABLE leads (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webinar_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(40) NULL,
    messenger_cuid VARCHAR(120) NULL,
    utm_source VARCHAR(120) NULL,
    utm_medium VARCHAR(120) NULL,
    utm_campaign VARCHAR(120) NULL,
    joined_at DATETIME NULL,
    left_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_webinar_email (webinar_id, email),
    FOREIGN KEY (webinar_id) REFERENCES webinars(id) ON DELETE CASCADE
);

CREATE TABLE webinar_registrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webinar_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(40) NULL,
    access_token VARCHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (webinar_id) REFERENCES webinars(id) ON DELETE CASCADE
);

CREATE TABLE attendance_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webinar_id BIGINT UNSIGNED NOT NULL,
    access_token VARCHAR(64) NOT NULL,
    event_type ENUM('join', 'leave') NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_attendance_webinar_time (webinar_id, created_at),
    FOREIGN KEY (webinar_id) REFERENCES webinars(id) ON DELETE CASCADE
);

CREATE TABLE data_slices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webinar_id BIGINT UNSIGNED NOT NULL,
    slice_label VARCHAR(120) NOT NULL,
    online_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    INDEX idx_slice_webinar_time (webinar_id, created_at),
    FOREIGN KEY (webinar_id) REFERENCES webinars(id) ON DELETE CASCADE
);

CREATE TABLE rate_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    scope VARCHAR(64) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    minute_key DATETIME NOT NULL,
    hits INT UNSIGNED NOT NULL DEFAULT 1,
    UNIQUE KEY uniq_scope_ip_minute (scope, ip, minute_key)
);

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor VARCHAR(120) NOT NULL,
    action VARCHAR(120) NOT NULL,
    meta_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_id VARCHAR(64) NOT NULL UNIQUE,
    webinar_id BIGINT UNSIGNED NOT NULL,
    lead_token VARCHAR(64) NOT NULL,
    amount_cents INT UNSIGNED NOT NULL,
    currency VARCHAR(8) NOT NULL,
    provider VARCHAR(32) NOT NULL,
    status VARCHAR(32) NOT NULL,
    provider_payload JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_payments_webinar_status (webinar_id, status),
    FOREIGN KEY (webinar_id) REFERENCES webinars(id) ON DELETE CASCADE
);

CREATE TABLE chat_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webinar_id BIGINT UNSIGNED NOT NULL,
    lead_token VARCHAR(64) NOT NULL,
    author_name VARCHAR(120) NOT NULL,
    message_text TEXT NOT NULL,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    is_admin_reply TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_chat_webinar_created (webinar_id, created_at),
    FOREIGN KEY (webinar_id) REFERENCES webinars(id) ON DELETE CASCADE
);

CREATE TABLE chat_bans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webinar_id BIGINT UNSIGNED NOT NULL,
    lead_token VARCHAR(64) NOT NULL,
    reason VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_chat_ban (webinar_id, lead_token),
    FOREIGN KEY (webinar_id) REFERENCES webinars(id) ON DELETE CASCADE
);

CREATE TABLE webinar_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL UNIQUE,
    webinar_id BIGINT UNSIGNED NOT NULL,
    mode ENUM('instant', 'plus_1_min', 'fixed') NOT NULL DEFAULT 'instant',
    timezone VARCHAR(64) NOT NULL DEFAULT 'UTC',
    start_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sessions_webinar_start (webinar_id, start_at),
    FOREIGN KEY (webinar_id) REFERENCES webinars(id) ON DELETE CASCADE
);

CREATE TABLE admin_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('owner', 'admin', 'moderator', 'sales') NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE api_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token VARCHAR(128) NOT NULL UNIQUE,
    role ENUM('owner', 'admin', 'moderator', 'sales') NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_api_tokens_user (user_id),
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE
);

CREATE TABLE webinar_room_states (
    webinar_id BIGINT UNSIGNED PRIMARY KEY,
    room_state ENUM('waiting', 'live', 'ended') NOT NULL DEFAULT 'waiting',
    message VARCHAR(255) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (webinar_id) REFERENCES webinars(id) ON DELETE CASCADE
);

CREATE TABLE offer_cards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webinar_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    ttl_sec INT UNSIGNED NOT NULL DEFAULT 900,
    cta_url VARCHAR(500) NOT NULL,
    activated_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_offer_webinar_activated (webinar_id, activated_at),
    FOREIGN KEY (webinar_id) REFERENCES webinars(id) ON DELETE CASCADE
);

CREATE TABLE lead_segments (
    webinar_id BIGINT UNSIGNED NOT NULL,
    lead_token VARCHAR(64) NOT NULL,
    segment_name VARCHAR(64) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (webinar_id, lead_token),
    FOREIGN KEY (webinar_id) REFERENCES webinars(id) ON DELETE CASCADE
);

CREATE TABLE email_queue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lead_token VARCHAR(64) NOT NULL,
    template_key VARCHAR(120) NOT NULL,
    payload_json JSON NULL,
    status ENUM('queued', 'sent', 'failed') NOT NULL DEFAULT 'queued',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_queue_status_created (status, created_at)
);

CREATE TABLE analytics_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webinar_id BIGINT UNSIGNED NOT NULL,
    lead_token VARCHAR(64) NOT NULL,
    event_type VARCHAR(64) NOT NULL,
    second_from_start INT UNSIGNED NOT NULL DEFAULT 0,
    utm_source VARCHAR(120) NULL,
    utm_medium VARCHAR(120) NULL,
    utm_campaign VARCHAR(120) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_analytics_event (webinar_id, event_type, second_from_start),
    FOREIGN KEY (webinar_id) REFERENCES webinars(id) ON DELETE CASCADE
);

CREATE TABLE ace_contents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webinar_id BIGINT UNSIGNED NOT NULL,
    content_type VARCHAR(64) NOT NULL,
    content_text MEDIUMTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ace_webinar_type (webinar_id, content_type),
    FOREIGN KEY (webinar_id) REFERENCES webinars(id) ON DELETE CASCADE
);

CREATE TABLE feature_flags (
    flag_key VARCHAR(120) PRIMARY KEY,
    is_enabled TINYINT(1) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
