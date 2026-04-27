<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$person = fetch_one(
    'SELECT p.*,
            u.id AS user_ref,
            u.username,
            u.email AS user_email,
            u.phone AS user_phone,
            u.role AS user_role,
            u.is_active AS user_is_active
     FROM personnel p
     LEFT JOIN users u ON u.id = p.user_id
     WHERE p.id = :id
     LIMIT 1',
    ['id' => $id]
);

if (!$person) {
    include __DIR__ . '/../../errors/404.php';
    exit;
}

$pageTitle = 'Personel Detayı';

$usageCount = count_value(
    'SELECT COUNT(*) FROM vehicle_assignments WHERE personnel_id = :personnel_id',
    ['personnel_id' => $id]
);

$activeAssignment = fetch_one(
    "SELECT va.*, v.plate_number, v.brand, v.model
     FROM vehicle_assignments va
     INNER JOIN vehicles v ON v.id = va.vehicle_id
     WHERE va.personnel_id = :personnel_id
       AND va.return_status = 'iade edilmedi'
     ORDER BY va.assigned_at DESC
     LIMIT 1",
    ['personnel_id' => $id]
);

$recentAssignments = fetch_all(
    "SELECT va.assigned_at, va.returned_at, va.start_km, va.end_km, va.return_status, v.plate_number
     FROM vehicle_assignments va
     INNER JOIN vehicles v ON v.id = va.vehicle_id
     WHERE va.personnel_id = :personnel_id
     ORDER BY va.assigned_at DESC
     LIMIT 8",
    ['personnel_id' => $id]
);

include __DIR__ . '/../../includes/header.php';
?>
<section class="panel">
    <div class="panel-head">
        <h2><?= e($person['full_name']) ?></h2>
        <div class="actions">
            <a class="button secondary" href="<?= e(app_url('modules/personnel/form.php?id=' . $person['id'])) ?>">Düzenle</a>
            <a class="button ghost" href="<?= e(app_url('modules/personnel/index.php')) ?>">Listeye Dön</a>
        </div>
    </div>
    <div class="panel-body">
        <div class="details-grid">
            <div><strong>Sicil No:</strong> <?= e($person['registration_no']) ?></div>
            <div><strong>Departman:</strong> <?= e($person['department']) ?></div>
            <div><strong>Görev:</strong> <?= e($person['duty_title']) ?></div>
            <div><strong>Sürücü Belgesi:</strong> <?= e($person['license_class'] ?: '-') ?></div>
            <div><strong>Telefon:</strong> <?= e($person['phone'] ?: '-') ?></div>
            <div><strong>E-posta:</strong> <?= e($person['email'] ?: '-') ?></div>
            <div><strong>Kayıt Durumu:</strong> <span class="<?= e(badge_class($person['status'])) ?>"><?= e($person['status']) ?></span></div>
            <div><strong>Toplam Kullanım:</strong> <?= e((string) $usageCount) ?></div>
        </div>
    </div>
</section>

<section class="panel">
    <div class="panel-head"><h2>Kullanıcı Hesabı</h2></div>
    <div class="panel-body">
        <?php if (!$person['user_ref']): ?>
            <p class="empty-state">Bu personele bağlı bir giriş hesabı yok.</p>
        <?php else: ?>
            <div class="details-grid">
                <div><strong>Kullanıcı Adı:</strong> <?= e($person['username']) ?></div>
                <div><strong>Rol:</strong> <span class="<?= e($person['user_role'] === 'admin' ? 'badge warning' : 'badge neutral') ?>"><?= e($person['user_role']) ?></span></div>
                <div><strong>E-posta:</strong> <?= e($person['user_email']) ?></div>
                <div><strong>Telefon:</strong> <?= e($person['user_phone'] ?: '-') ?></div>
                <div><strong>Hesap Durumu:</strong> <span class="<?= e((int) $person['user_is_active'] === 1 ? 'badge success' : 'badge danger') ?>"><?= e((int) $person['user_is_active'] === 1 ? 'aktif' : 'pasif') ?></span></div>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="panel">
    <div class="panel-head"><h2>Aktif Araç Kullanımı</h2></div>
    <div class="panel-body">
        <?php if (!$activeAssignment): ?>
            <p class="empty-state">Aktif araç zimmeti bulunmuyor.</p>
        <?php else: ?>
            <div class="details-grid">
                <div><strong>Araç:</strong> <?= e($activeAssignment['plate_number']) ?> (<?= e($activeAssignment['brand'] . ' ' . $activeAssignment['model']) ?>)</div>
                <div><strong>Teslim Alma:</strong> <?= e(format_date($activeAssignment['assigned_at'], 'd.m.Y H:i')) ?></div>
                <div><strong>Tahmini Teslim:</strong> <?= e(format_date($activeAssignment['expected_return_at'], 'd.m.Y H:i')) ?></div>
                <div><strong>Başlangıç KM:</strong> <?= e((string) $activeAssignment['start_km']) ?></div>
                <div><strong>Kullanım Amacı:</strong> <?= e($activeAssignment['usage_purpose']) ?></div>
                <div><strong>Durum:</strong> <span class="<?= e(badge_class($activeAssignment['return_status'])) ?>"><?= e($activeAssignment['return_status']) ?></span></div>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="panel">
    <div class="panel-head"><h2>Son Kullanım Geçmişi</h2></div>
    <div class="panel-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Araç</th>
                    <th>Teslim Alma</th>
                    <th>İade</th>
                    <th>KM</th>
                    <th>Durum</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recentAssignments as $item): ?>
                <tr>
                    <td><?= e($item['plate_number']) ?></td>
                    <td><?= e(format_date($item['assigned_at'], 'd.m.Y H:i')) ?></td>
                    <td><?= e(format_date($item['returned_at'], 'd.m.Y H:i')) ?></td>
                    <td>
                        <?= e((string) $item['start_km']) ?>
                        <?php if ($item['end_km'] !== null): ?>
                            - <?= e((string) $item['end_km']) ?>
                        <?php endif; ?>
                    </td>
                    <td><span class="<?= e(badge_class($item['return_status'])) ?>"><?= e($item['return_status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
