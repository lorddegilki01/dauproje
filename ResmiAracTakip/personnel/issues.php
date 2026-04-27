<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
$personnel = require_personnel_profile();

$pageTitle = 'Arıza / Sorun Bildirimlerim';
$errors = [];

$assignments = fetch_all(
    "SELECT va.id, va.vehicle_id, v.plate_number
     FROM vehicle_assignments va
     INNER JOIN vehicles v ON v.id = va.vehicle_id
     WHERE va.personnel_id = :personnel_id
     ORDER BY va.assigned_at DESC",
    ['personnel_id' => (int) $personnel['id']]
);

if (is_post()) {
    verify_csrf();
    $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
    $assignmentId = ($_POST['assignment_id'] ?? '') === '' ? null : (int) $_POST['assignment_id'];
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $reportDate = trim((string) ($_POST['report_date'] ?? date('Y-m-d')));
    $urgency = trim((string) ($_POST['urgency'] ?? 'orta'));

    if (!$vehicleId || $subject === '' || $description === '') {
        $errors[] = 'Araç, konu ve açıklama zorunludur.';
    }

    if (!$errors) {
        execute_query(
            'INSERT INTO issue_reports (vehicle_id, personnel_id, assignment_id, subject, description, report_date, urgency)
             VALUES (:vehicle_id, :personnel_id, :assignment_id, :subject, :description, :report_date, :urgency)',
            [
                'vehicle_id' => $vehicleId,
                'personnel_id' => (int) $personnel['id'],
                'assignment_id' => $assignmentId,
                'subject' => $subject,
                'description' => $description,
                'report_date' => $reportDate,
                'urgency' => $urgency,
            ]
        );
        log_activity('Arıza bildirimi oluşturuldu', 'Personel Arıza', 'Personel araç arıza/sorun bildirimi oluşturdu.');
        set_flash('success', 'Bildiriminiz kaydedildi.');
        redirect('personnel/issues.php');
    }
}

$rows = fetch_all(
    "SELECT ir.*, v.plate_number
     FROM issue_reports ir
     INNER JOIN vehicles v ON v.id = ir.vehicle_id
     WHERE ir.personnel_id = :personnel_id
     ORDER BY ir.created_at DESC",
    ['personnel_id' => (int) $personnel['id']]
);

include __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <div class="panel-head"><h2>Yeni Arıza / Sorun Bildirimi</h2></div>
    <div class="panel-body">
        <?php foreach ($errors as $error): ?>
            <div class="alert error"><span><?= e($error) ?></span></div>
        <?php endforeach; ?>
        <form accept-charset="UTF-8" class="form-grid" method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>Araç
                <select name="vehicle_id" required>
                    <option value="">Seçiniz</option>
                    <?php foreach ($assignments as $assignment): ?>
                        <option value="<?= e((string) $assignment['vehicle_id']) ?>"><?= e($assignment['plate_number']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>İlgili Atama (Opsiyonel)
                <select name="assignment_id">
                    <option value="">Yok</option>
                    <?php foreach ($assignments as $assignment): ?>
                        <option value="<?= e((string) $assignment['id']) ?>"><?= e($assignment['plate_number'] . ' / Kayıt #' . $assignment['id']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Konu
                <input type="text" name="subject" maxlength="140" value="<?= old('subject') ?>" required>
            </label>
            <label>Tarih
                <input type="date" name="report_date" value="<?= old('report_date', date('Y-m-d')) ?>" required>
            </label>
            <label>Aciliyet
                <select name="urgency" required>
                    <?php foreach (['düşük', 'orta', 'yüksek', 'kritik'] as $urgency): ?>
                        <option value="<?= e($urgency) ?>"><?= e(mb_convert_case($urgency, MB_CASE_TITLE, 'UTF-8')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="full-width">Açıklama
                <textarea name="description" rows="4" required><?= old('description') ?></textarea>
            </label>
            <div class="form-actions full-width">
                <button class="button primary" type="submit">Bildirimi Gönder</button>
            </div>
        </form>
    </div>
</section>

<section class="panel">
    <div class="panel-head"><h2>Bildirilerim</h2></div>
    <div class="panel-body">
        <table class="table">
            <thead><tr><th>Tarih</th><th>Araç</th><th>Konu</th><th>Aciliyet</th><th>Durum</th><th>Yönetici Notu</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e(format_date($row['report_date'])) ?></td>
                    <td><?= e($row['plate_number']) ?></td>
                    <td><?= e($row['subject']) ?></td>
                    <td><span class="<?= e(badge_class($row['urgency'])) ?>"><?= e($row['urgency']) ?></span></td>
                    <td><span class="<?= e(badge_class($row['status'])) ?>"><?= e($row['status']) ?></span></td>
                    <td><?= e($row['admin_note'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
