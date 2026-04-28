<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/BackupManager.php';
require_admin();

if (!is_post()) {
    redirect('admin/backups.php');
}
verify_csrf();

$manager = new BackupManager();
$manager->updateSettings($_POST);

log_activity((int) (current_user()['id'] ?? 0), 'Yedekleme', 'Ayar güncelleme', 'Yedekleme ayarları güncellendi.');
system_log((int) (current_user()['id'] ?? 0), 'backup.settings.update', 'basarili', 'Yedekleme ayarları güncellendi.');
set_flash('success', 'Yedekleme ayarları güncellendi.');
redirect('admin/backups.php');
