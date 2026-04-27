<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_role(['admin']);

if (!is_post()) {
    redirect('modules/vehicles/index.php');
}

verify_csrf();
$id = (int) ($_POST['id'] ?? 0);
$vehicle = fetch_one('SELECT * FROM vehicles WHERE id = :id', ['id' => $id]);

if ($vehicle) {
    execute_query('DELETE FROM vehicles WHERE id = :id', ['id' => $id]);
    log_activity('Araç silindi', 'Araç Yönetimi', $vehicle['plate_number'] . ' plakalı araç silindi.');
    set_flash('success', 'Araç kaydı silindi.');
}

redirect('modules/vehicles/index.php');
