<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_login();
verify_csrf();

header('Content-Type: application/json; charset=UTF-8');

$user = current_user();
if (!$user) {
    echo json_encode(['ok' => false], JSON_UNESCAPED_UNICODE);
    exit;
}

notification_mark_all_read((int) $user['id']);

echo json_encode([
    'ok' => true,
    'unread_count' => 0,
], JSON_UNESCAPED_UNICODE);
