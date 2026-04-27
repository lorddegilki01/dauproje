<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];
if (is_post()) {
    verify_csrf();
    $fullName = normalize_text((string) ($_POST['full_name'] ?? ''));
    $username = normalize_text((string) ($_POST['username'] ?? ''));
    $email = mb_strtolower(normalize_text((string) ($_POST['email'] ?? '')), 'UTF-8');
    $password = (string) ($_POST['password'] ?? '');
    $passwordAgain = (string) ($_POST['password_again'] ?? '');
    $role = (string) ($_POST['role'] ?? 'kullanici');
    $city = normalize_text((string) ($_POST['city'] ?? ''));
    $phone = normalize_text((string) ($_POST['phone'] ?? ''));

    if ($fullName === '' || $username === '' || $email === '' || $password === '' || $city === '') {
        $errors[] = 'Tüm zorunlu alanları doldurun.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir e-posta adresi girin.';
    }
    if ($password !== $passwordAgain) {
        $errors[] = 'Şifre alanları eşleşmiyor.';
    }
    if (mb_strlen($password, 'UTF-8') < 8) {
        $errors[] = 'Şifre en az 8 karakter olmalıdır.';
    }
    if (!in_array($role, ['kullanici', 'bagisci', 'talep_sahibi'], true)) {
        $role = 'kullanici';
    }

    if (fetch_one('SELECT id FROM users WHERE username = :username LIMIT 1', ['username' => $username])) {
        $errors[] = 'Bu kullanıcı adı kullanılıyor.';
    }
    if (fetch_one('SELECT id FROM users WHERE email = :email LIMIT 1', ['email' => $email])) {
        $errors[] = 'Bu e-posta adresi kullanılıyor.';
    }

    if (!$errors) {
        execute_query(
            'INSERT INTO users (full_name, username, email, password_hash, role, city, phone, is_active, created_at, updated_at)
             VALUES (:full_name,:username,:email,:password_hash,:role,:city,:phone,1,NOW(),NOW())',
            [
                'full_name' => $fullName,
                'username' => $username,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
                'city' => $city,
                'phone' => $phone,
            ]
        );

        set_flash('success', 'Kayıt başarılı. Şimdi giriş yapabilirsiniz.');
        redirect('auth/login.php');
    }
}

$activeMenu = '';
$pageTitle = 'Kayıt Ol';
require __DIR__ . '/../includes/header.php';
?>
<section class="card form-card">
    <h2>Topluluğa Katılın</h2>
    <?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
    <form method="post" class="form grid-2">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Ad Soyad<input type="text" name="full_name" required value="<?= e((string) ($_POST['full_name'] ?? '')) ?>"></label>
        <label>Kullanıcı Adı<input type="text" name="username" required value="<?= e((string) ($_POST['username'] ?? '')) ?>"></label>
        <label>E-posta<input type="email" name="email" required value="<?= e((string) ($_POST['email'] ?? '')) ?>"></label>
        <label>Şehir<input type="text" name="city" required value="<?= e((string) ($_POST['city'] ?? '')) ?>"></label>
        <label>Telefon<input type="text" name="phone" value="<?= e((string) ($_POST['phone'] ?? '')) ?>"></label>
        <label>Kullanım Amacı
            <select name="role">
                <option value="kullanici">Genel Kullanıcı</option>
                <option value="bagisci">Bağışçı</option>
                <option value="talep_sahibi">Talep Oluşturan</option>
            </select>
        </label>
        <label>Şifre<input type="password" name="password" required></label>
        <label>Şifre (Tekrar)<input type="password" name="password_again" required></label>
        <div class="full actions">
            <button class="btn primary" type="submit">Kayıt Ol</button>
            <a class="btn ghost" href="<?= e(app_url('auth/login.php')) ?>">Girişe Dön</a>
        </div>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

