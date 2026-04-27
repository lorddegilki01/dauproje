<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

if (is_post()) {
    verify_csrf();
}

if (is_logged_in()) {
    log_activity((int) current_user()['id'], 'Kimlik', 'Çıkış', 'Kullanıcı güvenli çıkış yaptı.');
}

$_SESSION = [];
session_destroy();
session_start();
set_flash('success', 'Güvenli çıkış yapıldı.');
redirect('auth/login.php');

