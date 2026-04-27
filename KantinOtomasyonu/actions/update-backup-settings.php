<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();
require_once __DIR__ . '/../includes/BackupManager.php';

if (!is_post()) {
    redirect('admin/backups.php');
}
verify_csrf();

$manager = new BackupManager(db(), dirname(__DIR__));

try {
    $manager->updateSettings($_POST, (int) current_user()['id']);
    set_flash('success', 'Yedekleme ayarları kaydedildi.');
} catch (Throwable $e) {
    set_flash('error', 'Ayarlar kaydedilemedi: ' . $e->getMessage());
}

redirect('admin/backups.php');
