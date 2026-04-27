<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_login();

$activeMenu = 'my_books';
$pageTitle = 'Bağışlarım';
$user = current_user();

if (is_admin()) {
    $books = fetch_all(
        'SELECT b.*, c.category_name, u.full_name donor_name
         FROM books b
         LEFT JOIN categories c ON c.id = b.category_id
         LEFT JOIN users u ON u.id = b.donor_user_id
         ORDER BY b.created_at DESC'
    );
} else {
    $books = fetch_all(
        'SELECT b.*, c.category_name
         FROM books b
         LEFT JOIN categories c ON c.id = b.category_id
         WHERE b.donor_user_id = :id
         ORDER BY b.created_at DESC',
        ['id' => (int) $user['id']]
    );
}

require __DIR__ . '/../includes/header.php';
?>
<section class="card">
    <div class="card-head">
        <h2><?= is_admin() ? 'Tüm Kitap Kayıtları' : 'Bağışladığım Kitaplar' ?></h2>
        <a class="btn primary" href="<?= e(app_url('books/form.php')) ?>">Yeni Kitap Ekle</a>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Kitap</th>
                    <th>Kategori</th>
                    <th>Şehir</th>
                    <th>Durum</th>
                    <?php if (is_admin()): ?><th>Bağışçı</th><?php endif; ?>
                    <th>Tarih</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$books): ?>
                    <tr><td colspan="<?= is_admin() ? '7' : '6' ?>">Henüz kayıt bulunmuyor.</td></tr>
                <?php endif; ?>
                <?php foreach ($books as $book): ?>
                    <tr>
                        <td><?= e((string) $book['title']) ?></td>
                        <td><?= e((string) ($book['category_name'] ?? 'Genel')) ?></td>
                        <td><?= e((string) $book['city']) ?></td>
                        <td><span class="<?= e(badge_class((string) $book['status'])) ?>"><?= e(book_status_label((string) $book['status'])) ?></span></td>
                        <?php if (is_admin()): ?><td><?= e((string) ($book['donor_name'] ?? '-')) ?></td><?php endif; ?>
                        <td><?= e(format_date((string) $book['created_at'])) ?></td>
                        <td>
                            <a href="<?= e(app_url('books/view.php?id=' . (int) $book['id'])) ?>">Detay</a> ·
                            <a href="<?= e(app_url('books/form.php?id=' . (int) $book['id'])) ?>">Düzenle</a> ·
                            <a class="danger-link" href="<?= e(app_url('books/delete.php?id=' . (int) $book['id'])) ?>" onclick="return confirm('Bu kaydı silmek istediğinize emin misiniz?')">Sil</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

