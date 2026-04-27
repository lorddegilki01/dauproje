<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$action = (string) ($_GET['action'] ?? '');
$match = fetch_one(
    'SELECT m.*, b.title
     FROM matches m
     INNER JOIN books b ON b.id = m.book_id
     WHERE m.id = :id',
    ['id' => $id]
);

if (!$match) {
    set_flash('error', 'Eşleşme kaydı bulunamadı.');
    redirect('matches/index.php');
}

$user = current_user();
$hasPermission = is_admin() || (int) $match['donor_user_id'] === (int) $user['id'] || (int) $match['requester_user_id'] === (int) $user['id'];
if (!$hasPermission) {
    set_flash('error', 'Bu kayıt için yetkiniz yok.');
    redirect('matches/index.php');
}

if ((string) $match['delivery_status'] !== 'bekliyor') {
    set_flash('warning', 'Bu eşleşme zaten kapatılmış.');
    redirect('matches/index.php');
}

if ($action === 'delivered') {
    db()->beginTransaction();
    try {
        execute_query(
            'UPDATE matches SET delivery_status = "teslim edildi", delivery_date = NOW(), updated_at = NOW() WHERE id = :id',
            ['id' => $id]
        );
        execute_query('UPDATE books SET status = "teslim edildi", is_active = 0, updated_at = NOW() WHERE id = :id', ['id' => (int) $match['book_id']]);
        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
    create_notification((int) $match['donor_user_id'], 'Teslim tamamlandı', $match['title'] . ' teslim edildi olarak güncellendi.', 'başarı', app_url('matches/index.php'));
    create_notification((int) $match['requester_user_id'], 'Teslim tamamlandı', $match['title'] . ' teslim edildi olarak güncellendi.', 'başarı', app_url('matches/index.php'));
    log_activity((int) $user['id'], 'Eşleşme', 'Teslim tamamlandı', (string) $match['title']);
    set_flash('success', 'Teslim süreci tamamlandı.');
    redirect('matches/index.php');
}

if ($action === 'cancel') {
    db()->beginTransaction();
    try {
        execute_query(
            'UPDATE matches SET delivery_status = "iptal", delivery_note = :note, updated_at = NOW() WHERE id = :id',
            ['note' => 'Eşleşme iptal edildi.', 'id' => $id]
        );
        execute_query('UPDATE books SET status = "askıda", is_active = 1, updated_at = NOW() WHERE id = :id', ['id' => (int) $match['book_id']]);
        execute_query('UPDATE book_requests SET request_status = "iptal", updated_at = NOW() WHERE id = :id', ['id' => (int) $match['request_id']]);
        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
    create_notification((int) $match['donor_user_id'], 'Eşleşme iptal edildi', $match['title'] . ' için eşleşme iptal edildi.', 'uyarı', app_url('matches/index.php'));
    create_notification((int) $match['requester_user_id'], 'Eşleşme iptal edildi', $match['title'] . ' için eşleşme iptal edildi.', 'uyarı', app_url('matches/index.php'));
    log_activity((int) $user['id'], 'Eşleşme', 'Eşleşme iptal edildi', (string) $match['title']);
    set_flash('success', 'Eşleşme iptal edildi.');
    redirect('matches/index.php');
}

set_flash('error', 'Geçersiz işlem.');
redirect('matches/index.php');

