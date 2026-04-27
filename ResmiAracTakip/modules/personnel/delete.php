<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_role(['admin']);

if (!is_post()) {
    redirect('modules/personnel/index.php');
}

verify_csrf();
$id = (int) ($_POST['id'] ?? 0);
$person = fetch_one('SELECT * FROM personnel WHERE id = :id', ['id' => $id]);

if ($person) {
    execute_query('DELETE FROM personnel WHERE id = :id', ['id' => $id]);
    log_activity('Personel silindi', 'Personel Yönetimi', $person['full_name'] . ' kaydı silindi.');
    set_flash('success', 'Personel kaydı silindi.');
}

redirect('modules/personnel/index.php');
