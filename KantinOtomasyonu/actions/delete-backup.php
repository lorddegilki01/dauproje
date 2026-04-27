<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();
require_once __DIR__ . '/../includes/BackupManager.php';

if (!is_post()) {
    redirect('admin/backups.php');
}
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Geçersiz yedek kaydı.');
    redirect('admin/backups.php');
}

$manager = new BackupManager(db(), dirname(__DIR__));
$result = $manager->deleteBackup($id, (int) current_user()['id']);

if (($result['success'] ?? false) === true) {
    set_flash('success', 'Yedek kaydı silindi.');
} else {
    set_flash('error', 'Yedek silinemedi: ' . (string) ($result['error'] ?? 'Bilinmeyen hata'));
}
redirect('admin/backups.php');
