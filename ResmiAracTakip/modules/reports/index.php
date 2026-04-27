<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$pageTitle = 'Raporlar';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

$usageReport = fetch_all(
    'SELECT v.plate_number, p.full_name, va.assigned_at, va.returned_at, va.usage_purpose
     FROM vehicle_assignments va
     INNER JOIN vehicles v ON v.id = va.vehicle_id
     INNER JOIN personnel p ON p.id = va.personnel_id
     WHERE DATE(va.assigned_at) BETWEEN :start_date AND :end_date
     ORDER BY va.assigned_at DESC',
    ['start_date' => $startDate, 'end_date' => $endDate]
);

$vehicleFuel = fetch_all(
    'SELECT v.plate_number, SUM(f.litre) AS total_litre, SUM(f.amount) AS total_amount
     FROM fuel_logs f
     INNER JOIN vehicles v ON v.id = f.vehicle_id
     WHERE f.purchase_date BETWEEN :start_date AND :end_date
     GROUP BY f.vehicle_id
     ORDER BY total_amount DESC',
    ['start_date' => $startDate, 'end_date' => $endDate]
);

$costSummary = [
    'fuel' => sum_value('SELECT SUM(amount) FROM fuel_logs WHERE purchase_date BETWEEN :start_date AND :end_date', ['start_date' => $startDate, 'end_date' => $endDate]),
    'maintenance' => sum_value('SELECT SUM(cost) FROM maintenance_records WHERE next_maintenance_date BETWEEN :start_date AND :end_date', ['start_date' => $startDate, 'end_date' => $endDate]),
    'expense' => sum_value('SELECT SUM(amount) FROM expense_records WHERE expense_date BETWEEN :start_date AND :end_date', ['start_date' => $startDate, 'end_date' => $endDate]),
];

include __DIR__ . '/../../includes/header.php';
?>
<section class="panel">
    <div class="panel-head">
        <h2>Tarih Aralığına Göre Raporlar</h2>
        <a class="button secondary" href="<?= e(app_url('modules/reports/print.php?start_date=' . $startDate . '&end_date=' . $endDate)) ?>" target="_blank">Yazdırılabilir Görünüm</a>
    </div>
    <div class="panel-body">
        <form accept-charset="UTF-8" class="filter-bar" method="get">
            <label>Başlangıç Tarihi
                <input type="date" name="start_date" value="<?= e($startDate) ?>">
            </label>
            <label>Bitiş Tarihi
                <input type="date" name="end_date" value="<?= e($endDate) ?>">
            </label>
            <button class="button primary" type="submit">Filtrele</button>
        </form>
    </div>
</section>

<section class="stats-grid">
    <article class="stat-card"><h3>Yakıt Gideri</h3><strong><?= e(format_money($costSummary['fuel'])) ?></strong><span>Seçili tarih aralığı</span></article>
    <article class="stat-card"><h3>Bakım Gideri</h3><strong><?= e(format_money($costSummary['maintenance'])) ?></strong><span>Periyodik bakım toplamı</span></article>
    <article class="stat-card"><h3>Genel Masraf</h3><strong><?= e(format_money($costSummary['expense'])) ?></strong><span>Arıza ve onarım giderleri</span></article>
</section>

<section class="panel">
    <div class="panel-head"><h2>Araç Bazlı Kullanım Geçmişi</h2></div>
    <div class="panel-body">
        <table class="table">
            <thead><tr><th>Araç</th><th>Personel</th><th>Başlangıç</th><th>Teslim</th><th>Amaç</th></tr></thead>
            <tbody>
            <?php foreach ($usageReport as $row): ?>
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
    </div>
</section>

<section class="panel">
    <div class="panel-head"><h2>Araç Bazlı Yakıt Gideri</h2></div>
    <div class="panel-body">
        <table class="table">
            <thead><tr><th>Araç</th><th>Toplam Litre</th><th>Toplam Tutar</th></tr></thead>
            <tbody>
            <?php foreach ($vehicleFuel as $row): ?>
                <tr>
                    <td><?= e($row['plate_number']) ?></td>
                    <td><?= e((string) round((float) $row['total_litre'], 2)) ?> lt</td>
                    <td><?= e(format_money((float) $row['total_amount'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
