<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$defaultRole = (string) ($_GET['role'] ?? 'kasiyer');
$errors = [];

$user = [
    'full_name' => '',
    'username' => '',
    'role' => in_array($defaultRole, ['admin', 'kasiyer'], true) ? $defaultRole : 'kasiyer',
    'is_active' => 1,
];

if ($id > 0) {
    $found = fetch_one('SELECT * FROM users WHERE id = :id LIMIT 1', ['id' => $id]);
    if (!$found) {
        set_flash('error', 'Kullanıcı bulunamadı.');
        redirect('modules/users/index.php');
    }
    $user = $found;
}

if (is_post()) {
    verify_csrf();
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $role = (string) ($_POST['role'] ?? 'kasiyer');
    $password = (string) ($_POST['password'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($fullName === '' || $username === '') {
        $errors[] = 'Ad soyad ve kullanıcı adı zorunludur.';
    }
    if (!in_array($role, ['admin', 'kasiyer'], true)) {
        $errors[] = 'Rol seçimi geçersiz.';
    }
    if ($id === 0 && $password === '') {
        $errors[] = 'Yeni kullanıcı için şifre zorunludur.';
    }

    $existing = fetch_one(
        'SELECT id FROM users WHERE username = :username AND id != :id LIMIT 1',
        ['username' => $username, 'id' => $id]
    );
    if ($existing) {
        $errors[] = 'Bu kullanıcı adı zaten kullanılıyor.';
    }

    if (!$errors) {
        if ($id > 0) {
            execute_query(
                'UPDATE users SET full_name=:full_name, username=:username, role=:role, is_active=:is_active WHERE id=:id',
                [
                    'full_name' => $fullName,
                    'username' => $username,
                    'role' => $role,
                    'is_active' => $isActive,
                    'id' => $id,
                ]
            );
            if ($password !== '') {
                execute_query(
                    'UPDATE users SET password_hash = :password_hash WHERE id = :id',
                    ['password_hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $id]
                );
            }
            set_flash('success', 'Kullanıcı güncellendi.');
        } else {
            execute_query(
                'INSERT INTO users (full_name, username, password_hash, role, is_active, created_at)
                 VALUES (:full_name, :username, :password_hash, :role, :is_active, NOW())',
                [
                    'full_name' => $fullName,
                    'username' => $username,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => $role,
                    'is_active' => $isActive,
                ]
            );
            set_flash('success', 'Kullanıcı eklendi.');
        }
        redirect('modules/users/index.php' . ($role === 'kasiyer' ? '?role=kasiyer' : ''));
    }

    $user['full_name'] = $fullName;
    $user['username'] = $username;
    $user['role'] = $role;
    $user['is_active'] = $isActive;
}

$activeMenu = ((string) ($user['role'] ?? '') === 'kasiyer') ? 'cashiers' : 'users';
$pageTitle = $id > 0 ? 'Kullanıcı Düzenle' : 'Yeni Kullanıcı';
require __DIR__ . '/../../includes/header.php';
?>
<section class="card">
    <h3><?= e($pageTitle) ?></h3>
    <?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
    <form method="post" class="form grid-2">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Ad Soyad
            <input type="text" name="full_name" value="<?= e((string) ($user['full_name'] ?? '')) ?>" required>
        </label>
        <label>Kullanıcı Adı
            <input type="text" name="username" value="<?= e((string) ($user['username'] ?? '')) ?>" required>
        </label>
        <label>Rol
            <select name="role" required>
                <option value="admin" <?= (($user['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
                <option value="kasiyer" <?= (($user['role'] ?? '') === 'kasiyer') ? 'selected' : '' ?>>Kasiyer</option>
            </select>
        </label>
        <label>Şifre <?= $id > 0 ? '(değişmeyecekse boş bırakın)' : '' ?>
            <input type="password" name="password" <?= $id > 0 ? '' : 'required' ?>>
        </label>
        <label><input type="checkbox" name="is_active" value="1" <?= ((int) ($user['is_active'] ?? 1) === 1) ? 'checked' : '' ?>> Kullanıcı aktif</label>
        <div class="full actions">
            <button class="btn primary" type="submit">Kaydet</button>
            <a class="btn ghost" href="<?= e(app_url('modules/users/index.php')) ?>">Listeye Dön</a>
        </div>
    </form>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
