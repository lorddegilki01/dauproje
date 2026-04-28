<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/BackupManager.php';

$manager = new BackupManager();
if (!$manager->dueForAutomaticRun()) {
    echo '[' . date('Y-m-d H:i:s') . "] Otomatik yedekleme zamanı gelmedi.\n";
    exit(0);
}

$result = $manager->createBackup('otomatik', null);
echo '[' . date('Y-m-d H:i:s') . '] ' . ($result['success'] ? 'Başarılı: ' : 'Hata: ') . $result['message'] . PHP_EOL;
exit($result['success'] ? 0 : 1);
