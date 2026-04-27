<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$activeMenu = 'profile';
$pageTitle = 'Profilim';
$errors = [];
$userId = (int) current_user()['id'];
$user = fetch_one('SELECT * FROM users WHERE id = :id LIMIT 1', ['id' => $userId]);

if (!$user) {
    logout_user();
    redirect('auth/login.php');
}

if (is_post()) {
    verify_csrf();
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));

    if ($fullName === '' || $username === '') {
        $errors[] = 'Ad soyad ve kullanıcı adı zorunludur.';
    }

    $existing = fetch_one(
        'SELECT id FROM users WHERE username = :username AND id != :id LIMIT 1',
        ['username' => $username, 'id' => $userId]
    );
    if ($existing) {
        $errors[] = 'Bu kullanıcı adı başka bir hesapta kullanılıyor.';
    }

    if (!$errors) {
        execute_query(
            'UPDATE users SET full_name = :full_name, username = :username WHERE id = :id',
            ['full_name' => $fullName, 'username' => $username, 'id' => $userId]
        );
        $user = fetch_one('SELECT * FROM users WHERE id = :id LIMIT 1', ['id' => $userId]) ?? $user;
        update_session_user($user);
        set_flash('success', 'Profil bilgileriniz güncellendi.');
        redirect('modules/profile/index.php');
    }

    $user['full_name'] = $fullName;
    $user['username'] = $username;
}

$isActive = (int) ($user['is_active'] ?? 1);

require __DIR__ . '/../../includes/header.php';
?>
<section class="card">
    <h3>Profil Bilgileri</h3>
    <?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
    <form method="post" class="form grid-2">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Ad Soyad
            <input type="text" name="full_name" value="<?= e((string) $user['full_name']) ?>" required>
        </label>
        <label>Kullanıcı Adı
            <input type="text" name="username" value="<?= e((string) $user['username']) ?>" required>
        </label>
        <label>Rol
            <input type="text" value="<?= e(mb_strtoupper((string) ($user['role'] ?? 'kasiyer'), 'UTF-8')) ?>" disabled>
        </label>
        <label>Durum
            <input type="text" value="<?= e($isActive === 1 ? 'Aktif' : 'Pasif') ?>" disabled>
        </label>
        <div class="full actions">
            <button class="btn primary" type="submit">Güncelle</button>
            <a class="btn ghost" href="<?= e(app_url('modules/profile/password.php')) ?>">Şifre Değiştir</a>
        </div>
    </form>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
