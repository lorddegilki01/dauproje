<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$pageTitle = 'Arıza ve Masraf Kayıtları';
$canManage = is_admin();
$rows = fetch_all(
    'SELECT e.*, v.plate_number FROM expense_records e
     INNER JOIN vehicles v ON v.id = e.vehicle_id
     ORDER BY e.expense_date DESC'
);
$totalExpense = sum_value('SELECT SUM(amount) FROM expense_records');

include __DIR__ . '/../../includes/header.php';
?>
<section class="stats-grid">
    <article class="stat-card">
        <h3>Toplam Masraf</h3>
        <strong><?= e(format_money($totalExpense)) ?></strong>
        <span>Sistemdeki tüm arıza ve onarım giderleri</span>
    </article>
</section>

<section class="panel">
    <div class="panel-head">
        <h2>Masraf Listesi</h2>
        <?php if ($canManage): ?>
            <a class="button primary" href="<?= e(app_url('modules/expenses/form.php')) ?>">Masraf Kaydı Ekle</a>
        <?php endif; ?>
    </div>
    <div class="panel-body">
        <table class="table">
            <thead><tr><th>Tarih</th><th>Araç</th><th>Tür</th><th>Servis</th><th>Tutar</th><th>Açıklama</th><th>İşlem</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e(format_date($row['expense_date'])) ?></td>
                    <td><?= e($row['plate_number']) ?></td>
                    <td><?= e($row['expense_type']) ?></td>
                    <td><?= e($row['service_name']) ?></td>
                    <td><?= e(format_money((float) $row['amount'])) ?></td>
                    <td><?= e($row['description']) ?></td>
                    <td class="actions">
                        <?php if ($canManage): ?>
                            <a href="<?= e(app_url('modules/expenses/form.php?id=' . $row['id'])) ?>">Düzenle</a>
                            <form accept-charset="UTF-8" method="post" action="<?= e(app_url('modules/expenses/delete.php')) ?>" class="inline-form">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
                                <button class="link-button danger-link" type="submit" data-confirm="Masraf kaydı silinsin mi?">Sil</button>
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
