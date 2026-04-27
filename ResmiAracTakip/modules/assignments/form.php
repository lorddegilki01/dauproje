<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$record = $id ? fetch_one('SELECT * FROM vehicle_assignments WHERE id = :id', ['id' => $id]) : null;

if ($id && !$record) {
    include __DIR__ . '/../../errors/404.php';
    exit;
}

$pageTitle = $record ? 'Zimmet Kaydı Düzenle' : 'Yeni Araç Zimmeti';

$vehicles = fetch_all(
    "SELECT id, plate_number
     FROM vehicles
     WHERE status = 'müsait' OR id = :current_id
     ORDER BY plate_number",
    ['current_id' => (int) ($record['vehicle_id'] ?? 0)]
);

$personnel = fetch_all(
    "SELECT id, full_name
     FROM personnel
     WHERE status = 'aktif' OR id = :current_id
     ORDER BY full_name",
    ['current_id' => (int) ($record['personnel_id'] ?? 0)]
);

$errors = [];

if (is_post()) {
    verify_csrf();

    $data = [
        'vehicle_id' => (int) ($_POST['vehicle_id'] ?? 0),
        'personnel_id' => (int) ($_POST['personnel_id'] ?? 0),
        'assigned_at' => normalize_datetime_local((string) ($_POST['assigned_at'] ?? '')),
        'expected_return_at' => trim((string) ($_POST['expected_return_at'] ?? '')),
        'start_km' => (int) ($_POST['start_km'] ?? 0),
        'usage_purpose' => trim((string) ($_POST['usage_purpose'] ?? '')),
        'route_info' => trim((string) ($_POST['route_info'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
    ];

    if (
        !$data['vehicle_id']
        || !$data['personnel_id']
        || $data['assigned_at'] === ''
        || $data['usage_purpose'] === ''
        || $data['start_km'] < 0
    ) {
        $errors[] = 'Araç, personel, teslim alma tarihi, başlangıç kilometresi ve kullanım amacı zorunludur.';
    }

    if ($data['expected_return_at'] !== '') {
        $expectedTs = strtotime($data['expected_return_at']);
        $assignedTs = strtotime($data['assigned_at']);

        if ($expectedTs === false) {
            $errors[] = 'Tahmini teslim tarihi geçerli bir tarih-saat olmalıdır.';
        } elseif ($assignedTs !== false && $expectedTs <= $assignedTs) {
            $errors[] = 'Tahmini teslim tarihi, teslim alma tarihinden sonra olmalıdır.';
        } else {
            $data['expected_return_at'] = date('Y-m-d H:i:s', $expectedTs);
        }
    } else {
        $data['expected_return_at'] = null;
    }

    $activeAssignment = fetch_one(
        "SELECT id
         FROM vehicle_assignments
         WHERE vehicle_id = :vehicle_id
           AND return_status = 'iade edilmedi'
           AND id != :id
         LIMIT 1",
        [
            'vehicle_id' => $data['vehicle_id'],
            'id' => $id,
        ]
    );

    if ($activeAssignment) {
        $errors[] = 'Seçilen araç hâlen başka bir personelde görünüyor.';
    }

    if (!$errors) {
        if ($record) {
            execute_query(
                'UPDATE vehicle_assignments
                 SET vehicle_id = :vehicle_id,
                     personnel_id = :personnel_id,
                     assigned_at = :assigned_at,
                     expected_return_at = :expected_return_at,
                     start_km = :start_km,
                     usage_purpose = :usage_purpose,
                     route_info = :route_info,
                     description = :description
                 WHERE id = :id',
                $data + ['id' => $id]
            );
            ensure_assignment_consistency((int) $record['vehicle_id']);
            log_activity('Zimmet kaydı güncellendi', 'Araç Zimmeti', 'Zimmet kaydı #' . $id . ' güncellendi.');
            set_flash('success', 'Zimmet kaydı güncellendi.');
        } else {
            execute_query(
                'INSERT INTO vehicle_assignments
                 (vehicle_id, personnel_id, assigned_at, expected_return_at, start_km, usage_purpose, route_info, description)
                 VALUES
                 (:vehicle_id, :personnel_id, :assigned_at, :expected_return_at, :start_km, :usage_purpose, :route_info, :description)',
                $data
            );
            log_activity('Araç zimmeti oluşturuldu', 'Araç Zimmeti', 'Araç #' . $data['vehicle_id'] . ' personel #' . $data['personnel_id'] . ' üzerine tanımlandı.');
            set_flash('success', 'Araç zimmet kaydı oluşturuldu.');
        }

        execute_query(
            'UPDATE vehicles
             SET current_km = GREATEST(current_km, :current_km)
             WHERE id = :id',
            [
                'current_km' => $data['start_km'],
                'id' => $data['vehicle_id'],
            ]
        );

        ensure_assignment_consistency($data['vehicle_id']);
        redirect('modules/assignments/index.php');
    }
}

include __DIR__ . '/../../includes/header.php';
?>
<section class="panel">
    <div class="panel-head"><h2><?= e($pageTitle) ?></h2></div>
    <div class="panel-body">
        <?php foreach ($errors as $error): ?>
            <div class="alert error"><span><?= e($error) ?></span></div>
        <?php endforeach; ?>

        <form accept-charset="UTF-8" class="form-grid" method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <label>Araç
                <select name="vehicle_id" required>
                    <option value="">Seçiniz</option>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <?php $selectedVehicle = (string) ($_POST['vehicle_id'] ?? $record['vehicle_id'] ?? '') === (string) $vehicle['id'] ? 'selected' : ''; ?>
                        <option value="<?= e((string) $vehicle['id']) ?>" <?= e($selectedVehicle) ?>><?= e($vehicle['plate_number']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Personel
                <select name="personnel_id" required>
                    <option value="">Seçiniz</option>
                    <?php foreach ($personnel as $person): ?>
                        <?php $selectedPersonnel = (string) ($_POST['personnel_id'] ?? $record['personnel_id'] ?? '') === (string) $person['id'] ? 'selected' : ''; ?>
                        <option value="<?= e((string) $person['id']) ?>" <?= e($selectedPersonnel) ?>><?= e($person['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Teslim Alma Tarihi
                <input type="datetime-local" name="assigned_at" value="<?= e($_POST['assigned_at'] ?? (isset($record['assigned_at']) ? date('Y-m-d\\TH:i', strtotime($record['assigned_at'])) : date('Y-m-d\\TH:i'))) ?>" required>
            </label>

            <label>Tahmini Teslim Tarihi
                <input type="datetime-local" name="expected_return_at" value="<?= e($_POST['expected_return_at'] ?? (isset($record['expected_return_at']) && $record['expected_return_at'] ? date('Y-m-d\\TH:i', strtotime($record['expected_return_at'])) : '')) ?>">
            </label>

            <label>Başlangıç Kilometresi
                <input type="number" name="start_km" min="0" value="<?= e($_POST['start_km'] ?? $record['start_km'] ?? '0') ?>" required>
            </label>

            <label>Kullanım Amacı
                <input type="text" name="usage_purpose" value="<?= e($_POST['usage_purpose'] ?? $record['usage_purpose'] ?? '') ?>" required>
            </label>

            <label>Güzergâh
                <input type="text" name="route_info" value="<?= e($_POST['route_info'] ?? $record['route_info'] ?? '') ?>">
            </label>

            <label class="full-width">Açıklama
                <textarea name="description" rows="4"><?= e($_POST['description'] ?? $record['description'] ?? '') ?></textarea>
            </label>

            <div class="form-actions full-width">
                <button class="button primary" type="submit">Kaydet</button>
                <a class="button ghost" href="<?= e(app_url('modules/assignments/index.php')) ?>">Vazgeç</a>
            </div>
        </form>
    </div>
</section>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
