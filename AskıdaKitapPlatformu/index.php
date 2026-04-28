<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$activeMenu = 'home';
$pageTitle = 'Ana Sayfa';

$stats = [
    'total_books' => count_value('SELECT COUNT(*) FROM books'),
    'active_books' => count_value('SELECT COUNT(*) FROM books WHERE status IN ("askida","askıda") AND is_active = 1'),
    'completed_matches' => count_value('SELECT COUNT(*) FROM matches WHERE delivery_status = "teslim edildi"'),
    'total_users' => count_value('SELECT COUNT(*) FROM users WHERE is_active = 1'),
];

$latestBooks = fetch_all(
    'SELECT b.id, b.title, b.author, b.city, b.status, c.category_name
     FROM books b
     LEFT JOIN categories c ON c.id = b.category_id
     WHERE b.status IN ("askida","askıda") AND b.is_active = 1
     ORDER BY b.created_at DESC
     LIMIT 6'
);

require __DIR__ . '/includes/header.php';
?>
<section class="landing-hero">
    <div class="hero-layer hero-layer-1"></div>
    <div class="hero-layer hero-layer-2"></div>
    <div class="hero-layer hero-layer-3"></div>

    <div class="hero-grid">
        <div class="hero-copy">
            <span class="hero-pill">Sosyal Dayanışma Platformu</span>
            <h1>Bir Kitap Askıya, Bir Gelecek Umuda</h1>
            <p>
                İhtiyaç sahibi okurlarla bağışçıları güvenli, şeffaf ve sürdürülebilir
                bir platformda buluşturuyoruz. Her bağış, bir insanın geleceğine dokunur.
            </p>

            <div class="hero-badges">
                <span><i>📦</i> Güvenli Teslim Süreci</span>
                <span><i>🔔</i> Anlık Bildirim Sistemi</span>
                <span><i>🛡️</i> Doğrulanmış Kullanıcı Akışı</span>
            </div>

            <div class="actions hero-actions">
                <a class="btn primary shine" href="<?= e(app_url('books/index.php')) ?>">Askıdaki Kitapları Keşfet</a>
                <a class="btn ghost" href="<?= e(app_url('books/form.php')) ?>">Hemen Bağış Yap</a>
            </div>
        </div>

        <div class="hero-visual">
            <div class="book-stack">
                <span class="book book-a">📘</span>
                <span class="book book-b">📗</span>
                <span class="book book-c">📙</span>
                <span class="book book-d">📕</span>
            </div>
            <div class="hero-glow"></div>
            <div class="hero-dots"></div>
        </div>
    </div>
</section>

<section class="stats-grid stats-premium">
    <article class="stat-card">
        <div class="stat-icon">📚</div>
        <div>
            <p>Toplam Kitap</p>
            <strong data-count="<?= e((string) $stats['total_books']) ?>">0</strong>
        </div>
    </article>
    <article class="stat-card">
        <div class="stat-icon">🎯</div>
        <div>
            <p>Aktif Askıdaki</p>
            <strong data-count="<?= e((string) $stats['active_books']) ?>">0</strong>
        </div>
    </article>
    <article class="stat-card">
        <div class="stat-icon">🤝</div>
        <div>
            <p>Tamamlanan Teslim</p>
            <strong data-count="<?= e((string) $stats['completed_matches']) ?>">0</strong>
        </div>
    </article>
    <article class="stat-card">
        <div class="stat-icon">🌍</div>
        <div>
            <p>Topluluk Üyesi</p>
            <strong data-count="<?= e((string) $stats['total_users']) ?>">0</strong>
        </div>
    </article>
</section>

<section class="process-section card">
    <div class="card-head">
        <h2>Nasıl Çalışır?</h2>
    </div>
    <div class="process-grid">
        <article class="process-card">
            <span class="process-step">1</span>
            <div class="process-icon">🎁</div>
            <h3>Kitap Bağışı Oluştur</h3>
            <p>Bağışçı, kitap bilgilerini ve teslim yöntemini sisteme girer.</p>
        </article>
        <article class="process-card">
            <span class="process-step">2</span>
            <div class="process-icon">📝</div>
            <h3>Talep Gönder</h3>
            <p>Okur, uygun kitabı seçip talep notuyla başvurusunu tamamlar.</p>
        </article>
        <article class="process-card">
            <span class="process-step">3</span>
            <div class="process-icon">🚚</div>
            <h3>Eşleş ve Teslim Et</h3>
            <p>Onaylanan talepler teslim sürecine düşer, adımlar panelden izlenir.</p>
        </article>
    </div>
</section>

<section class="card">
    <div class="card-head">
        <h2>Son Eklenen Askıdaki Kitaplar</h2>
        <a href="<?= e(app_url('books/index.php')) ?>">Tümünü Gör</a>
    </div>
    <div class="book-grid">
        <?php if (!$latestBooks): ?>
            <article class="card empty-state">
                <h3>Henüz askıda kitap bulunmuyor.</h3>
                <p>İlk bağışı siz başlatabilir, bir okurun hayatına dokunabilirsiniz.</p>
            </article>
        <?php endif; ?>
        <?php foreach ($latestBooks as $book): ?>
            <article class="book-card card">
                <h4><?= e((string) $book['title']) ?></h4>
                <p><?= e((string) $book['author']) ?> · <?= e((string) ($book['category_name'] ?? 'Genel')) ?></p>
                <p><?= e((string) $book['city']) ?></p>
                <span class="<?= e(badge_class((string) $book['status'])) ?>"><?= e(book_status_label((string) $book['status'])) ?></span>
                <a class="btn ghost" href="<?= e(app_url('books/view.php?id=' . (int) $book['id'])) ?>">Detay</a>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="cta-band card">
    <div>
        <h3>Bugün bir kitabı askıya bırak, yarını değiştiren kişi ol.</h3>
        <p>Platforma katıl, bağış yap veya kitap talep ederek dayanışmayı büyüt.</p>
    </div>
    <div class="actions">
        <a class="btn primary shine" href="<?= e(app_url('auth/register.php')) ?>">Topluluğa Katıl</a>
        <a class="btn ghost" href="<?= e(app_url('pages/about.php')) ?>">Daha Fazla Bilgi</a>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
