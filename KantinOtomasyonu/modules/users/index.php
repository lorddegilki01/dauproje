<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$roleFilter = (string) ($_GET['role'] ?? '');
$activeMenu = $roleFilter === 'kasiyer' ? 'cashiers' : 'users';
$pageTitle = $roleFilter === 'kasiyer' ? 'Kasiyer Yönetimi' : 'Kullanıcı Yönetimi';

$where = '1=1';
$params = [];
if (in_array($roleFilter, ['admin', 'kasiyer'], true)) {
    $where .= ' AND role = :role';
    $params['role'] = $roleFilter;
}

$users = fetch_all(
    "SELECT id, full_name, username, role, is_active, created_at
     FROM users
     WHERE {$where}
     ORDER BY created_at DESC",
    $params
);

require __DIR__ . '/../../includes/header.php';
?>
<section class="card">
    <div class="card-head">
        <h3><?= e($pageTitle) ?></h3>
        <a class="btn primary" href="<?= e(app_url('modules/users/form.php' . ($roleFilter ? '?role=' . urlencode($roleFilter) : ''))) ?>">Yeni Kullanıcı</a>
    </div>
    <form method="get" class="form inline">
        <label>Rol
            <select name="role">
                <option value="">Tümü</option>
                <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="kasiyer" <?= $roleFilter === 'kasiyer' ? 'selected' : '' ?>>Kasiyer</option>
            </select>
        </label>
        <button class="btn ghost" type="submit">Filtrele</button>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Ad Soyad</th><th>Kullanıcı Adı</th><th>Rol</th><th>Durum</th><th>Kayıt Tarihi</th><th>İşlem</th></tr></thead>
            <tbody>
            <?php if (!$users): ?><tr><td colspan="6">Kullanıcı bulunamadı.</td></tr><?php endif; ?>
            <?php foreach ($users as $row): ?>
                <tr>
                    <td><?= e((string) $row['full_name']) ?></td>
                    <td><?= e((string) $row['username']) ?></td>
                    <td><span class="badge neutral"><?= e(mb_strtoupper((string) $row['role'], 'UTF-8')) ?></span></td>
                    <td><span class="<?= e((int) $row['is_active'] === 1 ? 'badge success' : 'badge danger') ?>"><?= e((int) $row['is_active'] === 1 ? 'Aktif' : 'Pasif') ?></span></td>
                    <td><?= e(format_date((string) $row['created_at'])) ?></td>
                    <td class="actions">
                        <a href="<?= e(app_url('modules/users/form.php?id=' . (int) $row['id'])) ?>">Düzenle</a>
                        <?php if ((int) $row['id'] !== (int) current_user()['id']): ?>
                        <form method="post" action="<?= e(app_url('modules/users/delete.php')) ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
                            <button class="btn-link danger" type="submit" data-confirm="Kullanıcı silinsin mi?">Sil</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
