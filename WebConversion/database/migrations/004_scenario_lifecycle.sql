ALTER TABLE webinar_scenarios
    ADD COLUMN IF NOT EXISTS status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    ADD COLUMN IF NOT EXISTS published_at DATETIME NULL,
    ADD COLUMN IF NOT EXISTS source_version INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS migration_tag VARCHAR(64) NULL,
    ADD INDEX IF NOT EXISTS idx_scenarios_webinar_status (webinar_id, status, version);

CREATE TABLE IF NOT EXISTS scenario_import_exports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webinar_id BIGINT UNSIGNED NOT NULL,
    operation ENUM('import','export') NOT NULL,
    adapter VARCHAR(64) NOT NULL,
    payload_json JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (webinar_id) REFERENCES webinars(id) ON DELETE CASCADE
);
