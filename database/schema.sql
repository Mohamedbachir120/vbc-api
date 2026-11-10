-- database/schema.sql
-- MySQL schema mirroring prisma/schema.prisma (Postgres) for the Virtual Business Card API

CREATE DATABASE IF NOT EXISTS virtual_business_card
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE virtual_business_card;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cards (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uuid CHAR(36) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  role VARCHAR(255) NULL,
  company VARCHAR(255) NULL,
  website VARCHAR(255) NULL,
  email VARCHAR(255) NULL,
  phone VARCHAR(255) NULL,
  location VARCHAR(255) NULL,
  accent VARCHAR(7) NOT NULL DEFAULT '#6366f1',
  accent_light VARCHAR(7) NOT NULL DEFAULT '#e0e7ff',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  user_id INT UNSIGNED NOT NULL,
  CONSTRAINT fk_cards_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_cards_user_id (user_id),
  INDEX idx_cards_uuid (uuid)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS card_views (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(512) NULL,
  viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  card_id INT UNSIGNED NOT NULL,
  CONSTRAINT fk_card_views_card FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE,
  INDEX idx_card_views_card_id (card_id),
  INDEX idx_card_views_viewed_at (viewed_at)
) ENGINE=InnoDB;

-- ─── CRM (Phase 2) ──────────────────────────────────────────────────────────

ALTER TABLE users
  ADD COLUMN role ENUM('admin', 'commercial') NOT NULL DEFAULT 'commercial' AFTER name;

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
