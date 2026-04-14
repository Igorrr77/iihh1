CREATE TABLE IF NOT EXISTS release_stage_status (
    stage_code CHAR(1) PRIMARY KEY,
    stage_name VARCHAR(120) NOT NULL,
    is_completed TINYINT(1) NOT NULL DEFAULT 0,
    completed_at DATETIME NULL,
    owner VARCHAR(120) NULL,
    notes VARCHAR(500) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS production_incidents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    severity ENUM('critical','high','medium','low') NOT NULL,
    started_at DATETIME NOT NULL,
    resolved_at DATETIME NULL,
    summary VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_incidents_severity_started (severity, started_at)
);

CREATE TABLE IF NOT EXISTS sla_registry (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    metric_key VARCHAR(120) NOT NULL UNIQUE,
    target_value VARCHAR(120) NOT NULL,
    owner_on_call VARCHAR(120) NOT NULL,
    dashboard_url VARCHAR(500) NOT NULL,
    runbook_url VARCHAR(500) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS go_no_go_reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    review_date DATETIME NOT NULL,
    decision ENUM('go','no_go') NOT NULL,
    reviewer VARCHAR(120) NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_review_date (review_date)
);
