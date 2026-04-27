<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$record = $id ? fetch_one('SELECT * FROM fuel_logs WHERE id = :id', ['id' => $id]) : null;
if ($id && !$record) {
    include __DIR__ . '/../../errors/404.php';
    exit;
}

$pageTitle = $record ? 'Yakıt Kaydı Düzenle' : 'Yeni Yakıt Kaydı';
$vehicles = fetch_all('SELECT id, plate_number, current_km FROM vehicles ORDER BY plate_number');
$errors = [];

if (is_post()) {
    verify_csrf();
    $data = [
        'vehicle_id' => (int) ($_POST['vehicle_id'] ?? 0),
        'purchase_date' => trim((string) ($_POST['purchase_date'] ?? '')),
        'litre' => (float) ($_POST['litre'] ?? 0),
        'amount' => (float) ($_POST['amount'] ?? 0),
        'odometer_km' => (int) ($_POST['odometer_km'] ?? 0),
        'station_name' => trim((string) ($_POST['station_name'] ?? '')),
        'receipt_note' => trim((string) ($_POST['receipt_note'] ?? '')),
    ];

    if (!$data['vehicle_id'] || $data['purchase_date'] === '' || $data['litre'] <= 0 || $data['amount'] <= 0 || $data['station_name'] === '' || $data['odometer_km'] <= 0) {
        $errors[] = 'Araç, tarih, litre, tutar, kilometre ve istasyon alanları zorunludur.';
    }

    if (!$errors) {
        if ($record) {
            execute_query(
                'UPDATE fuel_logs SET vehicle_id=:vehicle_id, purchase_date=:purchase_date, litre=:litre, amount=:amount,
                 odometer_km=:odometer_km, station_name=:station_name, receipt_note=:receipt_note WHERE id=:id',
                $data + ['id' => $id]
            );
            log_activity('Yakıt kaydı güncellendi', 'Yakıt Takibi', 'Yakıt kaydı #' . $id . ' güncellendi.');
            set_flash('success', 'Yakıt kaydı güncellendi.');
        } else {
            execute_query(
                'INSERT INTO fuel_logs (vehicle_id, purchase_date, litre, amount, odometer_km, station_name, receipt_note)
                 VALUES (:vehicle_id, :purchase_date, :litre, :amount, :odometer_km, :station_name, :receipt_note)',
                $data
            );
            log_activity('Yakıt kaydı eklendi', 'Yakıt Takibi', 'Araç #' . $data['vehicle_id'] . ' için yakıt kaydı oluşturuldu.');
            set_flash('success', 'Yakıt kaydı eklendi.');
        }

        execute_query('UPDATE vehicles SET current_km = GREATEST(current_km, :odometer_km) WHERE id = :id', [
            'odometer_km' => $data['odometer_km'],
            'id' => $data['vehicle_id'],
        ]);
        redirect('modules/fuel/index.php');
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
            <label>Tarih
                <input type="date" name="purchase_date" value="<?= e($_POST['purchase_date'] ?? $record['purchase_date'] ?? date('Y-m-d')) ?>" required>
            </label>
            <label>Litre
                <input type="number" step="0.01" name="litre" value="<?= e($_POST['litre'] ?? $record['litre'] ?? '') ?>" required>
            </label>
            <label>Tutar
                <input type="number" step="0.01" name="amount" value="<?= e($_POST['amount'] ?? $record['amount'] ?? '') ?>" required>
            </label>
            <label>Kilometre
                <input type="number" name="odometer_km" value="<?= e($_POST['odometer_km'] ?? $record['odometer_km'] ?? '') ?>" required>
            </label>
            <label>İstasyon Bilgisi
                <input type="text" name="station_name" value="<?= e($_POST['station_name'] ?? $record['station_name'] ?? '') ?>" required>
            </label>
            <label class="full-width">Fiş Notu
                <textarea name="receipt_note" rows="4"><?= e($_POST['receipt_note'] ?? $record['receipt_note'] ?? '') ?></textarea>
            </label>
            <div class="form-actions full-width">
                <button class="button primary" type="submit">Kaydet</button>
                <a class="button ghost" href="<?= e(app_url('modules/fuel/index.php')) ?>">Vazgeç</a>
            </div>
        </form>
    </div>
</section>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
