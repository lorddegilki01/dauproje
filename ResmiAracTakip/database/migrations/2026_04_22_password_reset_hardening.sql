SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET collation_connection = 'utf8mb4_turkish_ci';

USE resmi_arac_takip;

ALTER TABLE password_resets
    MODIFY token_hash CHAR(64) NOT NULL,
    ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45) NULL AFTER token_hash,
    ADD COLUMN IF NOT EXISTS user_agent VARCHAR(255) NULL AFTER ip_address;

ALTER TABLE password_resets
    ADD INDEX IF NOT EXISTS idx_password_resets_user_window (user_id, created_at),
    ADD INDEX IF NOT EXISTS idx_password_resets_email_window (email, created_at);

CREATE TABLE IF NOT EXISTS security_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    email VARCHAR(120) NULL,
    event_type ENUM('password_reset_requested', 'password_reset_sent', 'password_reset_failed', 'password_changed') NOT NULL,
    status ENUM('success', 'failed', 'blocked') NOT NULL DEFAULT 'success',
    details VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_security_event_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_security_event_filter (event_type, status, created_at),
    INDEX idx_security_event_email (email, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS backup_schedules (
    id TINYINT UNSIGNED PRIMARY KEY,
    frequency ENUM('daily', 'weekly', 'monthly') NOT NULL DEFAULT 'daily',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    next_run_at DATETIME NULL,
    last_run_at DATETIME NULL,
    last_status ENUM('success', 'failed', 'pending') NOT NULL DEFAULT 'pending',
    last_error VARCHAR(255) NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS backup_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    run_type ENUM('manual', 'scheduled') NOT NULL DEFAULT 'manual',
    status ENUM('success', 'failed') NOT NULL,
    backup_file VARCHAR(255) NULL,
    error_message VARCHAR(255) NULL,
    triggered_by INT UNSIGNED NULL,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finished_at DATETIME NULL,
    CONSTRAINT fk_backup_log_user FOREIGN KEY (triggered_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_backup_logs_started (started_at),
    INDEX idx_backup_logs_status (status, started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

INSERT INTO backup_schedules (id, frequency, is_active, next_run_at, last_status)
VALUES (1, 'daily', 1, DATE_ADD(NOW(), INTERVAL 1 DAY), 'pending')
ON DUPLICATE KEY UPDATE id = id;
