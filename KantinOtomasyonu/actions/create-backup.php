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
$result = $manager->createBackup('manuel', (int) current_user()['id']);

if (($result['success'] ?? false) === true) {
    set_flash('success', 'Manuel yedek başarıyla alındı: ' . (string) $result['file_name']);
} else {
    set_flash('error', 'Yedekleme başarısız: ' . (string) ($result['error'] ?? 'Bilinmeyen hata'));
}
redirect('admin/backups.php');
