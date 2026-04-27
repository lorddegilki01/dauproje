<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$pageTitle = 'Araç Talep Yönetimi';
$errors = [];

if (is_post()) {
    verify_csrf();
    $requestId = (int) ($_POST['request_id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    $adminNote = trim((string) ($_POST['admin_note'] ?? ''));

    $request = fetch_one(
        "SELECT vr.*, p.full_name, v.plate_number, v.current_km, v.status AS vehicle_status
         FROM vehicle_requests vr
         INNER JOIN personnel p ON p.id = vr.personnel_id
         INNER JOIN vehicles v ON v.id = vr.vehicle_id
         WHERE vr.id = :id
         LIMIT 1",
        ['id' => $requestId]
    );

    if (!$request) {
        $errors[] = 'Talep bulunamadı.';
    } elseif ($request['status'] !== 'bekliyor') {
        $errors[] = 'Bu talep zaten sonuçlandırılmış.';
    } else {
        if ($action === 'approve') {
            $active = fetch_one(
                "SELECT id FROM vehicle_assignments WHERE vehicle_id = :vehicle_id AND return_status = 'iade edilmedi' LIMIT 1",
                ['vehicle_id' => (int) $request['vehicle_id']]
            );
            if ($active || $request['vehicle_status'] !== 'müsait') {
                $errors[] = 'Araç şu anda atanamaz durumda. Önce aktif zimmeti kapatın.';
            } else {
                execute_query(
                    'INSERT INTO vehicle_assignments
                     (vehicle_id, personnel_id, assigned_at, expected_return_at, start_km, usage_purpose, description, return_status)
                     VALUES (:vehicle_id, :personnel_id, :assigned_at, :expected_return_at, :start_km, :usage_purpose, :description, :return_status)',
                    [
                        'vehicle_id' => (int) $request['vehicle_id'],
                        'personnel_id' => (int) $request['personnel_id'],
                        'assigned_at' => $request['planned_start_at'],
                        'expected_return_at' => $request['planned_end_at'],
                        'start_km' => (int) $request['current_km'],
                        'usage_purpose' => $request['usage_purpose'],
                        'description' => $request['description'] ?? '',
                        'return_status' => 'iade edilmedi',
                    ]
                );

                $assignmentId = (int) db()->lastInsertId();
                execute_query(
                    "UPDATE vehicle_requests
                     SET status = 'onaylandı', admin_note = :admin_note, approved_by = :approved_by, assignment_id = :assignment_id
                     WHERE id = :id",
                    [
                        'admin_note' => $adminNote,
                        'approved_by' => (int) current_user()['id'],
                        'assignment_id' => $assignmentId,
                        'id' => $requestId,
                    ]
                );
                ensure_assignment_consistency((int) $request['vehicle_id']);
                log_activity('Talep onaylandı', 'Talep Yönetimi', 'Araç talebi onaylandı: #' . $requestId);
                set_flash('success', 'Talep onaylandı ve araç ataması oluşturuldu.');
                redirect('modules/requests/index.php');
            }
        } elseif ($action === 'reject') {
            execute_query(
                "UPDATE vehicle_requests
                 SET status = 'reddedildi', admin_note = :admin_note, approved_by = :approved_by
                 WHERE id = :id",
                [
                    'admin_note' => $adminNote,
                    'approved_by' => (int) current_user()['id'],
                    'id' => $requestId,
                ]
            );
            log_activity('Talep reddedildi', 'Talep Yönetimi', 'Araç talebi reddedildi: #' . $requestId);
            set_flash('success', 'Talep reddedildi.');
            redirect('modules/requests/index.php');
        }
    }
}

$rows = fetch_all(
    "SELECT vr.*, p.full_name, v.plate_number
     FROM vehicle_requests vr
     INNER JOIN personnel p ON p.id = vr.personnel_id
     INNER JOIN vehicles v ON v.id = vr.vehicle_id
     ORDER BY vr.request_date DESC"
);

include __DIR__ . '/../../includes/header.php';
?>
<section class="panel">
    <div class="panel-head"><h2>Araç Talepleri</h2></div>
    <div class="panel-body">
        <?php foreach ($errors as $error): ?>
            <div class="alert error"><span><?= e($error) ?></span></div>
        <?php endforeach; ?>
        <table class="table">
            <thead><tr><th>Personel</th><th>Araç</th><th>Talep</th><th>Kullanım Aralığı</th><th>Amaç</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['full_name']) ?></td>
                    <td><?= e($row['plate_number']) ?></td>
                    <td><?= e(format_date($row['request_date'], 'd.m.Y H:i')) ?></td>
                    <td><?= e(format_date($row['planned_start_at'], 'd.m.Y H:i')) ?> - <?= e(format_date($row['planned_end_at'], 'd.m.Y H:i')) ?></td>
                    <td><?= e($row['usage_purpose']) ?></td>
                    <td><span class="<?= e(badge_class($row['status'])) ?>"><?= e($row['status']) ?></span></td>
                    <td>
                        <?php if ($row['status'] === 'bekliyor'): ?>
                            <form accept-charset="UTF-8" method="post" class="form-grid">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="request_id" value="<?= e((string) $row['id']) ?>">
                                <label class="full-width">Yönetici Notu
                                    <input type="text" name="admin_note" maxlength="255">
                                </label>
                                <div class="form-actions full-width">
                                    <button class="button primary" type="submit" name="action" value="approve">Onayla</button>
                                    <button class="button ghost" type="submit" name="action" value="reject">Reddet</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <span><?= e($row['admin_note'] ?? '-') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
