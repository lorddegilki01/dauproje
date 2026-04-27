<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$activeMenu = 'home';
$pageTitle = 'Ana Sayfa';

$stats = [
    'total_books' => count_value('SELECT COUNT(*) FROM books'),
    'active_books' => count_value('SELECT COUNT(*) FROM books WHERE status = "askıda" AND is_active = 1'),
    'completed_matches' => count_value('SELECT COUNT(*) FROM matches WHERE delivery_status = "teslim edildi"'),
    'total_users' => count_value('SELECT COUNT(*) FROM users WHERE is_active = 1'),
];

$latestBooks = fetch_all(
    'SELECT b.id, b.title, b.author, b.city, b.status, c.category_name
     FROM books b
     LEFT JOIN categories c ON c.id = b.category_id
     WHERE b.status = "askıda" AND b.is_active = 1
     ORDER BY b.created_at DESC
     LIMIT 8'
);

require __DIR__ . '/includes/header.php';
?>
<section class="hero card">
    <div>
        <h1>Bir Kitap Askıya, Bir Gelecek Umuda</h1>
        <p>
            Askıda Kitap Platformu; bağış yapmak isteyenlerle kitap ihtiyacı olanları güvenli, şeffaf ve düzenli
            bir dayanışma modeliyle buluşturur.
        </p>
        <div class="actions">
            <a class="btn primary" href="<?= e(app_url('books/index.php')) ?>">Askıdaki Kitapları Gör</a>
            <a class="btn ghost" href="<?= e(app_url('books/form.php')) ?>">Kitap Bağışla</a>
        </div>
    </div>
</section>

<section class="stats-grid">
    <article class="card stat"><h3>Toplam Kitap</h3><strong><?= e((string) $stats['total_books']) ?></strong></article>
    <article class="card stat"><h3>Aktif Askıda</h3><strong><?= e((string) $stats['active_books']) ?></strong></article>
    <article class="card stat"><h3>Tamamlanan Teslim</h3><strong><?= e((string) $stats['completed_matches']) ?></strong></article>
    <article class="card stat"><h3>Topluluk Üyesi</h3><strong><?= e((string) $stats['total_users']) ?></strong></article>
</section>

<section class="card">
    <h2>Nasıl Çalışır?</h2>
    <div class="how-grid">
        <div>
            <h4>1. Kitap Bağışı Oluştur</h4>
            <p>Bağışçı kullanıcılar kitabın bilgilerini ve teslim yöntemini sisteme ekler.</p>
        </div>
        <div>
            <h4>2. Talep Gönder</h4>
            <p>İhtiyaç sahibi okuyucu uygun kitabı seçer ve talep notuyla başvuru yapar.</p>
        </div>
        <div>
            <h4>3. Eşleşme ve Teslim</h4>
            <p>Onaylanan talepler eşleşmeye döner; teslim süreci panel üzerinden takip edilir.</p>
        </div>
    </div>
</section>

<section class="card">
    <div class="card-head">
        <h2>Son Eklenen Askıdaki Kitaplar</h2>
        <a href="<?= e(app_url('books/index.php')) ?>">Tümünü Gör</a>
    </div>
    <div class="book-grid">
        <?php if (!$latestBooks): ?>
            <p class="empty">Henüz askıda kitap bulunmuyor.</p>
        <?php endif; ?>
        <?php foreach ($latestBooks as $book): ?>
            <article class="book-card">
                <h4><?= e((string) $book['title']) ?></h4>
                <p><?= e((string) $book['author']) ?> · <?= e((string) ($book['category_name'] ?? 'Genel')) ?></p>
                <p><?= e((string) $book['city']) ?></p>
                <span class="<?= e(badge_class((string) $book['status'])) ?>"><?= e(book_status_label((string) $book['status'])) ?></span>
                <a class="btn ghost" href="<?= e(app_url('books/view.php?id=' . (int) $book['id'])) ?>">Detay</a>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

