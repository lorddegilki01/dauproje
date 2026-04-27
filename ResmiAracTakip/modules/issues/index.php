<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$pageTitle = 'Arıza / Sorun Bildirim Yönetimi';

if (is_post()) {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $status = trim((string) ($_POST['status'] ?? 'açık'));
    $adminNote = trim((string) ($_POST['admin_note'] ?? ''));

    execute_query(
        'UPDATE issue_reports SET status = :status, admin_note = :admin_note WHERE id = :id',
        [
            'status' => $status,
            'admin_note' => $adminNote,
            'id' => $id,
        ]
    );
    log_activity('Arıza bildirimi güncellendi', 'Arıza Bildirim Yönetimi', 'Bildirimi durumu güncellendi: #' . $id);
    set_flash('success', 'Bildirim güncellendi.');
    redirect('modules/issues/index.php');
}

$rows = fetch_all(
    "SELECT ir.*, p.full_name, v.plate_number
     FROM issue_reports ir
     INNER JOIN personnel p ON p.id = ir.personnel_id
     INNER JOIN vehicles v ON v.id = ir.vehicle_id
     ORDER BY ir.created_at DESC"
);

include __DIR__ . '/../../includes/header.php';
?>
<section class="panel">
    <div class="panel-head"><h2>Tüm Arıza / Sorun Bildirimleri</h2></div>
    <div class="panel-body">
        <table class="table">
            <thead><tr><th>Tarih</th><th>Personel</th><th>Araç</th><th>Konu</th><th>Aciliyet</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e(format_date($row['report_date'])) ?></td>
                    <td><?= e($row['full_name']) ?></td>
                    <td><?= e($row['plate_number']) ?></td>
                    <td><?= e($row['subject']) ?></td>
                    <td><span class="<?= e(badge_class($row['urgency'])) ?>"><?= e($row['urgency']) ?></span></td>
                    <td><span class="<?= e(badge_class($row['status'])) ?>"><?= e($row['status']) ?></span></td>
                    <td>
                        <form accept-charset="UTF-8" method="post" class="form-grid">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
                            <label>Durum
                                <select name="status">
                                    <?php foreach (['açık', 'inceleniyor', 'çözüldü', 'reddedildi'] as $status): ?>
                                        <?php $selected = $row['status'] === $status ? 'selected' : ''; ?>
                                        <option value="<?= e($status) ?>" <?= e($selected) ?>><?= e(mb_convert_case($status, MB_CASE_TITLE, 'UTF-8')) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>Yönetici Notu
                                <input type="text" name="admin_note" value="<?= e($row['admin_note'] ?? '') ?>" maxlength="255">
                            </label>
                            <div class="form-actions full-width">
                                <button class="button primary" type="submit">Güncelle</button>
                            </div>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
