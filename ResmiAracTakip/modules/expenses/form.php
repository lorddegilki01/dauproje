<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$record = $id ? fetch_one('SELECT * FROM expense_records WHERE id = :id', ['id' => $id]) : null;
if ($id && !$record) {
    include __DIR__ . '/../../errors/404.php';
    exit;
}

$pageTitle = $record ? 'Masraf Kaydı Düzenle' : 'Yeni Arıza / Masraf Kaydı';
$vehicles = fetch_all('SELECT id, plate_number FROM vehicles ORDER BY plate_number');
$errors = [];

if (is_post()) {
    verify_csrf();
    $data = [
        'vehicle_id' => (int) ($_POST['vehicle_id'] ?? 0),
        'expense_type' => trim((string) ($_POST['expense_type'] ?? '')),
        'expense_date' => trim((string) ($_POST['expense_date'] ?? '')),
        'amount' => (float) ($_POST['amount'] ?? 0),
        'service_name' => trim((string) ($_POST['service_name'] ?? '')),
        'parts_changed' => trim((string) ($_POST['parts_changed'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
    ];

    if (!$data['vehicle_id'] || $data['expense_type'] === '' || $data['expense_date'] === '' || $data['amount'] <= 0 || $data['description'] === '') {
        $errors[] = 'Araç, masraf türü, tarih, tutar ve açıklama zorunludur.';
    }

    if (!$errors) {
        if ($record) {
            execute_query(
                'UPDATE expense_records SET vehicle_id=:vehicle_id, expense_type=:expense_type, expense_date=:expense_date,
                 amount=:amount, service_name=:service_name, parts_changed=:parts_changed, description=:description WHERE id=:id',
                $data + ['id' => $id]
            );
            log_activity('Masraf kaydı güncellendi', 'Arıza ve Masraf', 'Masraf kaydı #' . $id . ' güncellendi.');
            set_flash('success', 'Masraf kaydı güncellendi.');
        } else {
            execute_query(
                'INSERT INTO expense_records (vehicle_id, expense_type, expense_date, amount, service_name, parts_changed, description)
                 VALUES (:vehicle_id, :expense_type, :expense_date, :amount, :service_name, :parts_changed, :description)',
                $data
            );
            log_activity('Masraf kaydı eklendi', 'Arıza ve Masraf', 'Araç #' . $data['vehicle_id'] . ' için masraf kaydı oluşturuldu.');
            set_flash('success', 'Masraf kaydı oluşturuldu.');
        }

        redirect('modules/expenses/index.php');
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
            <label>Masraf Türü
                <select name="expense_type" required>
                    <option value="">Seçiniz</option>
                    <?php foreach (['arıza', 'onarım', 'parça değişimi', 'diğer'] as $type): ?>
                        <?php $selectedType = ($_POST['expense_type'] ?? $record['expense_type'] ?? '') === $type ? 'selected' : ''; ?>
                        <option value="<?= e($type) ?>" <?= e($selectedType) ?>><?= e(mb_convert_case($type, MB_CASE_TITLE, 'UTF-8')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Tarih
                <input type="date" name="expense_date" value="<?= e($_POST['expense_date'] ?? $record['expense_date'] ?? date('Y-m-d')) ?>" required>
            </label>
            <label>Tutar
                <input type="number" step="0.01" name="amount" value="<?= e($_POST['amount'] ?? $record['amount'] ?? '') ?>" required>
            </label>
            <label>Servis Bilgisi
                <input type="text" name="service_name" value="<?= e($_POST['service_name'] ?? $record['service_name'] ?? '') ?>">
            </label>
            <label>Parça Değişimi
                <input type="text" name="parts_changed" value="<?= e($_POST['parts_changed'] ?? $record['parts_changed'] ?? '') ?>">
            </label>
            <label class="full-width">Açıklama
                <textarea name="description" rows="4" required><?= e($_POST['description'] ?? $record['description'] ?? '') ?></textarea>
            </label>
            <div class="form-actions full-width">
                <button class="button primary" type="submit">Kaydet</button>
                <a class="button ghost" href="<?= e(app_url('modules/expenses/index.php')) ?>">Vazgeç</a>
            </div>
        </form>
    </div>
</section>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
