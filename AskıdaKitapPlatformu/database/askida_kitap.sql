CREATE DATABASE IF NOT EXISTS askida_kitap
CHARACTER SET utf8mb4
COLLATE utf8mb4_turkish_ci;

USE askida_kitap;
SET NAMES utf8mb4;

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
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
    status ENUM('askıda', 'talep edildi', 'teslim edildi', 'pasif') NOT NULL DEFAULT 'askıda',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_books_donor FOREIGN KEY (donor_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_books_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE book_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id INT UNSIGNED NOT NULL,
    requester_user_id INT UNSIGNED NOT NULL,
    request_note TEXT NOT NULL,
    request_status ENUM('bekliyor', 'onaylandı', 'reddedildi', 'iptal') NOT NULL DEFAULT 'bekliyor',
    donor_note TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_request_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    CONSTRAINT fk_request_user FOREIGN KEY (requester_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE matches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id INT UNSIGNED NOT NULL,
    request_id INT UNSIGNED NULL UNIQUE,
    donor_user_id INT UNSIGNED NOT NULL,
    requester_user_id INT UNSIGNED NOT NULL,
    delivery_status ENUM('bekliyor', 'teslim edildi', 'iptal') NOT NULL DEFAULT 'bekliyor',
    delivery_note TEXT NULL,
    delivery_date DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_match_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    CONSTRAINT fk_match_request FOREIGN KEY (request_id) REFERENCES book_requests(id) ON DELETE SET NULL,
    CONSTRAINT fk_match_donor FOREIGN KEY (donor_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_match_requester FOREIGN KEY (requester_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    title VARCHAR(160) NOT NULL,
    message VARCHAR(255) NOT NULL,
    notification_type ENUM('bilgi', 'başarı', 'uyarı', 'hata') NOT NULL DEFAULT 'bilgi',
    target_url VARCHAR(255) NOT NULL DEFAULT '#',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE contacts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL,
    subject VARCHAR(160) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('yeni', 'okundu', 'yanıtlandı') NOT NULL DEFAULT 'yeni',
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
    CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

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
(2, 1, 'Kürk Mantolu Madonna', 'Sabahattin Ali', 'Temiz durumda, sayfaları eksiksiz.', 'Ankara', 'elden', 'platform_mesaji', 'askıda', 1),
(2, 2, 'Atomik Alışkanlıklar', 'James Clear', 'Not alınmış ancak okunabilir durumda.', 'Ankara', 'kargo', 'eposta', 'talep edildi', 1),
(4, 3, 'Çocuk Kalbi', 'Edmondo De Amicis', 'İlkokul seviyesi için uygun.', 'Bursa', 'farketmez', 'telefon', 'askıda', 1),
(2, 4, 'TYT Matematik Soru Bankası', 'Kolektif', 'Güncel baskı değil ama temel kazanım için yeterli.', 'İstanbul', 'kargo', 'platform_mesaji', 'teslim edildi', 1),
(4, 5, 'Kısa Fizik Tarihi', 'Stephen Hawking', 'Bilim meraklıları için uygun.', 'Bursa', 'elden', 'platform_mesaji', 'askıda', 1),
(2, 6, 'Nutuk', 'Mustafa Kemal Atatürk', '2 cilt, temiz kullanım.', 'Ankara', 'elden', 'telefon', 'pasif', 0);

INSERT INTO book_requests (book_id, requester_user_id, request_note, request_status, donor_note, created_at, updated_at) VALUES
(2, 3, 'Üniversite hazırlığında faydalanmak istiyorum.', 'onaylandı', 'Bu hafta sonu teslim edebiliriz.', NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 3 DAY),
(1, 3, 'Romanı uzun süredir okumak istiyorum.', 'bekliyor', NULL, NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY),
(3, 1, 'Kardeşim için talep ediyorum.', 'reddedildi', 'Öncelik çocuklara yönelik kurumlara verildi.', NOW() - INTERVAL 6 DAY, NOW() - INTERVAL 5 DAY),
(4, 3, 'Sınav sürecinde çok işime yarar.', 'onaylandı', 'Kargo ile gönderildi.', NOW() - INTERVAL 15 DAY, NOW() - INTERVAL 14 DAY);

INSERT INTO matches (book_id, request_id, donor_user_id, requester_user_id, delivery_status, delivery_note, delivery_date, created_at, updated_at) VALUES
(2, 1, 2, 3, 'bekliyor', 'Hafta sonu şehir merkezinde teslim planlandı.', NULL, NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY),
(4, 4, 2, 3, 'teslim edildi', 'Kargo ile başarıyla teslim edildi.', NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 10 DAY);

INSERT INTO notifications (user_id, title, message, notification_type, target_url, is_read, created_at) VALUES
(2, 'Yeni Talep', 'Kürk Mantolu Madonna kitabınız için yeni talep var.', 'uyarı', 'requests/manage.php', 0, NOW() - INTERVAL 1 DAY),
(3, 'Talebiniz Onaylandı', 'Atomik Alışkanlıklar talebiniz onaylandı.', 'başarı', 'matches/index.php', 0, NOW() - INTERVAL 20 HOUR),
(1, 'Sistem Özeti', 'Bu hafta 2 yeni eşleşme tamamlandı.', 'bilgi', 'admin/dashboard.php', 1, NOW() - INTERVAL 8 HOUR);

INSERT INTO contacts (full_name, email, subject, message, status, created_at) VALUES
('Murat Kaya', 'murat@example.com', 'Platform hakkında', 'Toplu bağış için kurum süreci var mı?', 'yeni', NOW() - INTERVAL 2 DAY),
('Zeynep Demir', 'zeynep@example.com', 'Destek talebi', 'Talep ettiğim kitabın durumu ne zaman güncellenir?', 'okundu', NOW() - INTERVAL 1 DAY);

INSERT INTO activity_logs (user_id, module_name, action_name, details, ip_address, created_at) VALUES
(2, 'Kitap', 'Kitap eklendi', 'Kürk Mantolu Madonna askıya eklendi.', '127.0.0.1', NOW() - INTERVAL 7 DAY),
(3, 'Talep', 'Talep oluşturuldu', 'Atomik Alışkanlıklar için talep gönderildi.', '127.0.0.1', NOW() - INTERVAL 4 DAY),
(1, 'Yönetim', 'Talep onayı', '1 numaralı talep admin tarafından onaylandı.', '127.0.0.1', NOW() - INTERVAL 3 DAY);

