SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS password_change_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    change_type ENUM('profil', 'sifremi_unuttum', 'admin') NOT NULL DEFAULT 'profil',
    change_status ENUM('basarili', 'basarisiz') NOT NULL DEFAULT 'basarili',
    ip_address VARCHAR(50) NULL,
    user_agent VARCHAR(500) NULL,
    details VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_pwd_change_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_pwd_change_user_date (user_id, created_at),
    INDEX idx_pwd_change_type_date (change_type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS backup_settings (
    id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
    automatic_enabled TINYINT(1) NOT NULL DEFAULT 0,
    frequency ENUM('gunluk', 'haftalik', 'aylik') NOT NULL DEFAULT 'gunluk',
    backup_time TIME NOT NULL DEFAULT '03:00:00',
    max_backup_count INT UNSIGNED NOT NULL DEFAULT 20,
    backup_path VARCHAR(255) NOT NULL DEFAULT 'storage/backups',
    auto_cleanup_enabled TINYINT(1) NOT NULL DEFAULT 1,
    last_success_at DATETIME NULL,
    last_failed_at DATETIME NULL,
    next_run_at DATETIME NULL,
    last_error VARCHAR(255) NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS backups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    backup_type ENUM('manuel', 'otomatik') NOT NULL DEFAULT 'manuel',
    status ENUM('basarili', 'basarisiz') NOT NULL DEFAULT 'basarili',
    file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
    checksum VARCHAR(128) NULL,
    error_message VARCHAR(255) NULL,
    started_by INT UNSIGNED NULL,
    started_at DATETIME NOT NULL,
    finished_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_backup_user FOREIGN KEY (started_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_backup_type_date (backup_type, created_at),
    INDEX idx_backup_status_date (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE IF NOT EXISTS backup_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    backup_id BIGINT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    status ENUM('basarili', 'basarisiz', 'bilgi') NOT NULL DEFAULT 'bilgi',
    message VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_backup_logs_backup FOREIGN KEY (backup_id) REFERENCES backups(id) ON DELETE SET NULL,
    INDEX idx_backup_logs_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

INSERT IGNORE INTO backup_settings
    (id, automatic_enabled, frequency, backup_time, max_backup_count, backup_path, auto_cleanup_enabled, updated_at)
VALUES
    (1, 0, 'gunluk', '03:00:00', 20, 'storage/backups', 1, NOW());
