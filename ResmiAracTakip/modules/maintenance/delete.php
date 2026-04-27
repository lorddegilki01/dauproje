<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_role(['admin']);

if (!is_post()) {
    redirect('modules/maintenance/index.php');
}

verify_csrf();
$id = (int) ($_POST['id'] ?? 0);
$record = fetch_one('SELECT * FROM maintenance_records WHERE id = :id', ['id' => $id]);

if ($record) {
    execute_query('DELETE FROM maintenance_records WHERE id = :id', ['id' => $id]);
    ensure_assignment_consistency((int) $record['vehicle_id']);
    log_activity('Bakım kaydı silindi', 'Bakım Takibi', 'Bakım kaydı #' . $id . ' silindi.');
    set_flash('success', 'Bakım kaydı silindi.');
}

redirect('modules/maintenance/index.php');
