CREATE DATABASE IF NOT EXISTS askida_kitap
CHARACTER SET utf8mb4
COLLATE utf8mb4_turkish_ci;

USE askida_kitap;
SET NAMES utf8mb4;

DROP TABLE IF EXISTS backup_logs;
DROP TABLE IF EXISTS backups;
DROP TABLE IF EXISTS backup_settings;
DROP TABLE IF EXISTS password_change_logs;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS system_logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS contacts;
DROP TABLE IF EXISTS matches;
DROP TABLE IF EXISTS book_requests;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'kullanici', 'bagisci', 'talep_sahibi') NOT NULL DEFAULT 'kullanici',
    city VARCHAR(80) NOT NULL,
    phone VARCHAR(30) NULL,
    bio TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE books (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    donor_user_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NULL,
    title VARCHAR(160) NOT NULL,
    author VARCHAR(140) NOT NULL,
    description TEXT NOT NULL,
    city VARCHAR(80) NOT NULL,
    delivery_type ENUM('elden', 'kargo', 'farketmez') NOT NULL DEFAULT 'elden',
    contact_preference ENUM('platform_mesaji', 'telefon', 'eposta') NOT NULL DEFAULT 'platform_mesaji',
    cover_image VARCHAR(255) NULL,
    status ENUM('askida', 'talep edildi', 'eslesti', 'teslim edildi', 'pasif') NOT NULL DEFAULT 'askida',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_books_donor FOREIGN KEY (donor_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_books_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_books_status (status),
    INDEX idx_books_city (city),
    INDEX idx_books_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE book_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id INT UNSIGNED NOT NULL,
    requester_user_id INT UNSIGNED NOT NULL,
    request_note TEXT NOT NULL,
    request_status ENUM('bekliyor', 'onaylandi', 'reddedildi', 'iptal') NOT NULL DEFAULT 'bekliyor',
    donor_note TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_request_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    CONSTRAINT fk_request_user FOREIGN KEY (requester_user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_request_unique (book_id, requester_user_id),
    INDEX idx_request_status (request_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE matches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id INT UNSIGNED NOT NULL,
    request_id INT UNSIGNED NULL UNIQUE,
    donor_user_id INT UNSIGNED NOT NULL,
    requester_user_id INT UNSIGNED NOT NULL,
    delivery_status ENUM('bekliyor', 'teslim edildi', 'iptal edildi') NOT NULL DEFAULT 'bekliyor',
    delivery_note TEXT NULL,
    delivery_date DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_match_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    CONSTRAINT fk_match_request FOREIGN KEY (request_id) REFERENCES book_requests(id) ON DELETE SET NULL,
    CONSTRAINT fk_match_donor FOREIGN KEY (donor_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_match_requester FOREIGN KEY (requester_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_match_status (delivery_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    title VARCHAR(160) NOT NULL,
    message VARCHAR(255) NOT NULL,
    notification_type ENUM('bilgi', 'basari', 'uyari', 'hata') NOT NULL DEFAULT 'bilgi',
    target_url VARCHAR(255) NOT NULL DEFAULT '#',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_note_user_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE contacts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL,
    subject VARCHAR(160) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('yeni', 'okundu', 'yanitlandi') NOT NULL DEFAULT 'yeni',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE activity_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    module_name VARCHAR(100) NOT NULL,
    action_name VARCHAR(140) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(50) NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_activity_user_date (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE system_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    event_type VARCHAR(80) NOT NULL,
    event_status ENUM('basarili', 'basarisiz', 'bilgi') NOT NULL DEFAULT 'bilgi',
    message VARCHAR(255) NOT NULL,
    ip_address VARCHAR(50) NULL,
    user_agent VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_system_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_system_log_type_date (event_type, created_at),
    INDEX idx_system_log_status_date (event_status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE password_resets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    email VARCHAR(120) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    is_used TINYINT(1) NOT NULL DEFAULT 0,
    ip_address VARCHAR(50) NULL,
    user_agent VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_reset_email_date (email, created_at),
    INDEX idx_reset_token (token_hash),
    INDEX idx_reset_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE password_change_logs (
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

CREATE TABLE backup_settings (
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

CREATE TABLE backups (
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

CREATE TABLE backup_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    backup_id BIGINT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    status ENUM('basarili', 'basarisiz', 'bilgi') NOT NULL DEFAULT 'bilgi',
    message VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_backup_logs_backup FOREIGN KEY (backup_id) REFERENCES backups(id) ON DELETE SET NULL,
    INDEX idx_backup_logs_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

INSERT INTO backup_settings (
    id, automatic_enabled, frequency, backup_time, max_backup_count, backup_path,
    auto_cleanup_enabled, last_success_at, last_failed_at, next_run_at, last_error, updated_at
) VALUES
(1, 0, 'gunluk', '03:00:00', 20, 'storage/backups', 1, NULL, NULL, NULL, NULL, NOW());

INSERT INTO users (full_name, username, email, password_hash, role, city, phone, is_active) VALUES
('Platform Yöneticisi', 'admin', 'admin@askidakitap.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'İstanbul', '05550000001', 1),
('Ayşe Bağışçı', 'bagisci', 'bagisci@askidakitap.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'bagisci', 'Ankara', '05550000002', 1),
('Mehmet Okur', 'okur', 'okur@askidakitap.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talep_sahibi', 'İzmir', '05550000003', 1),
('Elif Kullanıcı', 'elif', 'elif@askidakitap.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kullanici', 'Bursa', '05550000004', 1);

INSERT INTO categories (category_name, is_active) VALUES
('Roman', 1),
('Kişisel Gelişim', 1),
('Çocuk Kitabı', 1),
('Sınav Hazırlık', 1),
('Bilim', 1),
('Tarih', 1);

INSERT INTO books (donor_user_id, category_id, title, author, description, city, delivery_type, contact_preference, status, is_active) VALUES
(2, 1, 'Kürk Mantolu Madonna', 'Sabahattin Ali', 'Temiz durumda, sayfaları eksiksiz.', 'Ankara', 'elden', 'platform_mesaji', 'askida', 1),
(2, 2, 'Atomik Alışkanlıklar', 'James Clear', 'Not alınmış ancak okunabilir durumda.', 'Ankara', 'kargo', 'eposta', 'talep edildi', 1),
(4, 3, 'Çocuk Kalbi', 'Edmondo De Amicis', 'İlkokul seviyesi için uygun.', 'Bursa', 'farketmez', 'telefon', 'askida', 1),
(2, 4, 'TYT Matematik Soru Bankası', 'Kolektif', 'Güncel baskı değil ama temel kazanım için yeterli.', 'İstanbul', 'kargo', 'platform_mesaji', 'teslim edildi', 1),
(4, 5, 'Kısa Fizik Tarihi', 'Stephen Hawking', 'Bilim meraklıları için uygun.', 'Bursa', 'elden', 'platform_mesaji', 'askida', 1),
(2, 6, 'Nutuk', 'Mustafa Kemal Atatürk', '2 cilt, temiz kullanım.', 'Ankara', 'elden', 'telefon', 'pasif', 0);

INSERT INTO book_requests (book_id, requester_user_id, request_note, request_status, donor_note, created_at, updated_at) VALUES
(2, 3, 'Üniversite hazırlığında faydalanmak istiyorum.', 'onaylandi', 'Bu hafta sonu teslim edebiliriz.', NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 3 DAY),
(1, 3, 'Romanı uzun süredir okumak istiyorum.', 'bekliyor', NULL, NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY),
(3, 1, 'Kardeşim için talep ediyorum.', 'reddedildi', 'Öncelik çocuklara yönelik kurumlara verildi.', NOW() - INTERVAL 6 DAY, NOW() - INTERVAL 5 DAY),
(4, 3, 'Sınav sürecinde çok işime yarar.', 'onaylandi', 'Kargo ile gönderildi.', NOW() - INTERVAL 15 DAY, NOW() - INTERVAL 14 DAY);

INSERT INTO matches (book_id, request_id, donor_user_id, requester_user_id, delivery_status, delivery_note, delivery_date, created_at, updated_at) VALUES
(2, 1, 2, 3, 'bekliyor', 'Hafta sonu şehir merkezinde teslim planlandı.', NULL, NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY),
(4, 4, 2, 3, 'teslim edildi', 'Kargo ile başarıyla teslim edildi.', NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 10 DAY);

INSERT INTO notifications (user_id, title, message, notification_type, target_url, is_read, created_at) VALUES
(2, 'Yeni Talep', 'Kürk Mantolu Madonna kitabınız için yeni talep var.', 'uyari', 'requests/manage.php', 0, NOW() - INTERVAL 1 DAY),
(3, 'Talebiniz Onaylandı', 'Atomik Alışkanlıklar talebiniz onaylandı.', 'basari', 'matches/index.php', 0, NOW() - INTERVAL 20 HOUR),
(1, 'Sistem Özeti', 'Bu hafta 2 yeni eşleşme tamamlandı.', 'bilgi', 'admin/dashboard.php', 1, NOW() - INTERVAL 8 HOUR);

INSERT INTO contacts (full_name, email, subject, message, status, created_at) VALUES
('Murat Kaya', 'murat@example.com', 'Platform hakkında', 'Toplu bağış için kurum süreci var mı?', 'yeni', NOW() - INTERVAL 2 DAY),
('Zeynep Demir', 'zeynep@example.com', 'Destek talebi', 'Talep ettiğim kitabın durumu ne zaman güncellenir?', 'okundu', NOW() - INTERVAL 1 DAY);

INSERT INTO activity_logs (user_id, module_name, action_name, details, ip_address, created_at) VALUES
(2, 'Kitap', 'Kitap eklendi', 'Kürk Mantolu Madonna askıya eklendi.', '127.0.0.1', NOW() - INTERVAL 7 DAY),
(3, 'Talep', 'Talep oluşturuldu', 'Atomik Alışkanlıklar için talep gönderildi.', '127.0.0.1', NOW() - INTERVAL 4 DAY),
(1, 'Yönetim', 'Talep onayı', '1 numaralı talep admin tarafından onaylandı.', '127.0.0.1', NOW() - INTERVAL 3 DAY);

INSERT INTO system_logs (user_id, event_type, event_status, message, ip_address, user_agent, created_at) VALUES
(1, 'auth.login', 'basarili', 'Yönetici giriş yaptı.', '127.0.0.1', 'Mozilla/5.0', NOW() - INTERVAL 1 DAY),
(3, 'request.create', 'basarili', 'Kullanıcı kitap talebi oluşturdu.', '127.0.0.1', 'Mozilla/5.0', NOW() - INTERVAL 12 HOUR),
(2, 'book.create', 'basarili', 'Bağışçı yeni kitap ekledi.', '127.0.0.1', 'Mozilla/5.0', NOW() - INTERVAL 6 HOUR);
