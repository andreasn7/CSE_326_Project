-- =====================================================================
-- CEI326 Web Engineering – Registry of Assets (Pothen Esches)
-- Database schema
--
-- Relationship summary:
-- one-to-one users <-> politicians (each politician account has one user)
-- one-to-many parties -> politicians (one party, many members)
-- one-to-many positions -> politicians (one position, many holders)
-- one-to-many districts -> politicians (one district, many representatives)
-- one-to-many politicians -> declarations (one politician, many yearly filings)
-- many-to-many politicians <-> committees (via politician_committees)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS cei326_project CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cei326_project;

-- ---------------------------------------------------------------------
-- Authenticated accounts (administrators and politicians).
-- Identity fields (first_name, last_name, phone) live here so a single
-- profile record describes every user regardless of role.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','politician') NOT NULL DEFAULT 'politician',
    phone VARCHAR(30) DEFAULT NULL,
    first_name VARCHAR(80) NOT NULL DEFAULT '',
    last_name VARCHAR(80) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_lastname (last_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Political parties referenced by politician profiles.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS parties (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    short_name VARCHAR(30) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Official positions a politician may hold (MP, Minister, Mayor, ...).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS positions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(120) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Administrative districts of the Republic of Cyprus.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS districts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Standing committees of the Cyprus Parliament. Joined to politicians
-- through the politician_committees junction table (many-to-many).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS committees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Politician profile: the public-facing identity of a user with role
-- 'politician'. Personal name and contact fields stay on `users`; this
-- table holds only the politically-meaningful attributes.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS politicians (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    party_id INT UNSIGNED DEFAULT NULL,
    position_id INT UNSIGNED DEFAULT NULL,
    district_id INT UNSIGNED DEFAULT NULL,
    children TINYINT UNSIGNED DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pol_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_pol_party FOREIGN KEY (party_id) REFERENCES parties(id) ON DELETE SET NULL,
    CONSTRAINT fk_pol_pos FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE SET NULL,
    CONSTRAINT fk_pol_dist FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE SET NULL,
    INDEX idx_pol_party (party_id),
    INDEX idx_pol_position (position_id),
    INDEX idx_pol_district (district_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Junction table: politicians <-> committees (many-to-many).
-- Composite primary key prevents a politician being assigned to the
-- same committee twice. Both foreign keys cascade on delete so the
-- junction stays clean when either side is removed.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS politician_committees (
    politician_id INT UNSIGNED NOT NULL,
    committee_id INT UNSIGNED NOT NULL,
    assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (politician_id, committee_id),
    CONSTRAINT fk_pc_pol FOREIGN KEY (politician_id) REFERENCES politicians(id) ON DELETE CASCADE,
    CONSTRAINT fk_pc_comm FOREIGN KEY (committee_id) REFERENCES committees(id) ON DELETE CASCADE,
    INDEX idx_pc_committee (committee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Annual asset declarations. The (politician_id, year) pair is unique
-- because a politician files exactly one declaration per year. Each row
-- starts as a draft and becomes 'submitted' when officially filed.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS asset_declarations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    politician_id INT UNSIGNED NOT NULL,
    year YEAR NOT NULL,
    status ENUM('draft','submitted') NOT NULL DEFAULT 'draft',
    total_deposits DECIMAL(15,2) DEFAULT 0.00,
    total_debts DECIMAL(15,2) DEFAULT 0.00,
    annual_income DECIMAL(15,2) DEFAULT 0.00,
    vehicles_count TINYINT UNSIGNED DEFAULT 0,
    vehicles_details TEXT DEFAULT NULL,
    shares_value DECIMAL(15,2) DEFAULT 0.00,
    shares_details TEXT DEFAULT NULL,
    real_estate_count TINYINT UNSIGNED DEFAULT 0,
    real_estate_details TEXT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    submitted_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pol_year (politician_id, year),
    CONSTRAINT fk_decl_pol FOREIGN KEY (politician_id) REFERENCES politicians(id) ON DELETE CASCADE,
    INDEX idx_decl_year (year),
    INDEX idx_decl_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;