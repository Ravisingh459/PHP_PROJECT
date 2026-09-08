-- NexaWork v3.0 Migration
-- Run: mysql -u root -p freelancehub < database/migration_v3.sql

USE freelancehub;

-- AI Query and Prompt Logs
CREATE TABLE IF NOT EXISTS ai_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    prompt_type VARCHAR(50) NOT NULL,
    prompt_text TEXT NOT NULL,
    response_text TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_ai_logs_user (user_id),
    INDEX idx_ai_logs_type (prompt_type)
) ENGINE=InnoDB;

-- Login Attempts for Rate Limiting & Security
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    email VARCHAR(255) NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_attempts_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB;
