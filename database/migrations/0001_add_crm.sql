-- Run this against an already-deployed database (one created before the CRM
-- tables existed in schema.sql) to add Phase 2 without dropping any data.
-- `mysql -u root -p virtual_business_card < database/migrations/0001_add_crm.sql`

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS role ENUM('admin', 'commercial') NOT NULL DEFAULT 'commercial' AFTER name;

CREATE TABLE IF NOT EXISTS leads (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  phone VARCHAR(50) NULL,
  email VARCHAR(255) NULL,
  budget_min INT UNSIGNED NULL,
  budget_max INT UNSIGNED NULL,
  location VARCHAR(255) NULL,
  property_type VARCHAR(100) NULL,
  options_json TEXT NULL,
  message TEXT NULL,
  source VARCHAR(50) NOT NULL DEFAULT 'manual',
  campaign VARCHAR(255) NULL,
  status ENUM('prospect', 'interesse', 'reserve', 'vendu') NOT NULL DEFAULT 'prospect',
  assigned_to INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_leads_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_leads_status (status),
  INDEX idx_leads_assigned_to (assigned_to),
  INDEX idx_leads_source (source),
  INDEX idx_leads_created_at (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lead_interactions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NULL,
  type ENUM('note', 'appel', 'visite', 'email') NOT NULL DEFAULT 'note',
  content TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_lead_interactions_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
  CONSTRAINT fk_lead_interactions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_lead_interactions_lead_id (lead_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reminders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  due_at DATETIME NOT NULL,
  note VARCHAR(512) NULL,
  is_done TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reminders_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
  CONSTRAINT fk_reminders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_reminders_user_due (user_id, due_at),
  INDEX idx_reminders_lead_id (lead_id)
) ENGINE=InnoDB;
