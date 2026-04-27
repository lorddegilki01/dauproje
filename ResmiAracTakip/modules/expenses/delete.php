<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_role(['admin']);

if (!is_post()) {
    redirect('modules/expenses/index.php');
}

verify_csrf();
$id = (int) ($_POST['id'] ?? 0);
$record = fetch_one('SELECT * FROM expense_records WHERE id = :id', ['id' => $id]);

if ($record) {
    execute_query('DELETE FROM expense_records WHERE id = :id', ['id' => $id]);
    log_activity('Masraf kaydı silindi', 'Arıza ve Masraf', 'Masraf kaydı #' . $id . ' silindi.');
    set_flash('success', 'Masraf kaydı silindi.');
}

redirect('modules/expenses/index.php');
