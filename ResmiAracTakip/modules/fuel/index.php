<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$pageTitle = 'Yakıt Takibi';
$canManage = is_admin();
$rows = fetch_all(
    'SELECT f.*, v.plate_number FROM fuel_logs f
     INNER JOIN vehicles v ON v.id = f.vehicle_id
     ORDER BY f.purchase_date DESC'
);
$monthlyCost = fetch_all(
    'SELECT DATE_FORMAT(purchase_date, "%Y-%m") AS period,
            SUM(litre) AS total_litre, SUM(amount) AS total_amount
     FROM fuel_logs GROUP BY DATE_FORMAT(purchase_date, "%Y-%m")
     ORDER BY period DESC LIMIT 12'
);

include __DIR__ . '/../../includes/header.php';
?>
<section class="panel">
    <div class="panel-head">
        <h2>Yakıt Kayıtları</h2>
        <?php if ($canManage): ?>
            <a class="button primary" href="<?= e(app_url('modules/fuel/form.php')) ?>">Yakıt Kaydı Ekle</a>
        <?php endif; ?>
    </div>
    <div class="panel-body">
        <table class="table">
            <thead><tr><th>Tarih</th><th>Araç</th><th>Litre</th><th>Tutar</th><th>Kilometre</th><th>İstasyon</th><th>İşlem</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e(format_date($row['purchase_date'])) ?></td>
                    <td><?= e($row['plate_number']) ?></td>
                    <td><?= e((string) $row['litre']) ?> lt</td>
                    <td><?= e(format_money((float) $row['amount'])) ?></td>
                    <td><?= e((string) $row['odometer_km']) ?></td>
                    <td><?= e($row['station_name']) ?></td>
                    <td class="actions">
                        <?php if ($canManage): ?>
                            <a href="<?= e(app_url('modules/fuel/form.php?id=' . $row['id'])) ?>">Düzenle</a>
                            <form accept-charset="UTF-8" method="post" action="<?= e(app_url('modules/fuel/delete.php')) ?>" class="inline-form">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
                                <button class="link-button danger-link" type="submit" data-confirm="Yakıt kaydı silinsin mi?">Sil</button>
                            </form>
                        <?php else: ?>
                            <span>-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <div class="panel-head"><h2>Aylık Yakıt Özeti</h2></div>
    <div class="panel-body">
        <table class="table">
            <thead><tr><th>Dönem</th><th>Toplam Litre</th><th>Toplam Tutar</th></tr></thead>
            <tbody>
            <?php foreach ($monthlyCost as $summary): ?>
                <tr>
                    <td><?= e($summary['period']) ?></td>
                    <td><?= e((string) round((float) $summary['total_litre'], 2)) ?> lt</td>
                    <td><?= e(format_money((float) $summary['total_amount'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
