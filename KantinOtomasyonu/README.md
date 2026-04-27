# Kantin Otomasyon Sistemi

## Kurulum
1. `database/kantin_otomasyon.sql` dosyasını phpMyAdmin üzerinden içe aktarın.
2. `config/config.php` içindeki veritabanı ayarlarını kontrol edin (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).
3. Apache ve MySQL servislerini başlatın (XAMPP).
4. Tarayıcıdan şu adrese gidin:  
   `http://localhost/DaüYarışma/KantinOtomasyonu/auth/login.php`

## Demo Kullanıcılar
- Admin: `admin / password`
- Kasiyer: `kasiyer / password`

## Modüller
- Giriş/çıkış sistemi (session tabanlı, rol yetkisi)
- Dashboard
- Ürün yönetimi
- Kategori yönetimi
- Stok takibi
- Satış ekranı (sepet + satış tamamlama + satış detayı + iptal)
- Gider yönetimi
- Raporlar (gelir-gider, ürün bazlı satış/kâr, kritik stok)
- Bildirim paneli

## Güvenlik
- PDO prepared statements
- CSRF token kontrolü
- XSS için `htmlspecialchars` (`e()` helper)
- Session timeout
- Rol bazlı yetki kontrolü (`require_admin`, `require_roles`)

## Not
- Satış iptali sadece admin tarafından yapılır.
- Satış geçmişi olan ürünler doğrudan silinemez (pasif yapılması önerilir).
