<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$activeMenu = '';
$pageTitle = 'Kullanıcı Yönetimi';
$errors = [];

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        $user = fetch_one('SELECT id, is_active FROM users WHERE id = :id', ['id' => $id]);
        if ($user) {
            execute_query('UPDATE users SET is_active = :is_active, updated_at = NOW() WHERE id = :id', [
                'is_active' => ((int) $user['is_active'] === 1) ? 0 : 1,
                'id' => $id,
            ]);
            set_flash('success', 'Kullanıcı durumu güncellendi.');
            redirect('admin/users.php');
        }
    }

    if ($action === 'create') {
        $fullName = normalize_text((string) ($_POST['full_name'] ?? ''));
        $username = normalize_text((string) ($_POST['username'] ?? ''));
        $email = mb_strtolower(normalize_text((string) ($_POST['email'] ?? '')), 'UTF-8');
        $role = (string) ($_POST['role'] ?? 'kullanici');
        $city = normalize_text((string) ($_POST['city'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($fullName === '' || $username === '' || $email === '' || $city === '' || $password === '') {
            $errors[] = 'Tüm zorunlu alanlar doldurulmalıdır.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-posta formatı geçersiz.';
        }
        if (mb_strlen($password, 'UTF-8') < 8) {
            $errors[] = 'Şifre en az 8 karakter olmalıdır.';
        }
        if (!in_array($role, ['admin', 'kullanici', 'bagisci', 'talep_sahibi'], true)) {
            $errors[] = 'Rol seçimi geçersiz.';
        }
        if (fetch_one('SELECT id FROM users WHERE username = :username', ['username' => $username])) {
            $errors[] = 'Kullanıcı adı kullanımda.';
        }
        if (fetch_one('SELECT id FROM users WHERE email = :email', ['email' => $email])) {
            $errors[] = 'E-posta kullanımda.';
        }

        if (!$errors) {
            execute_query(
                'INSERT INTO users (full_name, username, email, password_hash, role, city, is_active, created_at, updated_at)
                 VALUES (:full_name,:username,:email,:password_hash,:role,:city,1,NOW(),NOW())',
                [
                    'full_name' => $fullName,
                    'username' => $username,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => $role,
                    'city' => $city,
                ]
            );
            set_flash('success', 'Yeni kullanıcı oluşturuldu.');
            redirect('admin/users.php');
        }
    }
}

$users = fetch_all('SELECT * FROM users ORDER BY created_at DESC');
require __DIR__ . '/../includes/header.php';
?>
<section class="card">
    <h2>Yeni Kullanıcı Ekle</h2>
    <?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
    <form method="post" class="form grid-3">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="create">
        <label>Ad Soyad<input type="text" name="full_name" required></label>
        <label>Kullanıcı Adı<input type="text" name="username" required></label>
        <label>E-posta<input type="email" name="email" required></label>
        <label>Rol
            <select name="role">
                <option value="kullanici">Kullanıcı</option>
                <option value="bagisci">Bağışçı</option>
                <option value="talep_sahibi">Talep Sahibi</option>
                <option value="admin">Admin</option>
            </select>
        </label>
        <label>Şehir<input type="text" name="city" required></label>
        <label>Şifre<input type="password" name="password" required></label>
        <div class="full actions"><button class="btn primary" type="submit">Kullanıcı Ekle</button></div>
    </form>
</section>

<section class="card">
    <h2>Kullanıcı Listesi</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Ad Soyad</th>
                    <th>Kullanıcı Adı</th>
                    <th>E-posta</th>
                    <th>Rol</th>
                    <th>Şehir</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= e((string) $u['full_name']) ?></td>
                        <td><?= e((string) $u['username']) ?></td>
                        <td><?= e((string) $u['email']) ?></td>
                        <td><?= e((string) $u['role']) ?></td>
                        <td><?= e((string) $u['city']) ?></td>
                        <td><span class="<?= (int) $u['is_active'] === 1 ? 'badge success' : 'badge danger' ?>"><?= (int) $u['is_active'] === 1 ? 'Aktif' : 'Pasif' ?></span></td>
                        <td>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= e((string) $u['id']) ?>">
                                <button class="btn ghost" type="submit"><?= (int) $u['is_active'] === 1 ? 'Pasifleştir' : 'Aktifleştir' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

