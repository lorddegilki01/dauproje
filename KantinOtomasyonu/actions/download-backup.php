<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();
require_once __DIR__ . '/../includes/BackupManager.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('Geçersiz yedek kaydı.');
}

$manager = new BackupManager(db(), dirname(__DIR__));
$backup = $manager->getBackupById($id);
if (!$backup) {
    http_response_code(404);
    exit('Yedek bulunamadı.');
}

$filePath = (string) $backup['file_path'];
$realProject = realpath(dirname(__DIR__)) ?: dirname(__DIR__);
$realFile = realpath($filePath);
if ($realFile === false || !str_starts_with(strtolower($realFile), strtolower($realProject))) {
    http_response_code(403);
    exit('Dosya erişimi engellendi.');
}
if (!is_file($realFile)) {
    http_response_code(404);
    exit('Yedek dosyası bulunamadı.');
}

header('Content-Description: File Transfer');
header('Content-Type: application/sql; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . basename($realFile) . '"');
header('Content-Length: ' . (string) filesize($realFile));
header('Pragma: no-cache');
header('Expires: 0');
readfile($realFile);
exit;
