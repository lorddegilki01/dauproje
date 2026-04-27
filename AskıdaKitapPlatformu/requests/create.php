<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_login();

$bookId = (int) ($_GET['book_id'] ?? 0);
$book = fetch_one(
    'SELECT b.*, u.full_name donor_name
     FROM books b
     LEFT JOIN users u ON u.id = b.donor_user_id
     WHERE b.id = :id AND b.is_active = 1',
    ['id' => $bookId]
);

if (!$book || (string) $book['status'] !== 'askıda') {
    set_flash('error', 'Talep için uygun kitap bulunamadı.');
    redirect('books/index.php');
}
if ((int) $book['donor_user_id'] === (int) current_user()['id']) {
    set_flash('error', 'Kendi kitabınıza talep gönderemezsiniz.');
    redirect('books/view.php?id=' . $bookId);
}

$already = fetch_one(
    'SELECT id FROM book_requests WHERE book_id = :book_id AND requester_user_id = :user_id AND request_status IN ("bekliyor","onaylandı")',
    ['book_id' => $bookId, 'user_id' => (int) current_user()['id']]
);
if ($already) {
    set_flash('warning', 'Bu kitap için aktif bir talebiniz zaten bulunuyor.');
    redirect('books/view.php?id=' . $bookId);
}

$errors = [];
if (is_post()) {
    verify_csrf();
    $note = normalize_text((string) ($_POST['request_note'] ?? ''));
    if ($note === '') {
        $errors[] = 'Talep açıklaması zorunludur.';
    }

    if (!$errors) {
        execute_query(
            'INSERT INTO book_requests (book_id, requester_user_id, request_note, request_status, created_at, updated_at)
             VALUES (:book_id, :requester_user_id, :request_note, "bekliyor", NOW(), NOW())',
            [
                'book_id' => $bookId,
                'requester_user_id' => (int) current_user()['id'],
                'request_note' => $note,
            ]
        );

        execute_query('UPDATE books SET status = "talep edildi", updated_at = NOW() WHERE id = :id', ['id' => $bookId]);
        create_notification((int) $book['donor_user_id'], 'Yeni kitap talebi', $book['title'] . ' için yeni talep oluşturuldu.', 'uyarı', app_url('requests/manage.php'));
        log_activity((int) current_user()['id'], 'Talep', 'Talep oluşturuldu', (string) $book['title']);
        set_flash('success', 'Talebiniz başarıyla gönderildi.');
        redirect('requests/index.php');
    }
}

$activeMenu = 'my_requests';
$pageTitle = 'Kitap Talebi Oluştur';
require __DIR__ . '/../includes/header.php';
?>
<section class="card form-card">
    <h2>Kitap Talebi</h2>
    <p><strong>Kitap:</strong> <?= e((string) $book['title']) ?> · <strong>Bağışçı:</strong> <?= e((string) $book['donor_name']) ?></p>
    <?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
    <form method="post" class="form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Talep Açıklaması
            <textarea name="request_note" rows="5" required><?= e((string) ($_POST['request_note'] ?? '')) ?></textarea>
        </label>
        <div class="actions">
            <button class="btn primary" type="submit">Talep Gönder</button>
            <a class="btn ghost" href="<?= e(app_url('books/view.php?id=' . $bookId)) ?>">Vazgeç</a>
        </div>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

