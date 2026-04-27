<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$pageTitle = 'Kullanıcı Yönetimi';
$errors = [];

if (is_post()) {
    verify_csrf();

    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'toggle_status') {
        if ($id === (int) current_user()['id']) {
            $errors[] = 'Kendi hesabınızı pasife alamazsınız.';
        } else {
            execute_query('UPDATE users SET is_active = IF(is_active = 1, 0, 1) WHERE id = :id', ['id' => $id]);
            set_flash('success', 'Kullanıcı durumu güncellendi.');
            redirect('modules/users/index.php');
        }
    }

    if ($action === 'save_user') {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $role = trim((string) ($_POST['role'] ?? 'personel'));
        $password = (string) ($_POST['password'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($fullName === '' || $username === '' || $email === '') {
            $errors[] = 'Ad soyad, kullanıcı adı ve e-posta zorunludur.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Geçerli bir e-posta giriniz.';
        }
        if ($id === 0 && mb_strlen($password, 'UTF-8') < 8) {
            $errors[] = 'Yeni kullanıcı için şifre en az 8 karakter olmalıdır.';
        }

        $dupUser = fetch_one('SELECT id FROM users WHERE username = :username AND id != :id LIMIT 1', ['username' => $username, 'id' => $id]);
        $dupEmail = fetch_one('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1', ['email' => $email, 'id' => $id]);
        if ($dupUser) {
            $errors[] = 'Bu kullanıcı adı zaten kullanılıyor.';
        }
        if ($dupEmail) {
            $errors[] = 'Bu e-posta adresi zaten kullanılıyor.';
        }

        if (!$errors) {
            if ($id > 0) {
                execute_query(
                    'UPDATE users SET full_name = :full_name, username = :username, email = :email, phone = :phone, role = :role, is_active = :is_active WHERE id = :id',
                    [
                        'full_name' => $fullName,
                        'username' => $username,
                        'email' => $email,
                        'phone' => $phone,
                        'role' => $role,
                        'is_active' => $isActive,
                        'id' => $id,
                    ]
                );
                if ($password !== '') {
                    execute_query('UPDATE users SET password_hash = :hash WHERE id = :id', [
                        'hash' => password_hash($password, PASSWORD_DEFAULT),
                        'id' => $id,
                    ]);
                }
                set_flash('success', 'Kullanıcı güncellendi.');
            } else {
                execute_query(
                    'INSERT INTO users (full_name, username, email, phone, password_hash, role, is_active)
                     VALUES (:full_name, :username, :email, :phone, :password_hash, :role, :is_active)',
                    [
                        'full_name' => $fullName,
                        'username' => $username,
                        'email' => $email,
                        'phone' => $phone,
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'role' => $role,
                        'is_active' => $isActive,
                    ]
                );
                set_flash('success', 'Yeni kullanıcı oluşturuldu.');
            }
            redirect('modules/users/index.php');
        }
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
$editUser = $editId > 0 ? fetch_one('SELECT * FROM users WHERE id = :id', ['id' => $editId]) : null;

$rows = fetch_all('SELECT id, full_name, username, email, phone, role, is_active, created_at FROM users ORDER BY created_at DESC');

include __DIR__ . '/../../includes/header.php';
?>
<section class="panel">
    <div class="panel-head"><h2><?= e($editUser ? 'Kullanıcı Düzenle' : 'Yeni Kullanıcı Oluştur') ?></h2></div>
    <div class="panel-body">
        <?php foreach ($errors as $error): ?>
            <div class="alert error"><span><?= e($error) ?></span></div>
        <?php endforeach; ?>
        <form accept-charset="UTF-8" class="form-grid" method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save_user">
            <input type="hidden" name="id" value="<?= e((string) ($editUser['id'] ?? 0)) ?>">

            <label>Ad Soyad
                <input type="text" name="full_name" value="<?= e($_POST['full_name'] ?? $editUser['full_name'] ?? '') ?>" required>
            </label>
            <label>Kullanıcı Adı
                <input type="text" name="username" value="<?= e($_POST['username'] ?? $editUser['username'] ?? '') ?>" required>
            </label>
            <label>E-posta
                <input type="email" name="email" value="<?= e($_POST['email'] ?? $editUser['email'] ?? '') ?>" required>
            </label>
            <label>Telefon
                <input type="text" name="phone" value="<?= e($_POST['phone'] ?? $editUser['phone'] ?? '') ?>">
            </label>
            <label>Rol
                <select name="role" required>
                    <?php $selectedRole = $_POST['role'] ?? $editUser['role'] ?? 'personel'; ?>
                    <option value="admin" <?= e($selectedRole === 'admin' ? 'selected' : '') ?>>Admin</option>
                    <option value="personel" <?= e($selectedRole === 'personel' ? 'selected' : '') ?>>Personel</option>
                </select>
            </label>
            <label>Şifre <?= e($editUser ? '(değişmeyecekse boş bırakın)' : '') ?>
                <input type="password" name="password">
            </label>
            <label>
                <input type="checkbox" name="is_active" value="1" <?= e((($_POST['is_active'] ?? ($editUser['is_active'] ?? 1)) ? 'checked' : '')) ?>>
                Aktif
            </label>
            <div class="form-actions full-width">
                <button class="button primary" type="submit">Kaydet</button>
                <a class="button ghost" href="<?= e(app_url('modules/users/index.php')) ?>">Temizle</a>
            </div>
        </form>
    </div>
</section>

<section class="panel">
    <div class="panel-head"><h2>Kullanıcı Listesi</h2></div>
    <div class="panel-body">
        <table class="table">
            <thead><tr><th>Ad Soyad</th><th>Kullanıcı Adı</th><th>E-posta</th><th>Rol</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['full_name']) ?></td>
                    <td><?= e($row['username']) ?></td>
                    <td><?= e($row['email']) ?></td>
                    <td><span class="<?= e($row['role'] === 'admin' ? 'badge warning' : 'badge neutral') ?>"><?= e($row['role']) ?></span></td>
                    <td><span class="<?= e($row['is_active'] ? 'badge success' : 'badge danger') ?>"><?= e($row['is_active'] ? 'aktif' : 'pasif') ?></span></td>
                    <td class="actions">
                        <a href="<?= e(app_url('modules/users/index.php?edit=' . $row['id'])) ?>">Düzenle</a>
                        <?php if ((int) $row['id'] !== (int) current_user()['id']): ?>
                            <form accept-charset="UTF-8" method="post" class="inline-form">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
                                <button class="link-button" type="submit"><?= e($row['is_active'] ? 'Pasifleştir' : 'Aktifleştir') ?></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
