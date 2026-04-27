<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
$personnel = require_personnel_profile();

$pageTitle = 'Araçlarım';
$rows = fetch_all(
    "SELECT va.*, v.plate_number, v.brand, v.model, v.vehicle_type, v.fuel_type, v.current_km, v.status
     FROM vehicle_assignments va
     INNER JOIN vehicles v ON v.id = va.vehicle_id
     WHERE va.personnel_id = :personnel_id
     ORDER BY va.assigned_at DESC",
    ['personnel_id' => (int) $personnel['id']]
);

include __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <div class="panel-head"><h2>Kendinize Atanmış Araçlar</h2></div>
    <div class="panel-body">
        <table class="table">
            <thead>
            <tr>
                <th>Plaka</th>
                <th>Araç</th>
                <th>Tip / Yakıt</th>
                <th>KM</th>
                <th>Başlangıç</th>
                <th>Tahmini Teslim</th>
                <th>Amaç</th>
                <th>Durum</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['plate_number']) ?></td>
                    <td><?= e($row['brand'] . ' ' . $row['model']) ?></td>
                    <td><?= e($row['vehicle_type'] . ' / ' . $row['fuel_type']) ?></td>
                    <td><?= e((string) $row['current_km']) ?></td>
                    <td><?= e(format_date($row['assigned_at'], 'd.m.Y H:i')) ?></td>
                    <td><?= e(format_date($row['expected_return_at'], 'd.m.Y H:i')) ?></td>
                    <td><?= e($row['usage_purpose']) ?></td>
                    <td><span class="<?= e(badge_class($row['return_status'])) ?>"><?= e($row['return_status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
