SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(191) NOT NULL UNIQUE,
  `value` LONGTEXT NULL,
  `type` VARCHAR(50) NOT NULL DEFAULT 'string',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(191) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(50) NOT NULL DEFAULT 'admin',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  last_login_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(191) NOT NULL UNIQUE,
  parent_id BIGINT UNSIGNED NULL,
  title VARCHAR(191) NOT NULL,
  description TEXT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  is_system TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS videos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  youtube_video_id VARCHAR(32) NOT NULL UNIQUE,
  youtube_channel_id VARCHAR(64) NOT NULL,
  youtube_playlist_item_id VARCHAR(64) NULL,
  title VARCHAR(255) NOT NULL,
  description LONGTEXT NULL,
  published_at DATETIME NOT NULL,
  duration_seconds INT NOT NULL DEFAULT 0,
  duration_iso8601 VARCHAR(50) NOT NULL,
  thumbnail_default VARCHAR(500) NULL,
  thumbnail_medium VARCHAR(500) NULL,
  thumbnail_high VARCHAR(500) NULL,
  thumbnail_maxres VARCHAR(500) NULL,
  url VARCHAR(500) NOT NULL,
  embed_url VARCHAR(500) NOT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'draft',
  is_public TINYINT(1) NOT NULL DEFAULT 0,
  is_long_video TINYINT(1) NOT NULL DEFAULT 0,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  is_start_here TINYINT(1) NOT NULL DEFAULT 0,
  is_faq TINYINT(1) NOT NULL DEFAULT 0,
  is_story TINYINT(1) NOT NULL DEFAULT 0,
  ai_summary TEXT NULL,
  ai_confidence DECIMAL(5,4) NULL,
  ai_primary_category_id BIGINT UNSIGNED NULL,
  manual_primary_category_id BIGINT UNSIGNED NULL,
  final_primary_category_id BIGINT UNSIGNED NULL,
  manual_lock TINYINT(1) NOT NULL DEFAULT 0,
  transcript_text LONGTEXT NULL,
  source_payload_json LONGTEXT NULL,
  last_synced_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_videos_published_at (published_at),
  INDEX idx_videos_is_long_video (is_long_video),
  INDEX idx_videos_final_primary (final_primary_category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS video_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  video_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NOT NULL,
  source ENUM('ai','manual','rule','system') NOT NULL,
  confidence DECIMAL(5,4) NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uniq_video_category (video_id, category_id),
  INDEX idx_video_categories_video (video_id),
  INDEX idx_video_categories_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tags (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(191) NOT NULL UNIQUE,
  title VARCHAR(191) NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS video_tags (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  video_id BIGINT UNSIGNED NOT NULL,
  tag_id BIGINT UNSIGNED NOT NULL,
  source ENUM('ai','manual','rule') NOT NULL,
  confidence DECIMAL(5,4) NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uniq_video_tag (video_id, tag_id),
  INDEX idx_video_tags_video (video_id),
  INDEX idx_video_tags_tag (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ai_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  video_id BIGINT UNSIGNED NULL,
  job_type VARCHAR(100) NOT NULL,
  input_hash VARCHAR(64) NOT NULL,
  request_payload LONGTEXT NOT NULL,
  response_payload LONGTEXT NULL,
  status VARCHAR(50) NOT NULL,
  error_message TEXT NULL,
  attempts INT NOT NULL DEFAULT 0,
  started_at DATETIME NULL,
  finished_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_ai_jobs_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ai_classifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  video_id BIGINT UNSIGNED NOT NULL,
  model_name VARCHAR(120) NOT NULL,
  stage VARCHAR(50) NOT NULL,
  prompt_version VARCHAR(100) NOT NULL,
  input_snapshot LONGTEXT NOT NULL,
  output_json LONGTEXT NOT NULL,
  confidence DECIMAL(5,4) NULL,
  decided_primary_category_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sync_runs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sync_type VARCHAR(50) NOT NULL,
  started_at DATETIME NOT NULL,
  finished_at DATETIME NULL,
  status VARCHAR(50) NOT NULL,
  fetched_count INT NOT NULL DEFAULT 0,
  inserted_count INT NOT NULL DEFAULT 0,
  updated_count INT NOT NULL DEFAULT 0,
  skipped_short_count INT NOT NULL DEFAULT 0,
  queued_ai_count INT NOT NULL DEFAULT 0,
  error_count INT NOT NULL DEFAULT 0,
  log_text LONGTEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS manual_reviews (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  video_id BIGINT UNSIGNED NOT NULL,
  review_status VARCHAR(50) NOT NULL,
  note TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  resolved_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_manual_reviews_status (review_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS search_queries (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  raw_query VARCHAR(255) NOT NULL,
  normalized_query VARCHAR(255) NULL,
  ai_interpreted_json LONGTEXT NULL,
  results_count INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  INDEX idx_search_queries_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS related_videos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  video_id BIGINT UNSIGNED NOT NULL,
  related_video_id BIGINT UNSIGNED NOT NULL,
  score DECIMAL(6,4) NOT NULL,
  source ENUM('ai','rule') NOT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_related (video_id, related_video_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS page_cache (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cache_key VARCHAR(191) NOT NULL UNIQUE,
  content LONGTEXT NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(191) NOT NULL,
  entity_type VARCHAR(100) NOT NULL,
  entity_id BIGINT UNSIGNED NULL,
  old_value LONGTEXT NULL,
  new_value LONGTEXT NULL,
  ip VARCHAR(100) NULL,
  user_agent VARCHAR(500) NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_categories_slug ON categories(slug);
CREATE INDEX idx_tags_slug ON tags(slug);
