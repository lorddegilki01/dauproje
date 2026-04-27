<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
$personnel = require_personnel_profile();

$pageTitle = 'Araç İade';
$errors = [];

if (is_post()) {
    verify_csrf();
    $assignmentId = (int) ($_POST['assignment_id'] ?? 0);
    $endKm = (int) ($_POST['end_km'] ?? 0);
    $returnNote = trim((string) ($_POST['return_note'] ?? ''));
    $issueNote = trim((string) ($_POST['issue_note'] ?? ''));

    $assignment = fetch_one(
        "SELECT va.*, v.plate_number
         FROM vehicle_assignments va
         INNER JOIN vehicles v ON v.id = va.vehicle_id
         WHERE va.id = :id AND va.personnel_id = :personnel_id AND va.return_status = 'iade edilmedi'
         LIMIT 1",
        ['id' => $assignmentId, 'personnel_id' => (int) $personnel['id']]
    );

    if (!$assignment) {
        $errors[] = 'İade edilecek kayıt bulunamadı.';
    } elseif ($endKm < (int) $assignment['start_km']) {
        $errors[] = 'Bitiş kilometresi başlangıç kilometresinden düşük olamaz.';
    }

    if (!$errors) {
        execute_query(
            "UPDATE vehicle_assignments
             SET returned_at = NOW(), end_km = :end_km, return_note = :return_note, issue_note = :issue_note, return_status = 'iade edildi'
             WHERE id = :id",
            [
                'end_km' => $endKm,
                'return_note' => $returnNote,
                'issue_note' => $issueNote,
                'id' => $assignmentId,
            ]
        );
        execute_query('UPDATE vehicles SET current_km = GREATEST(current_km, :km) WHERE id = :id', [
            'km' => $endKm,
            'id' => (int) $assignment['vehicle_id'],
        ]);
        ensure_assignment_consistency((int) $assignment['vehicle_id']);
        log_activity('Araç iade edildi', 'Personel İşlemleri', $assignment['plate_number'] . ' iade edildi.');
        set_flash('success', 'Araç iade işlemi tamamlandı. Toplam gidilen km hesaplandı.');
        redirect('personnel/return_vehicle.php');
    }
}

$rows = fetch_all(
    "SELECT va.*, v.plate_number
     FROM vehicle_assignments va
     INNER JOIN vehicles v ON v.id = va.vehicle_id
     WHERE va.personnel_id = :personnel_id AND va.return_status = 'iade edilmedi'
     ORDER BY va.assigned_at DESC",
    ['personnel_id' => (int) $personnel['id']]
);

include __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <div class="panel-head"><h2>Aktif Araçlar için İade İşlemi</h2></div>
    <div class="panel-body">
        <?php foreach ($errors as $error): ?>
            <div class="alert error"><span><?= e($error) ?></span></div>
        <?php endforeach; ?>
        <table class="table">
            <thead><tr><th>Araç</th><th>Başlangıç Tarihi</th><th>Başlangıç KM</th><th>Kullanım Amacı</th><th>İade</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['plate_number']) ?></td>
                    <td><?= e(format_date($row['assigned_at'], 'd.m.Y H:i')) ?></td>
                    <td><?= e((string) $row['start_km']) ?></td>
                    <td><?= e($row['usage_purpose']) ?></td>
                    <td>
                        <form accept-charset="UTF-8" method="post" class="form-grid">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="assignment_id" value="<?= e((string) $row['id']) ?>">
                            <label>Bitiş Kilometresi
                                <input type="number" name="end_km" min="<?= e((string) $row['start_km']) ?>" required>
                            </label>
                            <label>Kullanım Notu
                                <input type="text" name="return_note" maxlength="255">
                            </label>
                            <label class="full-width">Arıza / Hasar / Sorun Bildirimi
                                <textarea name="issue_note" rows="3"></textarea>
                            </label>
                            <div class="form-actions full-width">
                                <button class="button primary" type="submit">Aracı İade Et</button>
                            </div>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
