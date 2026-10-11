CREATE DATABASE IF NOT EXISTS lost_and_found
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE lost_and_found;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS claims;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO categories (name) VALUES
    ('Electronics'),
    ('Documents'),
    ('Bags'),
    ('Keys'),
    ('Clothing'),
    ('Accessories'),
    ('Other');

CREATE TABLE reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    type ENUM('lost', 'found') NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    event_date DATE NOT NULL,
    verification_question VARCHAR(255) NULL,
    verification_answer_hash VARCHAR(255) NULL,
    status ENUM('open', 'resolved', 'closed') NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_reports_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_reports_category FOREIGN KEY (category_id) REFERENCES categories(id),
    INDEX idx_reports_status (status),
    INDEX idx_reports_owner (user_id),
    INDEX idx_reports_category (category_id),
    INDEX idx_reports_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE claims (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id INT UNSIGNED NOT NULL,
    claimant_id INT UNSIGNED NOT NULL,
    claim_details TEXT NOT NULL,
    verification_matches TINYINT(1) NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_claims_report FOREIGN KEY (report_id) REFERENCES reports(id),
    CONSTRAINT fk_claims_user FOREIGN KEY (claimant_id) REFERENCES users(id),
    UNIQUE KEY unique_claim (report_id, claimant_id)
) ENGINE=InnoDB;
