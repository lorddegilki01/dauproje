<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

$activeMenu = 'books';
$pageTitle = 'Askıdaki Kitaplar';

$q = normalize_text((string) ($_GET['q'] ?? ''));
$categoryId = (int) ($_GET['category_id'] ?? 0);
$city = normalize_text((string) ($_GET['city'] ?? ''));

$where = ['b.is_active = 1', 'b.status IN ("askıda","talep edildi")'];
$params = [];

if ($q !== '') {
    $where[] = '(b.title LIKE :q OR b.author LIKE :q OR b.description LIKE :q)';
    $params['q'] = '%' . $q . '%';
}
if ($categoryId > 0) {
    $where[] = 'b.category_id = :category_id';
    $params['category_id'] = $categoryId;
}
if ($city !== '') {
    $where[] = 'b.city = :city';
    $params['city'] = $city;
}

$sql = 'SELECT b.*, c.category_name, u.full_name donor_name
        FROM books b
        LEFT JOIN categories c ON c.id = b.category_id
        LEFT JOIN users u ON u.id = b.donor_user_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY b.created_at DESC';

$books = fetch_all($sql, $params);
$categories = fetch_all('SELECT id, category_name FROM categories WHERE is_active = 1 ORDER BY category_name');
$cities = fetch_all('SELECT DISTINCT city FROM books WHERE is_active = 1 ORDER BY city');

require __DIR__ . '/../includes/header.php';
?>
<section class="card">
    <div class="card-head">
        <h2>Askıdaki Kitaplar</h2>
        <?php if (is_logged_in()): ?>
            <a class="btn primary" href="<?= e(app_url('books/form.php')) ?>">Kitap Ekle</a>
        <?php endif; ?>
    </div>

    <form method="get" class="filters">
        <label>Arama
            <input type="text" name="q" placeholder="Kitap adı, yazar veya açıklama" value="<?= e($q) ?>">
        </label>
        <label>Kategori
            <select name="category_id">
                <option value="0">Tümü</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e((string) $category['id']) ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>>
                        <?= e((string) $category['category_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Şehir
            <select name="city">
                <option value="">Tümü</option>
                <?php foreach ($cities as $cityRow): ?>
                    <option value="<?= e((string) $cityRow['city']) ?>" <?= $city === (string) $cityRow['city'] ? 'selected' : '' ?>>
                        <?= e((string) $cityRow['city']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="actions">
            <button class="btn primary" type="submit">Filtrele</button>
            <a class="btn ghost" href="<?= e(app_url('books/index.php')) ?>">Sıfırla</a>
        </div>
    </form>
</section>

<section class="book-grid">
    <?php if (!$books): ?>
        <article class="card empty-state">
            <h3>Kriterlere uygun kitap bulunamadı.</h3>
            <p>Filtreleri değiştirerek tekrar deneyebilirsiniz.</p>
        </article>
    <?php endif; ?>
    <?php foreach ($books as $book): ?>
        <article class="book-card card">
            <h3><?= e((string) $book['title']) ?></h3>
            <p><strong>Yazar:</strong> <?= e((string) $book['author']) ?></p>
            <p><strong>Kategori:</strong> <?= e((string) ($book['category_name'] ?? 'Genel')) ?></p>
            <p><strong>Şehir:</strong> <?= e((string) $book['city']) ?></p>
            <p><strong>Bağışçı:</strong> <?= e((string) $book['donor_name']) ?></p>
            <span class="<?= e(badge_class((string) $book['status'])) ?>"><?= e(book_status_label((string) $book['status'])) ?></span>
            <div class="actions">
                <a class="btn ghost" href="<?= e(app_url('books/view.php?id=' . (int) $book['id'])) ?>">Detay</a>
            </div>
        </article>
    <?php endforeach; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

