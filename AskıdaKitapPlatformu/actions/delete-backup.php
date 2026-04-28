<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/BackupManager.php';
require_admin();

if (!is_post()) {
    redirect('admin/backups.php');
}
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Geçersiz yedek kaydı.');
    redirect('admin/backups.php');
}

$manager = new BackupManager();
$ok = $manager->deleteBackup($id, true);

if ($ok) {
    log_activity((int) (current_user()['id'] ?? 0), 'Yedekleme', 'Yedek silme', 'Yedek kaydı silindi: #' . $id);
    system_log((int) (current_user()['id'] ?? 0), 'backup.delete', 'basarili', 'Yedek silindi: #' . $id);
    set_flash('success', 'Yedek dosyası silindi.');
} else {
    set_flash('error', 'Yedek bulunamadı.');
}

redirect('admin/backups.php');
