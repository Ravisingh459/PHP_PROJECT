-- NexaWork v2.0 Migration
-- Run: mysql -u root -p freelancehub < database/migration_v2.sql

USE freelancehub;

-- Contract milestones for phased escrow payments
CREATE TABLE IF NOT EXISTS contract_milestones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contract_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    amount DECIMAL(12,2) NOT NULL,
    due_date DATE DEFAULT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'paid') NOT NULL DEFAULT 'pending',
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    completed_at DATETIME DEFAULT NULL,
    paid_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    INDEX idx_milestones_contract (contract_id, status)
) ENGINE=InnoDB;
