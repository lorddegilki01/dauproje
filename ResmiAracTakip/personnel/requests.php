<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
$personnel = require_personnel_profile();

$pageTitle = 'Araç Taleplerim';
$errors = [];

if (is_post()) {
    verify_csrf();
    $data = [
        'vehicle_id' => (int) ($_POST['vehicle_id'] ?? 0),
        'usage_purpose' => trim((string) ($_POST['usage_purpose'] ?? '')),
        'planned_start_at' => normalize_datetime_local((string) ($_POST['planned_start_at'] ?? '')),
        'planned_end_at' => normalize_datetime_local((string) ($_POST['planned_end_at'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
    ];

    if (!$data['vehicle_id'] || $data['usage_purpose'] === '') {
        $errors[] = 'Araç ve kullanım amacı zorunludur.';
    }

    if (strtotime($data['planned_end_at']) <= strtotime($data['planned_start_at'])) {
        $errors[] = 'Tahmini teslim tarihi başlangıç tarihinden sonra olmalıdır.';
    }

    $vehicle = fetch_one('SELECT id, status FROM vehicles WHERE id = :id', ['id' => $data['vehicle_id']]);
    if (!$vehicle || $vehicle['status'] !== 'müsait') {
        $errors[] = 'Seçtiğiniz araç şu anda talebe uygun durumda değildir.';
    }

    if (!$errors) {
        execute_query(
            'INSERT INTO vehicle_requests (personnel_id, vehicle_id, usage_purpose, planned_start_at, planned_end_at, description)
             VALUES (:personnel_id, :vehicle_id, :usage_purpose, :planned_start_at, :planned_end_at, :description)',
            [
                'personnel_id' => (int) $personnel['id'],
                'vehicle_id' => $data['vehicle_id'],
                'usage_purpose' => $data['usage_purpose'],
                'planned_start_at' => $data['planned_start_at'],
                'planned_end_at' => $data['planned_end_at'],
                'description' => $data['description'],
            ]
        );
        log_activity('Araç talebi oluşturuldu', 'Personel Talepleri', 'Personel araç talebi oluşturdu.');
        set_flash('success', 'Araç talebiniz oluşturuldu. Onay sürecine alındı.');
        redirect('personnel/requests.php');
    }
}

$vehicles = fetch_all("SELECT id, plate_number, brand, model FROM vehicles WHERE status = 'müsait' ORDER BY plate_number");
$requests = fetch_all(
    "SELECT vr.*, v.plate_number, v.brand, v.model
     FROM vehicle_requests vr
     INNER JOIN vehicles v ON v.id = vr.vehicle_id
     WHERE vr.personnel_id = :personnel_id
     ORDER BY vr.request_date DESC",
    ['personnel_id' => (int) $personnel['id']]
);

include __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <div class="panel-head"><h2>Yeni Araç Talebi</h2></div>
    <div class="panel-body">
        <?php foreach ($errors as $error): ?>
            <div class="alert error"><span><?= e($error) ?></span></div>
        <?php endforeach; ?>
        <form accept-charset="UTF-8" class="form-grid" method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>Araç
                <select name="vehicle_id" required>
                    <option value="">Seçiniz</option>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <option value="<?= e((string) $vehicle['id']) ?>">
                            <?= e($vehicle['plate_number'] . ' - ' . $vehicle['brand'] . ' ' . $vehicle['model']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Kullanım Amacı
                <input type="text" name="usage_purpose" maxlength="180" value="<?= old('usage_purpose') ?>" required>
            </label>
            <label>Kullanım Başlangıç
                <input type="datetime-local" name="planned_start_at" value="<?= old('planned_start_at', date('Y-m-d\TH:i')) ?>" required>
            </label>
            <label>Tahmini Teslim
                <input type="datetime-local" name="planned_end_at" value="<?= old('planned_end_at', date('Y-m-d\TH:i', strtotime('+8 hours'))) ?>" required>
            </label>
            <label class="full-width">Açıklama
                <textarea name="description" rows="4"><?= old('description') ?></textarea>
            </label>
            <div class="form-actions full-width">
                <button class="button primary" type="submit">Talep Gönder</button>
            </div>
        </form>
    </div>
</section>

<section class="panel">
    <div class="panel-head"><h2>Talep Geçmişim</h2></div>
    <div class="panel-body">
        <table class="table">
            <thead><tr><th>Tarih</th><th>Araç</th><th>Kullanım Aralığı</th><th>Amaç</th><th>Durum</th><th>Yönetici Notu</th></tr></thead>
            <tbody>
            <?php foreach ($requests as $request): ?>
                <tr>
                    <td><?= e(format_date($request['request_date'], 'd.m.Y H:i')) ?></td>
                    <td><?= e($request['plate_number'] . ' - ' . $request['brand'] . ' ' . $request['model']) ?></td>
                    <td><?= e(format_date($request['planned_start_at'], 'd.m.Y H:i')) ?> - <?= e(format_date($request['planned_end_at'], 'd.m.Y H:i')) ?></td>
                    <td><?= e($request['usage_purpose']) ?></td>
                    <td><span class="<?= e(badge_class($request['status'])) ?>"><?= e($request['status']) ?></span></td>
                    <td><?= e($request['admin_note'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
