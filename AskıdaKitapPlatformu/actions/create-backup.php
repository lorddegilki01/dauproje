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
$result = $manager->createBackup('manuel', (int) (current_user()['id'] ?? 0));

set_flash($result['success'] ? 'success' : 'error', (string) $result['message']);
redirect('admin/backups.php');
