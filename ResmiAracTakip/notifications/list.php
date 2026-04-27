<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_login();

header('Content-Type: application/json; charset=UTF-8');

$user = current_user();
if (!$user) {
    echo json_encode(['ok' => false, 'message' => 'Oturum bulunamadı.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (is_admin()) {
    sync_admin_notifications((int) $user['id']);
} elseif (is_personnel()) {
    sync_personnel_notifications((int) $user['id']);
}

$items = notification_recent_list((int) $user['id'], 12);
$unread = notification_unread_count((int) $user['id']);

echo json_encode([
    'ok' => true,
    'unread_count' => $unread,
    'items' => $items,
], JSON_UNESCAPED_UNICODE);
