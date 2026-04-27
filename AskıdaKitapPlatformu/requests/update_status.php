<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$action = (string) ($_GET['action'] ?? '');

$request = fetch_one(
    'SELECT r.*, b.title, b.id book_id, b.donor_user_id, b.status book_status
     FROM book_requests r
     INNER JOIN books b ON b.id = r.book_id
     WHERE r.id = :id',
    ['id' => $id]
);

if (!$request) {
    set_flash('error', 'Talep kaydı bulunamadı.');
    redirect('requests/index.php');
}

$user = current_user();

if ($action === 'cancel') {
    if ((int) $request['requester_user_id'] !== (int) $user['id']) {
        set_flash('error', 'Bu talebi iptal etme yetkiniz yok.');
        redirect('requests/index.php');
    }
    if ((string) $request['request_status'] !== 'bekliyor') {
        set_flash('warning', 'Sadece bekleyen talepler iptal edilebilir.');
        redirect('requests/index.php');
    }

    execute_query('UPDATE book_requests SET request_status = "iptal", updated_at = NOW() WHERE id = :id', ['id' => $id]);
    execute_query(
        'UPDATE books SET status = "askıda", updated_at = NOW()
         WHERE id = :id AND NOT EXISTS (
            SELECT 1 FROM book_requests WHERE book_id = :id2 AND request_status IN ("bekliyor","onaylandı")
         )',
        ['id' => (int) $request['book_id'], 'id2' => (int) $request['book_id']]
    );
    log_activity((int) $user['id'], 'Talep', 'Talep iptal edildi', (string) $request['title']);
    set_flash('success', 'Talep iptal edildi.');
    redirect('requests/index.php');
}

if (!can_manage_request($request)) {
    set_flash('error', 'Bu talep için işlem yetkiniz yok.');
    redirect('requests/manage.php');
}

if ((string) $request['request_status'] !== 'bekliyor') {
    set_flash('warning', 'Sadece bekleyen talepler işleme alınabilir.');
    redirect('requests/manage.php');
}

if ($action === 'approve') {
    db()->beginTransaction();
    try {
        execute_query('UPDATE book_requests SET request_status = "onaylandı", donor_note = :note, updated_at = NOW() WHERE id = :id', [
            'note' => 'Talep onaylandı.',
            'id' => $id,
        ]);
        execute_query('UPDATE books SET status = "talep edildi", updated_at = NOW() WHERE id = :id', ['id' => (int) $request['book_id']]);
        execute_query(
            'UPDATE book_requests SET request_status = "reddedildi", donor_note = "Başka bir talep onaylandı.", updated_at = NOW()
             WHERE book_id = :book_id AND id != :id AND request_status = "bekliyor"',
            ['book_id' => (int) $request['book_id'], 'id' => $id]
        );
        execute_query(
            'INSERT INTO matches (book_id, request_id, donor_user_id, requester_user_id, delivery_status, created_at, updated_at)
             VALUES (:book_id, :request_id, :donor_user_id, :requester_user_id, "bekliyor", NOW(), NOW())',
            [
                'book_id' => (int) $request['book_id'],
                'request_id' => $id,
                'donor_user_id' => (int) $request['donor_user_id'],
                'requester_user_id' => (int) $request['requester_user_id'],
            ]
        );
        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }

    create_notification((int) $request['requester_user_id'], 'Talebiniz onaylandı', $request['title'] . ' için talebiniz onaylandı.', 'başarı', app_url('matches/index.php'));
    log_activity((int) $user['id'], 'Talep', 'Talep onaylandı', (string) $request['title']);
    set_flash('success', 'Talep onaylandı ve eşleşme oluşturuldu.');
    redirect('requests/manage.php');
}

if ($action === 'reject') {
    execute_query('UPDATE book_requests SET request_status = "reddedildi", donor_note = :note, updated_at = NOW() WHERE id = :id', [
        'note' => 'Talep uygun bulunmadı.',
        'id' => $id,
    ]);
    execute_query(
        'UPDATE books SET status = "askıda", updated_at = NOW()
         WHERE id = :id AND NOT EXISTS (
            SELECT 1 FROM book_requests WHERE book_id = :id2 AND request_status IN ("bekliyor","onaylandı")
         )',
        ['id' => (int) $request['book_id'], 'id2' => (int) $request['book_id']]
    );
    create_notification((int) $request['requester_user_id'], 'Talebiniz reddedildi', $request['title'] . ' için talebiniz reddedildi.', 'uyarı', app_url('requests/index.php'));
    log_activity((int) $user['id'], 'Talep', 'Talep reddedildi', (string) $request['title']);
    set_flash('success', 'Talep reddedildi.');
    redirect('requests/manage.php');
}

set_flash('error', 'Geçersiz işlem.');
redirect('requests/manage.php');

