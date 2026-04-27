<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$vehicle = $id ? fetch_one('SELECT * FROM vehicles WHERE id = :id', ['id' => $id]) : null;

if ($id && !$vehicle) {
    include __DIR__ . '/../../errors/404.php';
    exit;
}

$pageTitle = $vehicle ? 'Araç Düzenle' : 'Yeni Araç Ekle';
$errors = [];

if (is_post()) {
    verify_csrf();

    $data = [
        'plate_number' => trim((string) ($_POST['plate_number'] ?? '')),
        'brand' => trim((string) ($_POST['brand'] ?? '')),
        'model' => trim((string) ($_POST['model'] ?? '')),
        'model_year' => (int) ($_POST['model_year'] ?? 0),
        'vehicle_type' => trim((string) ($_POST['vehicle_type'] ?? '')),
        'fuel_type' => trim((string) ($_POST['fuel_type'] ?? '')),
        'current_km' => (int) ($_POST['current_km'] ?? 0),
        'status' => trim((string) ($_POST['status'] ?? 'müsait')),
        'license_note' => trim((string) ($_POST['license_note'] ?? '')),
        'inspection_due_date' => $_POST['inspection_due_date'] ?: null,
        'insurance_due_date' => $_POST['insurance_due_date'] ?: null,
    ];

    if ($data['plate_number'] === '' || $data['brand'] === '' || $data['model'] === '') {
        $errors[] = 'Plaka, marka ve model alanları zorunludur.';
    }

    if ($data['model_year'] < 1990 || $data['model_year'] > ((int) date('Y') + 1)) {
        $errors[] = 'Araç yılı geçerli bir aralıkta olmalıdır.';
    }

    $duplicate = fetch_one(
        'SELECT id FROM vehicles WHERE plate_number = :plate AND id != :id LIMIT 1',
        ['plate' => $data['plate_number'], 'id' => $id]
    );

    if ($duplicate) {
        $errors[] = 'Bu plaka ile kayıtlı başka bir araç zaten mevcut.';
    }

    if (!$errors) {
        if ($vehicle) {
            execute_query(
                'UPDATE vehicles SET plate_number=:plate_number, brand=:brand, model=:model, model_year=:model_year,
                 vehicle_type=:vehicle_type, fuel_type=:fuel_type, current_km=:current_km, status=:status,
                 license_note=:license_note, inspection_due_date=:inspection_due_date, insurance_due_date=:insurance_due_date
                 WHERE id=:id',
                $data + ['id' => $id]
            );
            log_activity('Araç güncellendi', 'Araç Yönetimi', $data['plate_number'] . ' plakalı araç güncellendi.');
            set_flash('success', 'Araç bilgileri güncellendi.');
        } else {
            execute_query(
                'INSERT INTO vehicles (plate_number, brand, model, model_year, vehicle_type, fuel_type, current_km, status, license_note, inspection_due_date, insurance_due_date)
                 VALUES (:plate_number, :brand, :model, :model_year, :vehicle_type, :fuel_type, :current_km, :status, :license_note, :inspection_due_date, :insurance_due_date)',
                $data
            );
            log_activity('Araç eklendi', 'Araç Yönetimi', $data['plate_number'] . ' plakalı araç sisteme eklendi.');
            set_flash('success', 'Yeni araç kaydı oluşturuldu.');
        }

        redirect('modules/vehicles/index.php');
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
        <form accept-charset="UTF-8" class="form-grid" method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>Plaka
                <input type="text" name="plate_number" maxlength="20" value="<?= e($_POST['plate_number'] ?? $vehicle['plate_number'] ?? '') ?>" required>
            </label>
            <label>Marka
                <input type="text" name="brand" maxlength="80" value="<?= e($_POST['brand'] ?? $vehicle['brand'] ?? '') ?>" required>
            </label>
            <label>Model
                <input type="text" name="model" maxlength="80" value="<?= e($_POST['model'] ?? $vehicle['model'] ?? '') ?>" required>
            </label>
            <label>Yıl
                <input type="number" name="model_year" min="1990" max="<?= e((string) ((int) date('Y') + 1)) ?>" value="<?= e($_POST['model_year'] ?? $vehicle['model_year'] ?? date('Y')) ?>" required>
            </label>
            <label>Araç Tipi
                <input type="text" name="vehicle_type" maxlength="60" value="<?= e($_POST['vehicle_type'] ?? $vehicle['vehicle_type'] ?? '') ?>" required>
            </label>
            <label>Yakıt Türü
                <select name="fuel_type" required>
                    <?php foreach (['Benzin', 'Dizel', 'LPG', 'Hibrit', 'Elektrik'] as $fuel): ?>
                        <?php $selected = ($_POST['fuel_type'] ?? $vehicle['fuel_type'] ?? '') === $fuel ? 'selected' : ''; ?>
                        <option value="<?= e($fuel) ?>" <?= e($selected) ?>><?= e($fuel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Mevcut Kilometre
                <input type="number" name="current_km" min="0" value="<?= e($_POST['current_km'] ?? $vehicle['current_km'] ?? '0') ?>" required>
            </label>
            <label>Durum
                <select name="status" required>
                    <?php foreach (['müsait', 'kullanımda', 'bakımda', 'pasif'] as $status): ?>
                        <?php $selected = ($_POST['status'] ?? $vehicle['status'] ?? 'müsait') === $status ? 'selected' : ''; ?>
                        <option value="<?= e($status) ?>" <?= e($selected) ?>><?= e(mb_convert_case($status, MB_CASE_TITLE, 'UTF-8')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Muayene Bitiş Tarihi
                <input type="date" name="inspection_due_date" value="<?= e($_POST['inspection_due_date'] ?? $vehicle['inspection_due_date'] ?? '') ?>">
            </label>
            <label>Sigorta Bitiş Tarihi
                <input type="date" name="insurance_due_date" value="<?= e($_POST['insurance_due_date'] ?? $vehicle['insurance_due_date'] ?? '') ?>">
            </label>
            <label class="full-width">Ruhsat / Not Bilgisi
                <textarea name="license_note" rows="4"><?= e($_POST['license_note'] ?? $vehicle['license_note'] ?? '') ?></textarea>
            </label>
            <div class="form-actions full-width">
                <button class="button primary" type="submit">Kaydet</button>
                <a class="button ghost" href="<?= e(app_url('modules/vehicles/index.php')) ?>">Vazgeç</a>
            </div>
        </form>
    </div>
</section>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
