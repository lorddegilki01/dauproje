<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$record = $id ? fetch_one('SELECT * FROM maintenance_records WHERE id = :id', ['id' => $id]) : null;
if ($id && !$record) {
    include __DIR__ . '/../../errors/404.php';
    exit;
}

$pageTitle = $record ? 'Bakım Kaydı Düzenle' : 'Yeni Bakım Kaydı';
$vehicles = fetch_all('SELECT id, plate_number FROM vehicles ORDER BY plate_number');
$errors = [];

if (is_post()) {
    verify_csrf();
    $data = [
        'vehicle_id' => (int) ($_POST['vehicle_id'] ?? 0),
        'maintenance_type' => trim((string) ($_POST['maintenance_type'] ?? '')),
        'last_maintenance_date' => trim((string) ($_POST['last_maintenance_date'] ?? '')),
        'next_maintenance_date' => trim((string) ($_POST['next_maintenance_date'] ?? '')),
        'next_maintenance_km' => ($_POST['next_maintenance_km'] ?? '') === '' ? null : (int) $_POST['next_maintenance_km'],
        'service_name' => trim((string) ($_POST['service_name'] ?? '')),
        'cost' => (float) ($_POST['cost'] ?? 0),
        'description' => trim((string) ($_POST['description'] ?? '')),
    ];

    if (!$data['vehicle_id'] || $data['maintenance_type'] === '' || $data['last_maintenance_date'] === '' || $data['next_maintenance_date'] === '' || $data['service_name'] === '') {
        $errors[] = 'Araç, bakım türü, tarihler ve servis adı zorunludur.';
    }

    if (strtotime($data['next_maintenance_date']) < strtotime($data['last_maintenance_date'])) {
        $errors[] = 'Sonraki bakım tarihi son bakım tarihinden önce olamaz.';
    }

    if (!$errors) {
        if ($record) {
            execute_query(
                'UPDATE maintenance_records SET vehicle_id=:vehicle_id, maintenance_type=:maintenance_type,
                 last_maintenance_date=:last_maintenance_date, next_maintenance_date=:next_maintenance_date,
                 next_maintenance_km=:next_maintenance_km, service_name=:service_name, cost=:cost, description=:description
                 WHERE id=:id',
                $data + ['id' => $id]
            );
            log_activity('Bakım kaydı güncellendi', 'Bakım Takibi', 'Bakım kaydı #' . $id . ' güncellendi.');
            set_flash('success', 'Bakım kaydı güncellendi.');
        } else {
            execute_query(
                'INSERT INTO maintenance_records (vehicle_id, maintenance_type, last_maintenance_date, next_maintenance_date, next_maintenance_km, service_name, cost, description)
                 VALUES (:vehicle_id, :maintenance_type, :last_maintenance_date, :next_maintenance_date, :next_maintenance_km, :service_name, :cost, :description)',
                $data
            );
            log_activity('Bakım kaydı eklendi', 'Bakım Takibi', 'Araç #' . $data['vehicle_id'] . ' için bakım kaydı oluşturuldu.');
            set_flash('success', 'Bakım kaydı oluşturuldu.');
        }

        ensure_assignment_consistency($data['vehicle_id']);
        redirect('modules/maintenance/index.php');
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
            <label>Bakım Türü
                <input type="text" name="maintenance_type" value="<?= e($_POST['maintenance_type'] ?? $record['maintenance_type'] ?? '') ?>" required>
            </label>
            <label>Son Bakım Tarihi
                <input type="date" name="last_maintenance_date" value="<?= e($_POST['last_maintenance_date'] ?? $record['last_maintenance_date'] ?? date('Y-m-d')) ?>" required>
            </label>
            <label>Sonraki Bakım Tarihi
                <input type="date" name="next_maintenance_date" value="<?= e($_POST['next_maintenance_date'] ?? $record['next_maintenance_date'] ?? '') ?>" required>
            </label>
            <label>Sonraki Bakım Kilometresi
                <input type="number" name="next_maintenance_km" value="<?= e($_POST['next_maintenance_km'] ?? $record['next_maintenance_km'] ?? '') ?>">
            </label>
            <label>Servis Adı
                <input type="text" name="service_name" value="<?= e($_POST['service_name'] ?? $record['service_name'] ?? '') ?>" required>
            </label>
            <label>Maliyet
                <input type="number" step="0.01" name="cost" value="<?= e($_POST['cost'] ?? $record['cost'] ?? '0') ?>">
            </label>
            <label class="full-width">Açıklama
                <textarea name="description" rows="4"><?= e($_POST['description'] ?? $record['description'] ?? '') ?></textarea>
            </label>
            <div class="form-actions full-width">
                <button class="button primary" type="submit">Kaydet</button>
                <a class="button ghost" href="<?= e(app_url('modules/maintenance/index.php')) ?>">Vazgeç</a>
            </div>
        </form>
    </div>
</section>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
