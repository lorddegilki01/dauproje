# Askıda Kitap Platformu

Askıda Kitap Platformu; kitap bağışçıları ile ihtiyaç sahibi okuyucuları bir araya getiren, PHP + MySQL tabanlı tam çalışan bir dayanışma uygulamasıdır.

## Özellikler

- Kullanıcı kayıt / giriş / çıkış sistemi (session tabanlı)
- Rol yapısı: `admin`, `kullanici`, `bagisci`, `talep_sahibi`
- Kitap bağış CRUD (ekle, düzenle, sil, listele, detay)
- Askıdaki kitaplar için arama, kategori ve şehir filtreleme
- Kitap talep gönderme, iptal, onay / red yönetimi
- Onaylanan talepte otomatik eşleşme kaydı
- Teslim süreci takibi (`bekliyor`, `teslim edildi`, `iptal`)
- Admin yönetim paneli
  - kullanıcı yönetimi
  - kategori yönetimi
  - talep/eşleşme yönetimi
  - raporlama
  - iletişim mesajları takibi
- Bildirim listesi (global + kullanıcı bazlı)
- Profil ve şifre güncelleme
- Responsive arayüz

## Kurulum

1. Projeyi şu klasörde çalıştırın:
   - `C:\xampp\htdocs\DaüYarışma\AskıdaKitapPlatformu`
2. XAMPP üzerinden `Apache` ve `MySQL` servislerini başlatın.
3. Veritabanını içe aktarın:
   - `http://localhost/phpmyadmin`
   - `database/askida_kitap.sql` dosyasını import edin.
4. Gerekirse `config/config.php` içindeki DB ayarlarını güncelleyin:
   - `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
5. Uygulamayı açın:
   - `http://localhost/DaüYarışma/AskıdaKitapPlatformu/index.php`

## Demo Hesaplar

- Admin: `admin / password`
- Bağışçı: `bagisci / password`
- Talep Sahibi: `okur / password`

## Güvenlik Notları

- PDO prepared statements ile SQL injection riski azaltıldı.
- `htmlspecialchars` ile XSS’ye karşı çıktı kaçışlaması uygulanıyor.
- Session timeout ve `session_regenerate_id` kullanılıyor.
- CSRF token doğrulaması form gönderimlerinde aktif.
- Rol bazlı erişim kontrolü (`require_login`, `require_admin`, `require_role`) mevcut.

## Test Senaryoları

1. **Kayıt / giriş akışı**
   - Yeni kullanıcı oluştur.
   - Giriş yap.
   - Profilde şifre güncelle.
2. **Kitap bağışı**
   - Giriş yaptıktan sonra yeni kitap ekle.
   - Kitap düzenle ve listede doğrula.
3. **Talep süreci**
   - Farklı kullanıcı ile askıdaki kitaba talep gönder.
   - Bağışçı hesabıyla talebi onayla.
   - Eşleşme kaydının oluştuğunu doğrula.
4. **Teslim süreci**
   - Eşleşmede “Teslim Edildi” işlemi yap.
   - Kitap durumunun `teslim edildi` olduğuna bak.
5. **Admin modülleri**
   - Kullanıcı pasif/aktif durumu değiştir.
   - Kategori ekle/pasifleştir.
   - Rapor filtresini tarih aralığıyla çalıştır.
6. **İletişim**
   - İletişim formundan mesaj gönder.
   - Admin panelinde mesajı gör ve durumunu değiştir.

