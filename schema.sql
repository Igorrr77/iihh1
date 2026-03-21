CREATE TABLE tenants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  telegram_bot_token VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('owner','manager') NOT NULL DEFAULT 'manager',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE tenant_product_context (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  product_text MEDIUMTEXT NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE conversations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  chat_id BIGINT NOT NULL,
  client_handle VARCHAR(190) NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  INDEX idx_conv_tenant_chat (tenant_id, chat_id)
);

CREATE TABLE transcripts (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  conversation_id INT NOT NULL,
  role ENUM('client','seller','system') NOT NULL DEFAULT 'client',
  chunk_text MEDIUMTEXT NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  INDEX idx_transcript_created (created_at)
);

CREATE TABLE analyses (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  conversation_id INT NOT NULL,
  sentiment VARCHAR(32) NOT NULL,
  confidence_level TINYINT NOT NULL,
  objections_json JSON NOT NULL,
  pain_points_json JSON NOT NULL,
  lead_score TINYINT NOT NULL,
  churn_risk TINYINT NOT NULL,
  personality_json JSON NOT NULL,
  coaching_json JSON NOT NULL,
  patterns_json JSON NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  INDEX idx_analyses_created (created_at)
);

CREATE TABLE outbound_messages (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  conversation_id INT NOT NULL,
  message_text MEDIUMTEXT NOT NULL,
  sent_at DATETIME NOT NULL,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
);

CREATE TABLE tenant_integrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  crm_endpoint VARCHAR(500) NULL,
  webhook_url VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
