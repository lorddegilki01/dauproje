<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
$personnel = require_personnel_profile();
$user = current_user();

$pageTitle = 'Profilim';
$errors = [];

if (is_post()) {
    verify_csrf();

    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if ($fullName === '' || $email === '') {
        $errors[] = 'Ad soyad ve e-posta zorunludur.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir e-posta adresi giriniz.';
    }
    if ($password !== '' && mb_strlen($password, 'UTF-8') < 8) {
        $errors[] = 'Yeni şifre en az 8 karakter olmalıdır.';
    }
    if ($password !== '' && $password !== $passwordConfirm) {
        $errors[] = 'Şifre tekrarı eşleşmiyor.';
    }

    $duplicate = fetch_one(
        'SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1',
        ['email' => $email, 'id' => (int) $user['id']]
    );
    if ($duplicate) {
        $errors[] = 'Bu e-posta başka bir kullanıcı tarafından kullanılıyor.';
    }

    if (!$errors) {
        execute_query(
            'UPDATE users SET full_name = :full_name, email = :email, phone = :phone WHERE id = :id',
            [
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'id' => (int) $user['id'],
            ]
        );
        execute_query(
            'UPDATE personnel SET full_name = :full_name, email = :email, phone = :phone WHERE id = :id',
            [
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'id' => (int) $personnel['id'],
            ]
        );

        if ($password !== '') {
            execute_query(
                'UPDATE users SET password_hash = :password_hash WHERE id = :id',
                [
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'id' => (int) $user['id'],
                ]
            );
        }

        $_SESSION['user']['full_name'] = $fullName;
        log_activity('Profil güncellendi', 'Personel Profil', 'Personel profil bilgilerini güncelledi.');
        set_flash('success', 'Profil bilgileriniz güncellendi.');
        redirect('personnel/profile.php');
    }
}

$profile = fetch_one('SELECT id, full_name, email, phone FROM users WHERE id = :id', ['id' => (int) $user['id']]);

include __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <div class="panel-head"><h2>Profil Bilgilerim</h2></div>
    <div class="panel-body">
        <?php foreach ($errors as $error): ?>
            <div class="alert error"><span><?= e($error) ?></span></div>
        <?php endforeach; ?>
        <form accept-charset="UTF-8" class="form-grid" method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>Ad Soyad
                <input type="text" name="full_name" value="<?= e($_POST['full_name'] ?? $profile['full_name'] ?? '') ?>" required>
            </label>
            <label>E-posta
                <input type="email" name="email" value="<?= e($_POST['email'] ?? $profile['email'] ?? '') ?>" required>
            </label>
            <label>Telefon
                <input type="text" name="phone" value="<?= e($_POST['phone'] ?? $profile['phone'] ?? '') ?>">
            </label>
            <label>Kullanıcı Adı
                <input type="text" value="<?= e((string) $user['username']) ?>" disabled>
            </label>
            <label>Yeni Şifre
                <input type="password" name="password" maxlength="255">
            </label>
            <label>Yeni Şifre (Tekrar)
                <input type="password" name="password_confirm" maxlength="255">
            </label>
            <div class="form-actions full-width">
                <button class="button primary" type="submit">Kaydet</button>
            </div>
        </form>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
