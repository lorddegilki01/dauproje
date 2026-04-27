<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$pageTitle = 'Bakım Takibi';
$canManage = is_admin();
$rows = fetch_all(
    "SELECT m.*, v.plate_number,
            CASE
                WHEN m.next_maintenance_date < CURDATE() THEN 'gecikti'
                WHEN m.next_maintenance_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'yaklaşıyor'
                ELSE 'planlandı'
            END AS alert_status
     FROM maintenance_records m
     INNER JOIN vehicles v ON v.id = m.vehicle_id
     ORDER BY m.next_maintenance_date ASC"
);

include __DIR__ . '/../../includes/header.php';
?>
<section class="panel">
    <div class="panel-head">
        <h2>Bakım Kayıtları</h2>
        <?php if ($canManage): ?>
            <a class="button primary" href="<?= e(app_url('modules/maintenance/form.php')) ?>">Bakım Kaydı Ekle</a>
        <?php endif; ?>
    </div>
    <div class="panel-body">
        <table class="table">
            <thead><tr><th>Araç</th><th>Bakım Türü</th><th>Son Tarih</th><th>Sonraki Tarih</th><th>Servis</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['plate_number']) ?></td>
                    <td><?= e($row['maintenance_type']) ?></td>
                    <td><?= e(format_date($row['last_maintenance_date'])) ?></td>
                    <td><?= e(format_date($row['next_maintenance_date'])) ?></td>
                    <td><?= e($row['service_name']) ?></td>
                    <td><span class="<?= e(badge_class($row['alert_status'])) ?>"><?= e($row['alert_status']) ?></span></td>
                    <td class="actions">
                        <?php if ($canManage): ?>
                            <a href="<?= e(app_url('modules/maintenance/form.php?id=' . $row['id'])) ?>">Düzenle</a>
                            <form accept-charset="UTF-8" method="post" action="<?= e(app_url('modules/maintenance/delete.php')) ?>" class="inline-form">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
                                <button class="link-button danger-link" type="submit" data-confirm="Bakım kaydı silinsin mi?">Sil</button>
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
<?php include __DIR__ . '/../../includes/footer.php'; ?>
