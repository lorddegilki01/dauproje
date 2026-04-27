<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_role(['admin']);

if (!is_post()) {
    redirect('modules/assignments/index.php');
}

verify_csrf();
$id = (int) ($_POST['id'] ?? 0);
$assignment = fetch_one('SELECT * FROM vehicle_assignments WHERE id = :id', ['id' => $id]);

if ($assignment) {
    execute_query('DELETE FROM vehicle_assignments WHERE id = :id', ['id' => $id]);
    ensure_assignment_consistency((int) $assignment['vehicle_id']);
    log_activity('Zimmet silindi', 'Araç Zimmeti', 'Zimmet kaydı #' . $id . ' silindi.');
    set_flash('success', 'Zimmet kaydı silindi.');
}

redirect('modules/assignments/index.php');
