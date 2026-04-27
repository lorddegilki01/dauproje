SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET collation_connection = 'utf8mb4_turkish_ci';

CREATE DATABASE IF NOT EXISTS kantin_otomasyon
CHARACTER SET utf8mb4
COLLATE utf8mb4_turkish_ci;

USE kantin_otomasyon;

DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS sale_items;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'kasiyer') NOT NULL DEFAULT 'kasiyer',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    product_name VARCHAR(140) NOT NULL,
    product_code VARCHAR(50) NOT NULL UNIQUE,
    barcode VARCHAR(80) NULL,
    purchase_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    sale_price DECIMAL(10,2) NOT NULL,
    stock_quantity DECIMAL(10,2) NOT NULL DEFAULT 0,
    critical_level DECIMAL(10,2) NOT NULL DEFAULT 0,
    unit_type VARCHAR(20) NOT NULL DEFAULT 'adet',
    description TEXT NULL,
    status ENUM('aktif', 'pasif') NOT NULL DEFAULT 'aktif',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE sales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_no VARCHAR(30) NOT NULL UNIQUE,
    user_id INT UNSIGNED NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('tamamlandı', 'iptal') NOT NULL DEFAULT 'tamamlandı',
    sale_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sales_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_sales_date (sale_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE sale_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    line_total DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_sale_items_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    CONSTRAINT fk_sale_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE stock_movements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    movement_type ENUM('in', 'out') NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    note VARCHAR(255) NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_stock_movements_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_stock_movements_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_stock_movements_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE expenses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expense_type VARCHAR(80) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    expense_date DATE NOT NULL,
    description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_expense_date (expense_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

INSERT INTO users (full_name, username, password_hash, role, is_active) VALUES
('Sistem Yöneticisi', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1),
('Kantin Kasiyeri', 'kasiyer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kasiyer', 1);

INSERT INTO categories (category_name, description) VALUES
('Atıştırmalık', 'Cips, kraker, bisküvi vb.'),
('İçecek', 'Soğuk ve sıcak içecekler'),
('Sandviç', 'Hazır yiyecek ürünleri');

INSERT INTO products (category_id, product_name, product_code, barcode, purchase_price, sale_price, stock_quantity, critical_level, unit_type, description, status) VALUES
(1, 'Patates Cipsi', 'AT-001', '869000001', 12.50, 20.00, 45, 12, 'adet', 'Klasik boy paket.', 'aktif'),
(2, 'Su 500ml', 'IC-001', '869000002', 3.00, 5.00, 120, 30, 'adet', 'Pet şişe su.', 'aktif'),
(2, 'Meyve Suyu', 'IC-002', '869000003', 8.50, 14.00, 22, 10, 'adet', 'Karışık meyve.', 'aktif'),
(3, 'Kaşarlı Sandviç', 'SN-001', '869000004', 20.00, 35.00, 14, 6, 'adet', 'Günlük taze üretim.', 'aktif'),
(1, 'Çikolatalı Bisküvi', 'AT-002', '869000005', 9.00, 15.00, 9, 10, 'adet', 'Kritik stok örneği.', 'aktif');

INSERT INTO sales (sale_no, user_id, total_amount, status, sale_date, created_at) VALUES
('S202604230101', 2, 75.00, 'tamamlandı', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY),
('S202604230102', 2, 54.00, 'tamamlandı', NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 1 DAY);

INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, line_total) VALUES
(1, 1, 2, 20.00, 40.00),
(1, 2, 3, 5.00, 15.00),
(1, 3, 1, 20.00, 20.00),
(2, 4, 1, 35.00, 35.00),
(2, 2, 2, 5.00, 10.00),
(2, 1, 1, 20.00, 20.00);

INSERT INTO stock_movements (product_id, movement_type, quantity, note, user_id, created_at) VALUES
(1, 'in', 50, 'Açılış stoğu', 1, NOW() - INTERVAL 8 DAY),
(2, 'in', 150, 'Açılış stoğu', 1, NOW() - INTERVAL 8 DAY),
(3, 'in', 40, 'Açılış stoğu', 1, NOW() - INTERVAL 8 DAY),
(4, 'in', 20, 'Açılış stoğu', 1, NOW() - INTERVAL 8 DAY),
(5, 'in', 20, 'Açılış stoğu', 1, NOW() - INTERVAL 8 DAY),
(5, 'out', 11, 'Satışlar nedeniyle düşüş', 2, NOW() - INTERVAL 1 DAY);

INSERT INTO expenses (expense_type, amount, expense_date, description, created_at) VALUES
('Elektrik', 1200.00, CURDATE() - INTERVAL 5 DAY, 'Aylık elektrik faturası', NOW() - INTERVAL 5 DAY),
('Su', 380.00, CURDATE() - INTERVAL 4 DAY, 'Aylık su faturası', NOW() - INTERVAL 4 DAY),
('Malzeme', 975.50, CURDATE() - INTERVAL 2 DAY, 'Temizlik ve sarf malzeme alımı', NOW() - INTERVAL 2 DAY);
