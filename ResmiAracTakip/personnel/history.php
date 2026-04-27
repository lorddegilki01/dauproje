<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
$personnel = require_personnel_profile();

$pageTitle = 'Kullanım Geçmişim';

$assignments = fetch_all(
    "SELECT va.*, v.plate_number
     FROM vehicle_assignments va
     INNER JOIN vehicles v ON v.id = va.vehicle_id
     WHERE va.personnel_id = :personnel_id
     ORDER BY va.assigned_at DESC",
    ['personnel_id' => (int) $personnel['id']]
);

$fuelLogs = fetch_all(
    "SELECT fl.*, v.plate_number
     FROM fuel_logs fl
     INNER JOIN vehicles v ON v.id = fl.vehicle_id
     WHERE fl.personnel_id = :personnel_id
     ORDER BY fl.purchase_date DESC",
    ['personnel_id' => (int) $personnel['id']]
);

$issues = fetch_all(
    "SELECT ir.*, v.plate_number
     FROM issue_reports ir
     INNER JOIN vehicles v ON v.id = ir.vehicle_id
     WHERE ir.personnel_id = :personnel_id
     ORDER BY ir.created_at DESC",
    ['personnel_id' => (int) $personnel['id']]
);

include __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <div class="panel-head"><h2>Kullanım Geçmişi</h2></div>
    <div class="panel-body">
        <table class="table">
            <thead><tr><th>Araç</th><th>Başlangıç</th><th>Teslim</th><th>Başlangıç KM</th><th>Bitiş KM</th><th>Toplam KM</th></tr></thead>
            <tbody>
            <?php foreach ($assignments as $row): ?>
                <?php $totalKm = ($row['end_km'] !== null) ? ((int) $row['end_km'] - (int) $row['start_km']) : 0; ?>
                <tr>
                    <td><?= e($row['plate_number']) ?></td>
                    <td><?= e(format_date($row['assigned_at'], 'd.m.Y H:i')) ?></td>
                    <td><?= e(format_date($row['returned_at'], 'd.m.Y H:i')) ?></td>
                    <td><?= e((string) $row['start_km']) ?></td>
                    <td><?= e((string) ($row['end_km'] ?? 0)) ?></td>
                    <td><?= e((string) $totalKm) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel-grid">
    <article class="panel">
        <div class="panel-head"><h2>Kendi Yakıt Kayıtlarım</h2></div>
        <div class="panel-body">
            <table class="table">
                <thead><tr><th>Tarih</th><th>Araç</th><th>Litre</th><th>Tutar</th></tr></thead>
                <tbody>
                <?php foreach ($fuelLogs as $fuel): ?>
                    <tr>
                        <td><?= e(format_date($fuel['purchase_date'])) ?></td>
                        <td><?= e($fuel['plate_number']) ?></td>
                        <td><?= e((string) $fuel['litre']) ?> lt</td>
                        <td><?= e(format_money((float) $fuel['amount'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="panel">
        <div class="panel-head"><h2>Kendi Arıza Bildirimlerim</h2></div>
        <div class="panel-body">
            <table class="table">
                <thead><tr><th>Tarih</th><th>Araç</th><th>Konu</th><th>Durum</th></tr></thead>
                <tbody>
                <?php foreach ($issues as $issue): ?>
                    <tr>
                        <td><?= e(format_date($issue['report_date'])) ?></td>
                        <td><?= e($issue['plate_number']) ?></td>
                        <td><?= e($issue['subject']) ?></td>
                        <td><span class="<?= e(badge_class($issue['status'])) ?>"><?= e($issue['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
