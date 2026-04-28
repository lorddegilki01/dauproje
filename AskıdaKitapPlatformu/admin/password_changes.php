<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$activeMenu = 'password_changes';
$pageTitle = 'Şifre Değişiklik Geçmişi';

$q = normalize_text((string) ($_GET['q'] ?? ''));
$type = (string) ($_GET['type'] ?? '');
$start = (string) ($_GET['start'] ?? '');
$end = (string) ($_GET['end'] ?? '');

$sql = 'SELECT pcl.*, u.username, u.full_name
        FROM password_change_logs pcl
        INNER JOIN users u ON u.id = pcl.user_id
        WHERE 1=1';
$params = [];

if ($q !== '') {
    $sql .= ' AND (u.username LIKE :q OR u.full_name LIKE :q)';
    $params['q'] = '%' . $q . '%';
}
if (in_array($type, ['profil', 'sifremi_unuttum', 'admin'], true)) {
    $sql .= ' AND pcl.change_type = :change_type';
    $params['change_type'] = $type;
}
if ($start !== '') {
    $sql .= ' AND pcl.created_at >= :start_date';
    $params['start_date'] = $start . ' 00:00:00';
}
if ($end !== '') {
    $sql .= ' AND pcl.created_at <= :end_date';
    $params['end_date'] = $end . ' 23:59:59';
}
$sql .= ' ORDER BY pcl.created_at DESC LIMIT 250';
$rows = fetch_all($sql, $params);

require __DIR__ . '/../includes/header.php';
?>
<section class="card">
    <div class="card-head">
        <h2>Şifre Değişiklik Geçmişi</h2>
    </div>
    <form method="get" class="form grid-4">
        <label>Arama
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="Kullanıcı adı veya ad soyad">
        </label>
        <label>İşlem Tipi
            <select name="type">
                <option value="">Tümü</option>
                <option value="profil" <?= $type === 'profil' ? 'selected' : '' ?>>Profil</option>
                <option value="sifremi_unuttum" <?= $type === 'sifremi_unuttum' ? 'selected' : '' ?>>Şifremi Unuttum</option>
                <option value="admin" <?= $type === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
        </label>
        <label>Başlangıç
            <input type="date" name="start" value="<?= e($start) ?>">
        </label>
        <label>Bitiş
            <input type="date" name="end" value="<?= e($end) ?>">
        </label>
        <div class="full actions">
            <button class="btn primary" type="submit">Filtrele</button>
            <a class="btn ghost" href="<?= e(app_url('admin/password_changes.php')) ?>">Temizle</a>
        </div>
    </form>
</section>

<section class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr>
                <th>Tarih</th>
                <th>Kullanıcı</th>
                <th>İşlem Tipi</th>
                <th>Durum</th>
                <th>IP</th>
                <th>Detay</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="6">Kayıt bulunamadı.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e(format_date((string) $row['created_at'])) ?></td>
                    <td><?= e((string) $row['full_name']) ?> <small class="muted">(<?= e((string) $row['username']) ?>)</small></td>
                    <td><?= e((string) $row['change_type']) ?></td>
                    <td><span class="<?= e((string) $row['change_status'] === 'basarili' ? 'badge success' : 'badge danger') ?>"><?= e((string) $row['change_status']) ?></span></td>
                    <td><?= e((string) ($row['ip_address'] ?? '-')) ?></td>
                    <td><?= e((string) ($row['details'] ?? '-')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
