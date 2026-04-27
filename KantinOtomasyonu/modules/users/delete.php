<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

if (!is_post()) {
    redirect('modules/users/index.php');
}
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Geçersiz kullanıcı.');
    redirect('modules/users/index.php');
}
if ($id === (int) current_user()['id']) {
    set_flash('error', 'Kendi hesabınızı silemezsiniz.');
    redirect('modules/users/index.php');
}

execute_query('DELETE FROM users WHERE id = :id LIMIT 1', ['id' => $id]);
set_flash('success', 'Kullanıcı silindi.');
redirect('modules/users/index.php');
