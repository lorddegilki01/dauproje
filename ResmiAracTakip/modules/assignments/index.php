<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$pageTitle = 'Araç Zimmet ve Kullanım Takibi';
$canManage = is_admin();
$rows = fetch_all(
    "SELECT va.*, v.plate_number, p.full_name
     FROM vehicle_assignments va
     INNER JOIN vehicles v ON v.id = va.vehicle_id
     INNER JOIN personnel p ON p.id = va.personnel_id
     ORDER BY va.assigned_at DESC"
);

include __DIR__ . '/../../includes/header.php';
?>
<section class="panel">
    <div class="panel-head">
        <h2>Zimmet Kayıtları</h2>
        <?php if ($canManage): ?>
            <a class="button primary" href="<?= e(app_url('modules/assignments/form.php')) ?>">Yeni Zimmet Kaydı</a>
        <?php endif; ?>
    </div>
    <div class="panel-body">
        <input type="search" class="table-search" data-table-search="assignment-table" placeholder="Araç, personel veya amaç ara">
        <table id="assignment-table" class="table">
            <thead><tr><th>Araç</th><th>Personel</th><th>Başlangıç</th><th>Teslim</th><th>Amaç</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['plate_number']) ?></td>
                    <td><?= e($row['full_name']) ?></td>
                    <td><?= e(format_date($row['assigned_at'], 'd.m.Y H:i')) ?></td>
                    <td><?= e(format_date($row['returned_at'], 'd.m.Y H:i')) ?></td>
                    <td><?= e($row['usage_purpose']) ?></td>
                    <td><span class="<?= e(badge_class($row['return_status'])) ?>"><?= e($row['return_status']) ?></span></td>
                    <td class="actions">
                        <?php if ($canManage): ?>
                            <?php if ($row['return_status'] === 'iade edilmedi'): ?>
                                <a href="<?= e(app_url('modules/assignments/return.php?id=' . $row['id'])) ?>">İade Al</a>
                            <?php endif; ?>
                            <a href="<?= e(app_url('modules/assignments/form.php?id=' . $row['id'])) ?>">Düzenle</a>
                            <form accept-charset="UTF-8" method="post" action="<?= e(app_url('modules/assignments/delete.php')) ?>" class="inline-form">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
                                <button class="link-button danger-link" type="submit" data-confirm="Zimmet kaydı silinsin mi?">Sil</button>
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
