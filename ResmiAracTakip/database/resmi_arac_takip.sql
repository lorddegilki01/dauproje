SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET collation_connection = 'utf8mb4_turkish_ci';

CREATE DATABASE IF NOT EXISTS resmi_arac_takip
CHARACTER SET utf8mb4
COLLATE utf8mb4_turkish_ci;

USE resmi_arac_takip;

DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS backup_logs;
DROP TABLE IF EXISTS backup_schedules;
DROP TABLE IF EXISTS security_events;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS issue_reports;
DROP TABLE IF EXISTS vehicle_requests;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS expense_records;
DROP TABLE IF EXISTS maintenance_records;
DROP TABLE IF EXISTS fuel_logs;
DROP TABLE IF EXISTS vehicle_assignments;
DROP TABLE IF EXISTS personnel;
DROP TABLE IF EXISTS vehicles;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    phone VARCHAR(30) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'personel') NOT NULL DEFAULT 'personel',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE vehicles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plate_number VARCHAR(20) NOT NULL UNIQUE,
    brand VARCHAR(80) NOT NULL,
    model VARCHAR(80) NOT NULL,
    model_year YEAR NOT NULL,
    vehicle_type VARCHAR(60) NOT NULL,
    fuel_type VARCHAR(30) NOT NULL,
    current_km INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('müsait', 'kullanımda', 'bakımda', 'pasif') NOT NULL DEFAULT 'müsait',
    license_note TEXT NULL,
    inspection_due_date DATE NULL,
    insurance_due_date DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE personnel (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL UNIQUE,
    full_name VARCHAR(120) NOT NULL,
    registration_no VARCHAR(30) NOT NULL UNIQUE,
    phone VARCHAR(30) NULL,
    email VARCHAR(120) NULL,
    department VARCHAR(100) NOT NULL,
    duty_title VARCHAR(100) NOT NULL,
    license_class VARCHAR(20) NULL,
    status ENUM('aktif', 'pasif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_personnel_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE vehicle_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT UNSIGNED NOT NULL,
    personnel_id INT UNSIGNED NOT NULL,
    assigned_at DATETIME NOT NULL,
    expected_return_at DATETIME NULL,
    received_by_personnel_at DATETIME NULL,
    receive_note TEXT NULL,
    returned_at DATETIME NULL,
    start_km INT UNSIGNED NOT NULL,
    end_km INT UNSIGNED NULL,
    return_note TEXT NULL,
    issue_note TEXT NULL,
    usage_purpose VARCHAR(180) NOT NULL,
    route_info VARCHAR(180) NULL,
    description TEXT NULL,
    return_status ENUM('iade edilmedi', 'iade edildi') NOT NULL DEFAULT 'iade edilmedi',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_assignment_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    CONSTRAINT fk_assignment_personnel FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE vehicle_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT UNSIGNED NOT NULL,
    vehicle_id INT UNSIGNED NOT NULL,
    request_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usage_purpose VARCHAR(180) NOT NULL,
    planned_start_at DATETIME NOT NULL,
    planned_end_at DATETIME NOT NULL,
    description TEXT NULL,
    status ENUM('bekliyor', 'onaylandı', 'reddedildi', 'iptal') NOT NULL DEFAULT 'bekliyor',
    admin_note TEXT NULL,
    approved_by INT UNSIGNED NULL,
    assignment_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_request_personnel FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE,
    CONSTRAINT fk_request_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    CONSTRAINT fk_request_admin FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_request_assignment FOREIGN KEY (assignment_id) REFERENCES vehicle_assignments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE fuel_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT UNSIGNED NOT NULL,
    personnel_id INT UNSIGNED NULL,
    purchase_date DATE NOT NULL,
    litre DECIMAL(10,2) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    odometer_km INT UNSIGNED NOT NULL,
    station_name VARCHAR(120) NOT NULL,
    receipt_note TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_fuel_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    CONSTRAINT fk_fuel_personnel FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE maintenance_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT UNSIGNED NOT NULL,
    maintenance_type VARCHAR(120) NOT NULL,
    last_maintenance_date DATE NOT NULL,
    next_maintenance_date DATE NOT NULL,
    next_maintenance_km INT UNSIGNED NULL,
    service_name VARCHAR(120) NOT NULL,
    cost DECIMAL(10,2) NOT NULL DEFAULT 0,
    description TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_maintenance_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE expense_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT UNSIGNED NOT NULL,
    expense_type ENUM('arıza', 'onarım', 'parça değişimi', 'diğer') NOT NULL,
    expense_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    service_name VARCHAR(120) NULL,
    parts_changed VARCHAR(180) NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_expense_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE issue_reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT UNSIGNED NOT NULL,
    personnel_id INT UNSIGNED NOT NULL,
    assignment_id INT UNSIGNED NULL,
    subject VARCHAR(140) NOT NULL,
    description TEXT NOT NULL,
    report_date DATE NOT NULL,
    urgency ENUM('düşük', 'orta', 'yüksek', 'kritik') NOT NULL DEFAULT 'orta',
    status ENUM('açık', 'inceleniyor', 'çözüldü', 'reddedildi') NOT NULL DEFAULT 'açık',
    admin_note TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_issue_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    CONSTRAINT fk_issue_personnel FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE,
    CONSTRAINT fk_issue_assignment FOREIGN KEY (assignment_id) REFERENCES vehicle_assignments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE announcements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    content TEXT NOT NULL,
    target_role ENUM('tümü', 'admin', 'personel') NOT NULL DEFAULT 'tümü',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_announcement_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(40) NOT NULL,
    title VARCHAR(180) NOT NULL,
    message VARCHAR(300) NOT NULL,
    related_module VARCHAR(80) NULL,
    related_record_id BIGINT UNSIGNED NULL,
    url VARCHAR(255) NULL,
    icon VARCHAR(40) NULL,
    color_class VARCHAR(30) NULL,
    unique_key VARCHAR(160) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_notification_user_key (user_id, unique_key),
    INDEX idx_notification_user_read (user_id, is_read, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE security_events (
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

CREATE TABLE backup_schedules (
    id TINYINT UNSIGNED PRIMARY KEY,
    frequency ENUM('daily', 'weekly', 'monthly') NOT NULL DEFAULT 'daily',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    next_run_at DATETIME NULL,
    last_run_at DATETIME NULL,
    last_status ENUM('success', 'failed', 'pending') NOT NULL DEFAULT 'pending',
    last_error VARCHAR(255) NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE backup_logs (
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

CREATE TABLE activity_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    module_name VARCHAR(100) NOT NULL,
    action_name VARCHAR(120) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(50) NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE password_resets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    email VARCHAR(120) NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_password_resets_lookup (token_hash, expires_at, used_at),
    INDEX idx_password_resets_user_window (user_id, created_at),
    INDEX idx_password_resets_email_window (email, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

INSERT INTO users (full_name, username, email, phone, password_hash, role, is_active) VALUES
('Sistem Yöneticisi', 'admin', 'admin@gmail.com', '05320000001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1),
('Ahmet Çelik', 'personel', 'personel@gmail.com', '05321234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'personel', 1);

INSERT INTO vehicles (plate_number, brand, model, model_year, vehicle_type, fuel_type, current_km, status, license_note, inspection_due_date, insurance_due_date) VALUES
('34 AB 1234', 'Ford', 'Transit', 2022, 'Minibüs', 'Dizel', 68250, 'müsait', 'Personel servisinde kullanılmaktadır.', '2026-06-15', '2026-08-20'),
('06 CD 5678', 'Fiat', 'Egea', 2021, 'Binek', 'Benzin', 45110, 'kullanımda', 'İdari işler birimine tahsisli.', '2026-05-10', '2026-07-01'),
('07 EF 9012', 'Volkswagen', 'Crafter', 2020, 'Panelvan', 'Dizel', 98320, 'bakımda', 'Teknik ekip sevkiyat aracı.', '2026-04-25', '2026-05-30'),
('35 GH 3456', 'Renault', 'Clio', 2023, 'Binek', 'Benzin', 18140, 'müsait', 'Şehir içi resmi işlemler.', '2027-01-10', '2026-12-18');

INSERT INTO personnel (user_id, full_name, registration_no, phone, email, department, duty_title, license_class, status) VALUES
(2, 'Ahmet Çelik', '100245', '05321234567', 'personel@gmail.com', 'İdari İşler', 'Şoför', 'B', 'aktif'),
(NULL, 'Ayşe Güneş', '100312', '05337654321', 'ayse.gunes@kurum.gov.tr', 'Destek Hizmetleri', 'Memur', 'B', 'aktif'),
(NULL, 'Mehmet Şahin', '100478', '05324445566', 'mehmet.sahin@kurum.gov.tr', 'Teknik Birim', 'Teknisyen', 'C', 'aktif'),
(NULL, 'Elif Öztürk', '100589', '05321112233', 'elif.ozturk@kurum.gov.tr', 'Satın Alma', 'Uzman', 'B', 'pasif');

INSERT INTO vehicle_assignments (vehicle_id, personnel_id, assigned_at, expected_return_at, received_by_personnel_at, receive_note, returned_at, start_km, end_km, return_note, issue_note, usage_purpose, route_info, description, return_status) VALUES
(2, 1, '2026-04-20 08:30:00', '2026-04-22 18:00:00', '2026-04-20 08:32:00', 'Araç teslim alındı, yakıt seviyesi uygun.', NULL, 44800, NULL, NULL, NULL, 'Resmi evrak teslimi', 'Lefkoşa - Girne', 'Gün içi resmi yazışma ve teslim süreci.', 'iade edilmedi'),
(1, 2, '2026-04-15 09:00:00', '2026-04-15 17:00:00', '2026-04-15 09:02:00', 'Toplantı için teslim alındı.', '2026-04-15 16:40:00', 67910, 68250, 'Araç sorunsuz iade edildi.', NULL, 'Personel nakli', 'Gazimağusa - Lefkoşa', 'Toplantı ve dönüş transferi.', 'iade edildi');

INSERT INTO vehicle_requests (personnel_id, vehicle_id, request_date, usage_purpose, planned_start_at, planned_end_at, description, status, admin_note, approved_by, assignment_id) VALUES
(1, 1, '2026-04-19 14:10:00', 'Resmi kurum ziyareti', '2026-04-24 09:00:00', '2026-04-24 17:30:00', 'Aynı gün dönüş planlanmaktadır.', 'bekliyor', NULL, NULL, NULL),
(1, 2, '2026-04-10 11:20:00', 'Evrak teslimi', '2026-04-20 08:30:00', '2026-04-22 18:00:00', 'Merkez ve ilçe müdürlükleri arası belge teslimi.', 'onaylandı', 'Talep uygun bulundu, araç tahsis edildi.', 1, 1),
(1, 3, '2026-04-05 10:00:00', 'Teknik saha kontrolü', '2026-04-12 08:00:00', '2026-04-12 18:00:00', 'Araç bakımda olduğu için alternatif talep edilecek.', 'reddedildi', 'Araç bakımda olduğundan talep reddedildi.', 1, NULL);

INSERT INTO fuel_logs (vehicle_id, personnel_id, purchase_date, litre, amount, odometer_km, station_name, receipt_note) VALUES
(2, 1, '2026-04-21', 22.50, 954.20, 45010, 'K-Pet Lefkoşa', 'Aktif görev sırasında personel tarafından girildi.'),
(1, NULL, '2026-04-03', 64.50, 2848.25, 67620, 'Petrol Ofisi Gazimağusa', 'Kurumsal kart ile ödeme yapıldı.'),
(2, NULL, '2026-04-12', 42.10, 1815.40, 44420, 'K-Pet Lefkoşa', 'Şehir içi görev öncesi dolum.'),
(3, NULL, '2026-04-08', 71.00, 3097.80, 97810, 'Alpet Sanayi', 'Teknik sevkiyat öncesi yakıt alımı.');

INSERT INTO maintenance_records (vehicle_id, maintenance_type, last_maintenance_date, next_maintenance_date, next_maintenance_km, service_name, cost, description) VALUES
(1, 'Periyodik bakım', '2026-01-12', '2026-05-12', 72000, 'Yetkili Ford Servisi', 8750.00, 'Motor yağı, filtre ve genel kontroller tamamlandı.'),
(2, 'Yağ ve filtre bakımı', '2025-12-20', '2026-04-28', 47000, 'Fiat Yetkili Servis', 4325.00, 'Bakım tarihi yaklaşıyor.'),
(3, 'Fren ve balata kontrolü', '2025-11-10', '2026-04-18', 99000, 'Sanayi Teknik Servis', 12540.00, 'Bakım tarihi geçti, kırmızı uyarı gerektirir.'),
(4, 'İlk bakım', '2026-02-05', '2026-08-05', 25000, 'Renault Mais', 3650.00, 'Araç yeni olduğundan planlı bakım kaydı.');

INSERT INTO expense_records (vehicle_id, expense_type, expense_date, amount, service_name, parts_changed, description) VALUES
(3, 'arıza', '2026-04-16', 6450.00, 'Sanayi Teknik Servis', 'Turbo hortumu', 'Arıza lambası uyarısı sonrası parça değişimi yapıldı.'),
(1, 'parça değişimi', '2026-03-11', 2380.00, 'Ford Yedek Parça Merkezi', 'Ön silecek takımı', 'Yoğun kullanım nedeniyle silecek seti değiştirildi.'),
(2, 'onarım', '2026-02-21', 4120.00, 'Merkez Oto Servis', 'Sağ arka kapı kilidi', 'Kapı kilit mekanizması onarıldı.');

INSERT INTO issue_reports (vehicle_id, personnel_id, assignment_id, subject, description, report_date, urgency, status, admin_note) VALUES
(2, 1, 1, 'Fren sesi', 'Düşük hızda frenleme sırasında hafif ses geliyor.', '2026-04-21', 'orta', 'inceleniyor', 'Servis kontrolü planlandı.'),
(2, 1, 1, 'Sağ far zayıf', 'Gece kullanımında sağ far ışığı zayıf.', '2026-04-20', 'düşük', 'açık', NULL);

INSERT INTO announcements (title, content, target_role, is_active, created_by) VALUES
('Yakıt Fişi Zorunluluğu', 'Tüm personel yakıt alımlarında fiş bilgisini eksiksiz girmelidir.', 'personel', 1, 1),
('Bakım Süreç Güncellemesi', 'Bakımı yaklaşan araçlar için sistem uyarılarını günlük takip ediniz.', 'tümü', 1, 1);

INSERT INTO activity_logs (user_id, module_name, action_name, details, ip_address, created_at) VALUES
(1, 'Araç Yönetimi', 'Araç eklendi', '35 GH 3456 plakalı araç sisteme eklendi.', '127.0.0.1', NOW() - INTERVAL 3 DAY),
(1, 'Bakım Takibi', 'Bakım kaydı eklendi', '07 EF 9012 için bakım kaydı girildi.', '127.0.0.1', NOW() - INTERVAL 2 DAY),
(2, 'Araç Zimmeti', 'Araç teslim alındı', '06 CD 5678, Ahmet Çelik üzerine zimmetlendi.', '127.0.0.1', NOW() - INTERVAL 1 DAY);
INSERT INTO backup_schedules (id, frequency, is_active, next_run_at, last_status)
VALUES (1, 'daily', 1, DATE_ADD(NOW(), INTERVAL 1 DAY), 'pending');
