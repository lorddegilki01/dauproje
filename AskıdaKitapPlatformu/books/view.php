<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

$activeMenu = 'books';
$pageTitle = 'Kitap Detayı';
$id = (int) ($_GET['id'] ?? 0);

$book = fetch_one(
    'SELECT b.*, c.category_name, u.full_name donor_name, u.email donor_email, u.phone donor_phone
     FROM books b
     LEFT JOIN categories c ON c.id = b.category_id
     LEFT JOIN users u ON u.id = b.donor_user_id
     WHERE b.id = :id',
    ['id' => $id]
);

if (!$book) {
    set_flash('error', 'Kitap kaydı bulunamadı.');
    redirect('books/index.php');
}

$existingRequest = null;
if (is_logged_in()) {
    $existingRequest = fetch_one(
        'SELECT id, request_status FROM book_requests
         WHERE book_id = :book_id AND requester_user_id = :user_id
         ORDER BY id DESC
         LIMIT 1',
        ['book_id' => $id, 'user_id' => (int) current_user()['id']]
    );
}

require __DIR__ . '/../includes/header.php';
?>
<section class="card detail-card">
    <h2><?= e((string) $book['title']) ?></h2>
    <p class="muted"><?= e((string) $book['author']) ?> · <?= e((string) ($book['category_name'] ?? 'Genel')) ?></p>
    <p><?= nl2br(e((string) $book['description'])) ?></p>

    <div class="detail-grid">
        <div><strong>Şehir:</strong> <?= e((string) $book['city']) ?></div>
        <div><strong>Teslim Şekli:</strong> <?= e((string) $book['delivery_type']) ?></div>
        <div><strong>İletişim Tercihi:</strong> <?= e((string) $book['contact_preference']) ?></div>
        <div><strong>Durum:</strong> <span class="<?= e(badge_class((string) $book['status'])) ?>"><?= e(book_status_label((string) $book['status'])) ?></span></div>
        <div><strong>Bağışçı:</strong> <?= e((string) $book['donor_name']) ?></div>
    </div>

    <div class="actions">
        <?php if (is_logged_in() && (int) current_user()['id'] !== (int) $book['donor_user_id'] && in_array((string) $book['status'], ['askida', 'askıda'], true)): ?>
            <?php if ($existingRequest): ?>
                <span class="<?= e(badge_class((string) $existingRequest['request_status'])) ?>">
                    Talebiniz: <?= e(request_status_label((string) $existingRequest['request_status'])) ?>
                </span>
            <?php else: ?>
                <a class="btn primary shine" href="<?= e(app_url('requests/create.php?book_id=' . (int) $book['id'])) ?>">Talep Gönder</a>
            <?php endif; ?>
        <?php endif; ?>
        <a class="btn ghost" href="<?= e(app_url('books/index.php')) ?>">Listeye Dön</a>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
