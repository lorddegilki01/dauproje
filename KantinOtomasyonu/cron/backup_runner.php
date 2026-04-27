<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/BackupManager.php';

$manager = new BackupManager(db(), dirname(__DIR__));
$manager->ensureSchema();

$ran = $manager->runScheduledBackupIfDue();

if (PHP_SAPI === 'cli') {
    echo $ran
        ? '[' . date('Y-m-d H:i:s') . "] Otomatik yedekleme çalıştırıldı.\n"
        : '[' . date('Y-m-d H:i:s') . "] Çalıştırılacak otomatik yedek bulunmuyor.\n";
}
