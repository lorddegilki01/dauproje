<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('Geçersiz yedek kimliği.');
}

$row = fetch_one('SELECT * FROM backups WHERE id = :id', ['id' => $id]);
if (!$row) {
    http_response_code(404);
    exit('Yedek bulunamadı.');
}

$path = (string) ($row['file_path'] ?? '');
if ($path === '' || !is_file($path)) {
    http_response_code(404);
    exit('Yedek dosyası bulunamadı.');
}

header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . basename((string) $row['file_name']) . '"');
header('Content-Length: ' . (string) filesize($path));
readfile($path);
exit;
