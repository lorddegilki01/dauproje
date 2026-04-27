<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$vehicle = fetch_one('SELECT * FROM vehicles WHERE id = :id', ['id' => $id]);

if (!$vehicle) {
    include __DIR__ . '/../../errors/404.php';
    exit;
}

$pageTitle = $vehicle['plate_number'] . ' Araç Detayı';
$canManage = is_admin();
$assignments = fetch_all(
    'SELECT va.*, p.full_name FROM vehicle_assignments va
     INNER JOIN personnel p ON p.id = va.personnel_id
     WHERE va.vehicle_id = :id ORDER BY va.assigned_at DESC',
    ['id' => $id]
);
$fuelLogs = fetch_all('SELECT * FROM fuel_logs WHERE vehicle_id = :id ORDER BY purchase_date DESC LIMIT 5', ['id' => $id]);
$maintenanceLogs = fetch_all('SELECT * FROM maintenance_records WHERE vehicle_id = :id ORDER BY next_maintenance_date DESC LIMIT 5', ['id' => $id]);
include __DIR__ . '/../../includes/header.php';
?>
<section class="detail-grid">
    <article class="panel">
        <div class="panel-head">
            <h2>Temel Bilgiler</h2>
            <?php if ($canManage): ?>
                <a class="button secondary" href="<?= e(app_url('modules/vehicles/form.php?id=' . $id)) ?>">Düzenle</a>
            <?php endif; ?>
        </div>
        <div class="panel-body info-list">
            <div><span>Plaka</span><strong><?= e($vehicle['plate_number']) ?></strong></div>
            <div><span>Marka / Model</span><strong><?= e($vehicle['brand'] . ' ' . $vehicle['model']) ?></strong></div>
            <div><span>Araç Tipi</span><strong><?= e($vehicle['vehicle_type']) ?></strong></div>
            <div><span>Yakıt Türü</span><strong><?= e($vehicle['fuel_type']) ?></strong></div>
            <div><span>Durum</span><strong><span class="<?= e(badge_class($vehicle['status'])) ?>"><?= e($vehicle['status']) ?></span></strong></div>
            <div><span>Kilometre</span><strong><?= e(number_format((int) $vehicle['current_km'], 0, ',', '.')) ?> km</strong></div>
            <div><span>Muayene</span><strong><?= e(format_date($vehicle['inspection_due_date'])) ?></strong></div>
            <div><span>Sigorta</span><strong><?= e(format_date($vehicle['insurance_due_date'])) ?></strong></div>
            <div class="full-width"><span>Not</span><strong><?= e($vehicle['license_note']) ?></strong></div>
        </div>
    </article>

    <article class="panel">
        <div class="panel-head"><h2>Son Kullanımlar</h2></div>
        <div class="panel-body">
            <table class="table">
                <thead><tr><th>Personel</th><th>Başlangıç</th><th>Durum</th><th>Km</th></tr></thead>
                <tbody>
                <?php foreach ($assignments as $assignment): ?>
                    <tr>
                        <td><?= e($assignment['full_name']) ?></td>
                        <td><?= e(format_date($assignment['assigned_at'], 'd.m.Y H:i')) ?></td>
                        <td><span class="<?= e(badge_class($assignment['return_status'])) ?>"><?= e($assignment['return_status']) ?></span></td>
                        <td><?= e((string) $assignment['start_km']) ?> / <?= e((string) ($assignment['end_km'] ?? 0)) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<section class="panel-grid">
    <article class="panel">
        <div class="panel-head"><h2>Son Yakıt Kayıtları</h2></div>
        <div class="panel-body">
            <table class="table">
                <thead><tr><th>Tarih</th><th>Litre</th><th>Tutar</th><th>İstasyon</th></tr></thead>
                <tbody>
                <?php foreach ($fuelLogs as $fuel): ?>
                    <tr>
                        <td><?= e(format_date($fuel['purchase_date'])) ?></td>
                        <td><?= e((string) $fuel['litre']) ?> lt</td>
                        <td><?= e(format_money((float) $fuel['amount'])) ?></td>
                        <td><?= e($fuel['station_name']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
    <article class="panel">
        <div class="panel-head"><h2>Bakım Geçmişi</h2></div>
        <div class="panel-body">
            <table class="table">
                <thead><tr><th>Bakım Türü</th><th>Son Tarih</th><th>Sonraki Tarih</th><th>Maliyet</th></tr></thead>
                <tbody>
                <?php foreach ($maintenanceLogs as $maintenance): ?>
                    <tr>
                        <td><?= e($maintenance['maintenance_type']) ?></td>
                        <td><?= e(format_date($maintenance['last_maintenance_date'])) ?></td>
                        <td><?= e(format_date($maintenance['next_maintenance_date'])) ?></td>
                        <td><?= e(format_money((float) $maintenance['cost'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
