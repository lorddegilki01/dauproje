<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$assignment = fetch_one(
    "SELECT va.*, v.plate_number, v.id AS vehicle_ref FROM vehicle_assignments va
     INNER JOIN vehicles v ON v.id = va.vehicle_id
     WHERE va.id = :id",
    ['id' => $id]
);

if (!$assignment) {
    include __DIR__ . '/../../errors/404.php';
    exit;
}

$pageTitle = 'Araç İade İşlemi';
$errors = [];

if (is_post()) {
    verify_csrf();

    $returnedAt = trim((string) ($_POST['returned_at'] ?? ''));
    $endKm = (int) ($_POST['end_km'] ?? 0);

    if ($returnedAt === '' || $endKm < (int) $assignment['start_km']) {
        $errors[] = 'Teslim tarihi girilmeli ve bitiş kilometresi başlangıç kilometresinden düşük olmamalıdır.';
    }

    if (!$errors) {
        execute_query(
            "UPDATE vehicle_assignments SET returned_at = :returned_at, end_km = :end_km, return_status = 'iade edildi' WHERE id = :id",
            ['returned_at' => $returnedAt, 'end_km' => $endKm, 'id' => $id]
        );
        execute_query("UPDATE vehicles SET status = 'müsait', current_km = :current_km WHERE id = :id", [
            'current_km' => $endKm,
            'id' => $assignment['vehicle_ref'],
        ]);
        log_activity('Araç iade alındı', 'Araç Zimmeti', $assignment['plate_number'] . ' iade olarak kapatıldı.');
        set_flash('success', 'Araç teslim işlemi tamamlandı.');
        redirect('modules/assignments/index.php');
    }
}

include __DIR__ . '/../../includes/header.php';
?>
<section class="panel">
    <div class="panel-head"><h2>Araç İade İşlemi</h2></div>
    <div class="panel-body">
        <?php foreach ($errors as $error): ?>
            <div class="alert error"><span><?= e($error) ?></span></div>
        <?php endforeach; ?>
        <form accept-charset="UTF-8" class="form-grid" method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>Araç
                <input type="text" value="<?= e($assignment['plate_number']) ?>" disabled>
            </label>
            <label>Başlangıç Km
                <input type="text" value="<?= e((string) $assignment['start_km']) ?>" disabled>
            </label>
            <label>Teslim Tarihi
                <input type="datetime-local" name="returned_at" value="<?= e($_POST['returned_at'] ?? date('Y-m-d\TH:i')) ?>" required>
            </label>
            <label>Bitiş Kilometresi
                <input type="number" name="end_km" min="<?= e((string) $assignment['start_km']) ?>" value="<?= e($_POST['end_km'] ?? (string) $assignment['start_km']) ?>" required>
            </label>
            <div class="form-actions full-width">
                <button class="button primary" type="submit">İade Kaydını Tamamla</button>
                <a class="button ghost" href="<?= e(app_url('modules/assignments/index.php')) ?>">Vazgeç</a>
            </div>
        </form>
    </div>
</section>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
