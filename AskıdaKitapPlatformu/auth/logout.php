<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

if (is_post()) {
    verify_csrf();
}

$user = current_user();
if ($user) {
    log_activity((int) $user['id'], 'Kimlik', 'Çıkış', 'Kullanıcı güvenli çıkış yaptı.');
    system_log((int) $user['id'], 'auth.logout', 'basarili', 'Kullanıcı çıkış yaptı.');
}

$_SESSION = [];
session_destroy();
session_start();
set_flash('success', 'Güvenli çıkış yapıldı.');
redirect('auth/login.php');
