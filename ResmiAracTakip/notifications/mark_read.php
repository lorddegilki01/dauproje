<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_login();
verify_csrf();

header('Content-Type: application/json; charset=UTF-8');

$user = current_user();
$id = (int) ($_POST['id'] ?? 0);

if (!$user || $id <= 0) {
    echo json_encode(['ok' => false], JSON_UNESCAPED_UNICODE);
    exit;
}

notification_mark_read((int) $user['id'], $id);

echo json_encode([
    'ok' => true,
    'unread_count' => notification_unread_count((int) $user['id']),
], JSON_UNESCAPED_UNICODE);
