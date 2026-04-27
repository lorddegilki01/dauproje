<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
$personnel = require_personnel_profile();

$pageTitle = 'Araç Teslim Alma';
$errors = [];

if (is_post()) {
    verify_csrf();
    $assignmentId = (int) ($_POST['assignment_id'] ?? 0);
    $startKm = (int) ($_POST['start_km'] ?? 0);
    $receiveNote = trim((string) ($_POST['receive_note'] ?? ''));

    $assignment = fetch_one(
        "SELECT va.*, v.current_km, v.plate_number
         FROM vehicle_assignments va
         INNER JOIN vehicles v ON v.id = va.vehicle_id
         WHERE va.id = :id AND va.personnel_id = :personnel_id AND va.return_status = 'iade edilmedi'
         LIMIT 1",
        ['id' => $assignmentId, 'personnel_id' => (int) $personnel['id']]
    );

    if (!$assignment) {
        $errors[] = 'Teslim alınabilecek uygun bir kayıt bulunamadı.';
    } elseif ($startKm < (int) $assignment['current_km']) {
        $errors[] = 'Başlangıç kilometresi araç mevcut kilometresinden düşük olamaz.';
    }

    if (!$errors) {
        execute_query(
            'UPDATE vehicle_assignments
             SET received_by_personnel_at = NOW(), start_km = :start_km, receive_note = :receive_note
             WHERE id = :id',
            [
                'start_km' => $startKm,
                'receive_note' => $receiveNote,
                'id' => $assignmentId,
            ]
        );
        execute_query('UPDATE vehicles SET current_km = GREATEST(current_km, :km) WHERE id = :id', [
            'km' => $startKm,
            'id' => (int) $assignment['vehicle_id'],
        ]);
        log_activity('Araç teslim alındı', 'Personel İşlemleri', $assignment['plate_number'] . ' personel tarafından teslim alındı.');
        set_flash('success', 'Araç teslim alma işlemi kaydedildi.');
        redirect('personnel/receive_vehicle.php');
    }
}

$rows = fetch_all(
    "SELECT va.id, va.assigned_at, va.expected_return_at, va.start_km, va.receive_note, va.received_by_personnel_at,
            v.plate_number, v.current_km
     FROM vehicle_assignments va
     INNER JOIN vehicles v ON v.id = va.vehicle_id
     WHERE va.personnel_id = :personnel_id AND va.return_status = 'iade edilmedi'
     ORDER BY va.assigned_at DESC",
    ['personnel_id' => (int) $personnel['id']]
);

include __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <div class="panel-head"><h2>Aktif Atamalarda Teslim Alma Onayı</h2></div>
    <div class="panel-body">
        <?php foreach ($errors as $error): ?>
            <div class="alert error"><span><?= e($error) ?></span></div>
        <?php endforeach; ?>
        <table class="table">
            <thead><tr><th>Araç</th><th>Atama Tarihi</th><th>Mevcut KM</th><th>Tahmini Teslim</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['plate_number']) ?></td>
                    <td><?= e(format_date($row['assigned_at'], 'd.m.Y H:i')) ?></td>
                    <td><?= e((string) $row['current_km']) ?></td>
                    <td><?= e(format_date($row['expected_return_at'], 'd.m.Y H:i')) ?></td>
                    <td><?= e($row['received_by_personnel_at'] ? 'Teslim alındı' : 'Teslim bekliyor') ?></td>
                    <td>
                        <?php if (!$row['received_by_personnel_at']): ?>
                            <form accept-charset="UTF-8" method="post" class="form-grid">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="assignment_id" value="<?= e((string) $row['id']) ?>">
                                <label>Başlangıç KM
                                    <input type="number" name="start_km" min="<?= e((string) $row['current_km']) ?>" value="<?= e((string) $row['current_km']) ?>" required>
                                </label>
                                <label>Teslim Alma Notu
                                    <input type="text" name="receive_note" maxlength="255">
                                </label>
                                <div class="form-actions full-width">
                                    <button class="button primary" type="submit">Teslim Aldım</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <span><?= e($row['receive_note'] ?: '-') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
