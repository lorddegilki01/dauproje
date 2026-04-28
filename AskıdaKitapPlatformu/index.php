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
<div class="landing-v2" data-landing-parallax>
    <section class="v2-hero">
        <div class="v2-bg-grid"></div>
        <div class="v2-orb v2-orb-a"></div>
        <div class="v2-orb v2-orb-b"></div>
        <div class="v2-orb v2-orb-c"></div>
        <div class="v2-floating v2-float-1"></div>
        <div class="v2-floating v2-float-2"></div>
        <div class="v2-floating v2-float-3"></div>

        <div class="v2-hero-content">
            <span class="v2-chip">Sosyal Dayanışma Platformu</span>
            <h1>Bir Kitap Askıya, Bir Gelecek Umuda</h1>
            <p>
                Bağışçılar ve ihtiyaç sahibi okurları modern, güvenli ve takip edilebilir bir
                dayanışma modeliyle buluşturuyoruz.
            </p>

            <div class="v2-badges">
                <span>Güvenli Teslim Süreci</span>
                <span>Anlık Bildirim Altyapısı</span>
                <span>Doğrulanmış Kullanıcı Akışı</span>
            </div>

            <div class="actions">
                <a class="btn primary shine" href="<?= e(app_url('books/index.php')) ?>">Askıdaki Kitapları Keşfet</a>
                <a class="btn ghost" href="<?= e(app_url('books/form.php')) ?>">Hemen Bağış Yap</a>
            </div>
        </div>

        <aside class="v2-hero-visual">
            <div class="v2-book-stage">
                <span class="v2-book v2-book-1"></span>
                <span class="v2-book v2-book-2"></span>
                <span class="v2-book v2-book-3"></span>
                <span class="v2-book v2-book-4"></span>
            </div>
            <div class="v2-glow-ring"></div>
        </aside>
    </section>

    <section class="v2-stats">
        <article class="v2-stat-card">
            <i class="sg sg-books"></i>
            <div>
                <p>Toplam Kitap</p>
                <strong data-count="<?= e((string) $stats['total_books']) ?>">0</strong>
            </div>
        </article>
        <article class="v2-stat-card">
            <i class="sg sg-active"></i>
            <div>
                <p>Aktif Askıdaki</p>
                <strong data-count="<?= e((string) $stats['active_books']) ?>">0</strong>
            </div>
        </article>
        <article class="v2-stat-card">
            <i class="sg sg-match"></i>
            <div>
                <p>Tamamlanan Teslim</p>
                <strong data-count="<?= e((string) $stats['completed_matches']) ?>">0</strong>
            </div>
        </article>
        <article class="v2-stat-card">
            <i class="sg sg-users"></i>
            <div>
                <p>Topluluk Üyesi</p>
                <strong data-count="<?= e((string) $stats['total_users']) ?>">0</strong>
            </div>
        </article>
    </section>

    <section class="v2-process">
        <h2>Nasıl Çalışır?</h2>
        <div class="v2-process-line"></div>
        <div class="v2-process-grid">
            <article class="v2-step">
                <span class="v2-step-no">1</span>
                <div class="v2-step-icon">🎁</div>
                <h3>Kitap Bağışı Oluştur</h3>
                <p>Kitap bilgilerini ekleyin, platformda askıya çıkarın.</p>
            </article>
            <article class="v2-step">
                <span class="v2-step-no">2</span>
                <div class="v2-step-icon">📝</div>
                <h3>Talep Gönder</h3>
                <p>Okurlar, uygun kitap için kısa notla talep oluşturur.</p>
            </article>
            <article class="v2-step">
                <span class="v2-step-no">3</span>
                <div class="v2-step-icon">🚚</div>
                <h3>Eşleşme ve Teslim</h3>
                <p>Onaylanan talepler takipli teslim sürecine geçer.</p>
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

    <section class="v2-cta">
        <div>
            <h3>Bir kitabı askıya bırak, bir geleceği güçlendir.</h3>
            <p>Topluluğa katıl, bağış yap veya kitap talep ederek dayanışmayı büyüt.</p>
        </div>
        <div class="actions">
            <a class="btn primary shine" href="<?= e(app_url('auth/register.php')) ?>">Topluluğa Katıl</a>
            <a class="btn ghost" href="<?= e(app_url('pages/about.php')) ?>">Daha Fazla Bilgi</a>
        </div>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
