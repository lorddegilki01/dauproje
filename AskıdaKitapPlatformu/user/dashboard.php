<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_login();

$activeMenu = 'panel';
$pageTitle = 'Panelim';
$user = current_user();

$stats = [
    'added_books' => count_value('SELECT COUNT(*) FROM books WHERE donor_user_id = :id', ['id' => (int) $user['id']]),
    'requested_books' => count_value('SELECT COUNT(*) FROM book_requests WHERE requester_user_id = :id', ['id' => (int) $user['id']]),
    'pending_requests' => count_value('SELECT COUNT(*) FROM book_requests WHERE requester_user_id = :id AND request_status = "bekliyor"', ['id' => (int) $user['id']]),
    'delivered_matches' => count_value('SELECT COUNT(*) FROM matches WHERE (requester_user_id=:id OR donor_user_id=:id) AND delivery_status = "teslim edildi"', ['id' => (int) $user['id']]),
];

$recentRequests = fetch_all(
    'SELECT r.*, b.title
     FROM book_requests r
     INNER JOIN books b ON b.id = r.book_id
     WHERE r.requester_user_id = :id
     ORDER BY r.created_at DESC
     LIMIT 5',
    ['id' => (int) $user['id']]
);

$notifications = fetch_all(
    'SELECT * FROM notifications
     WHERE user_id IS NULL OR user_id = :id
     ORDER BY created_at DESC
     LIMIT 6',
    ['id' => (int) $user['id']]
);

require __DIR__ . '/../includes/header.php';
?>
<section class="stats-grid">
    <article class="card stat"><h3>Eklediğim Kitap</h3><strong><?= e((string) $stats['added_books']) ?></strong></article>
    <article class="card stat"><h3>Talep Ettiğim</h3><strong><?= e((string) $stats['requested_books']) ?></strong></article>
    <article class="card stat"><h3>Bekleyen Talep</h3><strong><?= e((string) $stats['pending_requests']) ?></strong></article>
    <article class="card stat"><h3>Teslim Edilen</h3><strong><?= e((string) $stats['delivered_matches']) ?></strong></article>
</section>

<section class="dashboard-grid">
    <article class="card">
        <h2>Son Taleplerim</h2>
        <ul class="list">
            <?php if (!$recentRequests): ?><li>Talep kaydınız bulunmuyor.</li><?php endif; ?>
            <?php foreach ($recentRequests as $request): ?>
                <li>
                    <?= e((string) $request['title']) ?>
                    <span class="<?= e(badge_class((string) $request['request_status'])) ?>"><?= e(request_status_label((string) $request['request_status'])) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>
    <article class="card">
        <h2>Bildirimler</h2>
        <ul class="list">
            <?php if (!$notifications): ?><li>Yeni bildirim bulunmuyor.</li><?php endif; ?>
            <?php foreach ($notifications as $note): ?>
                <li>
                    <a href="<?= e((string) $note['target_url']) ?>"><?= e((string) $note['title']) ?></a>
                    <small><?= e(format_date((string) $note['created_at'])) ?></small>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

