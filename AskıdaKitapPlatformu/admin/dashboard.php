<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$activeMenu = '';
$pageTitle = 'Admin Paneli';

$stats = [
    'total_users' => count_value('SELECT COUNT(*) FROM users'),
    'active_books' => count_value('SELECT COUNT(*) FROM books WHERE status = "askıda" AND is_active = 1'),
    'pending_requests' => count_value('SELECT COUNT(*) FROM book_requests WHERE request_status = "bekliyor"'),
    'waiting_matches' => count_value('SELECT COUNT(*) FROM matches WHERE delivery_status = "bekliyor"'),
    'completed_matches' => count_value('SELECT COUNT(*) FROM matches WHERE delivery_status = "teslim edildi"'),
];

$popularCategories = fetch_all(
    'SELECT c.category_name, COUNT(*) total
     FROM books b
     INNER JOIN categories c ON c.id = b.category_id
     GROUP BY c.id, c.category_name
     ORDER BY total DESC
     LIMIT 5'
);

$cityDistribution = fetch_all(
    'SELECT city, COUNT(*) total
     FROM books
     WHERE is_active = 1
     GROUP BY city
     ORDER BY total DESC
     LIMIT 6'
);

$recentActivities = fetch_all(
    'SELECT a.*, u.full_name
     FROM activity_logs a
     LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.created_at DESC
     LIMIT 10'
);

require __DIR__ . '/../includes/header.php';
?>
<section class="stats-grid">
    <article class="card stat"><h3>Toplam Kullanıcı</h3><strong><?= e((string) $stats['total_users']) ?></strong></article>
    <article class="card stat"><h3>Aktif Askıdaki</h3><strong><?= e((string) $stats['active_books']) ?></strong></article>
    <article class="card stat"><h3>Bekleyen Talep</h3><strong><?= e((string) $stats['pending_requests']) ?></strong></article>
    <article class="card stat"><h3>Bekleyen Teslim</h3><strong><?= e((string) $stats['waiting_matches']) ?></strong></article>
</section>

<section class="dashboard-grid">
    <article class="card">
        <div class="card-head">
            <h2>Hızlı Yönetim</h2>
        </div>
        <div class="quick-links">
            <a class="btn ghost" href="<?= e(app_url('admin/users.php')) ?>">Kullanıcı Yönetimi</a>
            <a class="btn ghost" href="<?= e(app_url('admin/categories.php')) ?>">Kategori Yönetimi</a>
            <a class="btn ghost" href="<?= e(app_url('admin/books.php')) ?>">Kitap Yönetimi</a>
            <a class="btn ghost" href="<?= e(app_url('admin/requests.php')) ?>">Talep Yönetimi</a>
            <a class="btn ghost" href="<?= e(app_url('admin/matches.php')) ?>">Teslim Yönetimi</a>
            <a class="btn ghost" href="<?= e(app_url('admin/contacts.php')) ?>">İletişim Mesajları</a>
            <a class="btn ghost" href="<?= e(app_url('admin/reports.php')) ?>">Raporlar</a>
        </div>
    </article>

    <article class="card">
        <h2>Popüler Kategoriler</h2>
        <ul class="list">
            <?php if (!$popularCategories): ?>
                <li>Veri bulunmuyor.</li>
            <?php endif; ?>
            <?php foreach ($popularCategories as $item): ?>
                <li><?= e((string) $item['category_name']) ?> <strong><?= e((string) $item['total']) ?></strong></li>
            <?php endforeach; ?>
        </ul>
    </article>

    <article class="card">
        <h2>Şehirlere Göre Dağılım</h2>
        <ul class="list">
            <?php if (!$cityDistribution): ?>
                <li>Veri bulunmuyor.</li>
            <?php endif; ?>
            <?php foreach ($cityDistribution as $city): ?>
                <li><?= e((string) $city['city']) ?> <strong><?= e((string) $city['total']) ?></strong></li>
            <?php endforeach; ?>
        </ul>
    </article>
</section>

<section class="card">
    <h2>Son Sistem Hareketleri</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Kullanıcı</th>
                    <th>Modül</th>
                    <th>İşlem</th>
                    <th>Detay</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$recentActivities): ?>
                    <tr><td colspan="5">Kayıt bulunmuyor.</td></tr>
                <?php endif; ?>
                <?php foreach ($recentActivities as $activity): ?>
                    <tr>
                        <td><?= e(format_date((string) $activity['created_at'])) ?></td>
                        <td><?= e((string) ($activity['full_name'] ?? 'Sistem')) ?></td>
                        <td><?= e((string) $activity['module_name']) ?></td>
                        <td><?= e((string) $activity['action_name']) ?></td>
                        <td><?= e((string) ($activity['details'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
