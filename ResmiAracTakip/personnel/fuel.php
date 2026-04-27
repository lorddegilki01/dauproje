<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
$personnel = require_personnel_profile();

$pageTitle = 'Yakıt Kayıtlarım';
$errors = [];

$activeAssignments = fetch_all(
    "SELECT va.id, va.vehicle_id, v.plate_number, v.current_km
     FROM vehicle_assignments va
     INNER JOIN vehicles v ON v.id = va.vehicle_id
     WHERE va.personnel_id = :personnel_id AND va.return_status = 'iade edilmedi'
     ORDER BY va.assigned_at DESC",
    ['personnel_id' => (int) $personnel['id']]
);

if (is_post()) {
    verify_csrf();

    $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
    $purchaseDate = trim((string) ($_POST['purchase_date'] ?? ''));
    $litre = (float) ($_POST['litre'] ?? 0);
    $amount = (float) ($_POST['amount'] ?? 0);
    $km = (int) ($_POST['odometer_km'] ?? 0);
    $station = trim((string) ($_POST['station_name'] ?? ''));
    $note = trim((string) ($_POST['receipt_note'] ?? ''));

    $assignment = fetch_one(
        "SELECT va.id, v.current_km, v.plate_number
         FROM vehicle_assignments va
         INNER JOIN vehicles v ON v.id = va.vehicle_id
         WHERE va.personnel_id = :personnel_id
           AND va.vehicle_id = :vehicle_id
           AND va.return_status = 'iade edilmedi'
         LIMIT 1",
        [
            'personnel_id' => (int) $personnel['id'],
            'vehicle_id' => $vehicleId,
        ]
    );

    if (!$assignment) {
        $errors[] = 'Sadece aktif kullanımınızdaki araç için yakıt girebilirsiniz.';
    }
    if ($purchaseDate === '' || $litre <= 0 || $amount <= 0 || $km <= 0 || $station === '') {
        $errors[] = 'Tüm zorunlu alanları doldurunuz.';
    }
    if ($assignment && $km < (int) $assignment['current_km']) {
        $errors[] = 'Kilometre değeri mevcut kilometreden düşük olamaz.';
    }

    if (!$errors) {
        execute_query(
            'INSERT INTO fuel_logs (vehicle_id, personnel_id, purchase_date, litre, amount, odometer_km, station_name, receipt_note)
             VALUES (:vehicle_id, :personnel_id, :purchase_date, :litre, :amount, :odometer_km, :station_name, :receipt_note)',
            [
                'vehicle_id' => $vehicleId,
                'personnel_id' => (int) $personnel['id'],
                'purchase_date' => $purchaseDate,
                'litre' => $litre,
                'amount' => $amount,
                'odometer_km' => $km,
                'station_name' => $station,
                'receipt_note' => $note,
            ]
        );

        execute_query(
            'UPDATE vehicles SET current_km = GREATEST(current_km, :km) WHERE id = :vehicle_id',
            ['km' => $km, 'vehicle_id' => $vehicleId]
        );

        log_activity('Yakıt kaydı eklendi', 'Personel Yakıt', 'Personel kendi araç kullanımı için yakıt kaydı ekledi.');
        set_flash('success', 'Yakıt kaydı eklendi.');
        redirect('personnel/fuel.php');
    }
}

$rows = fetch_all(
    "SELECT fl.*, v.plate_number
     FROM fuel_logs fl
     INNER JOIN vehicles v ON v.id = fl.vehicle_id
     WHERE fl.personnel_id = :personnel_id
     ORDER BY fl.purchase_date DESC, fl.id DESC",
    ['personnel_id' => (int) $personnel['id']]
);

include __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <div class="panel-head"><h2>Yakıt Kaydı Ekle</h2></div>
    <div class="panel-body">
        <?php foreach ($errors as $error): ?>
            <div class="alert error"><span><?= e($error) ?></span></div>
        <?php endforeach; ?>
        <form accept-charset="UTF-8" class="form-grid" method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>Araç
                <select name="vehicle_id" required>
                    <option value="">Seçiniz</option>
                    <?php foreach ($activeAssignments as $assignment): ?>
                        <option value="<?= e((string) $assignment['vehicle_id']) ?>"><?= e($assignment['plate_number']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Tarih
                <input type="date" name="purchase_date" value="<?= old('purchase_date', date('Y-m-d')) ?>" required>
            </label>
            <label>Litre
                <input type="number" step="0.01" name="litre" value="<?= old('litre') ?>" required>
            </label>
            <label>Tutar
                <input type="number" step="0.01" name="amount" value="<?= old('amount') ?>" required>
            </label>
            <label>Kilometre
                <input type="number" name="odometer_km" value="<?= old('odometer_km') ?>" required>
            </label>
            <label>İstasyon
                <input type="text" name="station_name" value="<?= old('station_name') ?>" required>
            </label>
            <label class="full-width">Açıklama
                <textarea name="receipt_note" rows="3"><?= old('receipt_note') ?></textarea>
            </label>
            <div class="form-actions full-width">
                <button class="button primary" type="submit">Kaydet</button>
            </div>
        </form>
    </div>
</section>

<section class="panel">
    <div class="panel-head"><h2>Yakıt Geçmişim</h2></div>
    <div class="panel-body">
        <table class="table">
            <thead><tr><th>Tarih</th><th>Araç</th><th>Litre</th><th>Tutar</th><th>Kilometre</th><th>İstasyon</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e(format_date($row['purchase_date'])) ?></td>
                    <td><?= e($row['plate_number']) ?></td>
                    <td><?= e((string) $row['litre']) ?> lt</td>
                    <td><?= e(format_money((float) $row['amount'])) ?></td>
                    <td><?= e((string) $row['odometer_km']) ?></td>
                    <td><?= e($row['station_name']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
