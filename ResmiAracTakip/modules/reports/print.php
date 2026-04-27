<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

$reportRows = fetch_all(
    'SELECT v.plate_number, p.full_name, va.assigned_at, va.returned_at, va.usage_purpose
     FROM vehicle_assignments va
     INNER JOIN vehicles v ON v.id = va.vehicle_id
     INNER JOIN personnel p ON p.id = va.personnel_id
     WHERE DATE(va.assigned_at) BETWEEN :start_date AND :end_date
     ORDER BY va.assigned_at DESC',
    ['start_date' => $startDate, 'end_date' => $endDate]
);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yazdırılabilir Rapor</title>
    <link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>">
</head>
<body class="print-page">
<section class="panel">
    <h1>Resmi Araç Takip Raporu</h1>
    <p>Rapor Aralığı: <?= e(format_date($startDate)) ?> - <?= e(format_date($endDate)) ?></p>
    <table class="table">
        <thead><tr><th>Araç</th><th>Personel</th><th>Başlangıç</th><th>Teslim</th><th>Amaç</th></tr></thead>
        <tbody>
        <?php foreach ($reportRows as $row): ?>
            <tr>
                <td><?= e($row['plate_number']) ?></td>
                <td><?= e($row['full_name']) ?></td>
                <td><?= e(format_date($row['assigned_at'], 'd.m.Y H:i')) ?></td>
                <td><?= e(format_date($row['returned_at'], 'd.m.Y H:i')) ?></td>
                <td><?= e($row['usage_purpose']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<script>window.print();</script>
</body>
</html>
