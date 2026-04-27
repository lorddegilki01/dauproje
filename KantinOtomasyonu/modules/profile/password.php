<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$activeMenu = 'password';
$pageTitle = 'Şifre Değiştir';
$errors = [];
$userId = (int) current_user()['id'];

if (is_post()) {
    verify_csrf();
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $newPasswordAgain = (string) ($_POST['new_password_again'] ?? '');

    $user = fetch_one('SELECT * FROM users WHERE id = :id LIMIT 1', ['id' => $userId]);
    if (!$user) {
        $errors[] = 'Kullanıcı bulunamadı.';
    } else {
        if (!password_verify($currentPassword, (string) $user['password_hash'])) {
            $errors[] = 'Mevcut şifre yanlış.';
        }
        if (mb_strlen($newPassword, 'UTF-8') < 6) {
            $errors[] = 'Yeni şifre en az 6 karakter olmalıdır.';
        }
        if ($newPassword !== $newPasswordAgain) {
            $errors[] = 'Yeni şifreler eşleşmiyor.';
        }
    }

    if (!$errors) {
        execute_query(
            'UPDATE users SET password_hash = :password_hash WHERE id = :id',
            ['password_hash' => password_hash($newPassword, PASSWORD_DEFAULT), 'id' => $userId]
        );
        set_flash('success', 'Şifreniz başarıyla değiştirildi.');
        redirect('modules/profile/password.php');
    }
}

require __DIR__ . '/../../includes/header.php';
?>
<section class="card">
    <h3>Şifre Değiştir</h3>
    <?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
    <form method="post" class="form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Mevcut Şifre
            <input type="password" name="current_password" required>
        </label>
        <label>Yeni Şifre
            <input type="password" name="new_password" required>
        </label>
        <label>Yeni Şifre (Tekrar)
            <input type="password" name="new_password_again" required>
        </label>
        <div class="actions">
            <button class="btn primary" type="submit">Şifreyi Güncelle</button>
            <a class="btn ghost" href="<?= e(app_url('modules/profile/index.php')) ?>">Profile Dön</a>
        </div>
    </form>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
