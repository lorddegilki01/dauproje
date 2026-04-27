<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/functions.php';

if (is_post()) {
    $token = (string) ($_POST['csrf_token'] ?? '');
    $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
    if ($token !== '' && $sessionToken !== '' && hash_equals($sessionToken, $token)) {
        logout_user();
        set_flash('success', 'Güvenli çıkış yapıldı.');
        redirect('auth/login.php');
    }

    set_flash('warning', 'Güvenlik doğrulaması yenilendi. Lütfen tekrar deneyin.');
    redirect('index.php');
}

logout_user();
set_flash('success', 'Çıkış yapıldı.');
redirect('auth/login.php');
