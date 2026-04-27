<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_role(['admin']);

if (!is_post()) {
    redirect('modules/fuel/index.php');
}

verify_csrf();
$id = (int) ($_POST['id'] ?? 0);
$record = fetch_one('SELECT * FROM fuel_logs WHERE id = :id', ['id' => $id]);

if ($record) {
    execute_query('DELETE FROM fuel_logs WHERE id = :id', ['id' => $id]);
    log_activity('Yakıt kaydı silindi', 'Yakıt Takibi', 'Yakıt kaydı #' . $id . ' silindi.');
    set_flash('success', 'Yakıt kaydı silindi.');
}

redirect('modules/fuel/index.php');
