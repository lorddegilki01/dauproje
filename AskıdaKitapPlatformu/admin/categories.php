<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$activeMenu = '';
$pageTitle = 'Kategori Yönetimi';
$errors = [];

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create') {
        $name = normalize_text((string) ($_POST['category_name'] ?? ''));
        if ($name === '') {
            $errors[] = 'Kategori adı zorunludur.';
        }
        if (fetch_one('SELECT id FROM categories WHERE category_name = :name', ['name' => $name])) {
            $errors[] = 'Bu kategori zaten mevcut.';
        }
        if (!$errors) {
            execute_query('INSERT INTO categories (category_name, is_active, created_at) VALUES (:name, 1, NOW())', ['name' => $name]);
            set_flash('success', 'Kategori eklendi.');
            redirect('admin/categories.php');
        }
    }

    if ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        $row = fetch_one('SELECT id, is_active FROM categories WHERE id = :id', ['id' => $id]);
        if ($row) {
            execute_query('UPDATE categories SET is_active = :is_active WHERE id = :id', [
                'is_active' => ((int) $row['is_active'] === 1) ? 0 : 1,
                'id' => $id,
            ]);
            set_flash('success', 'Kategori durumu güncellendi.');
            redirect('admin/categories.php');
        }
    }
}

$categories = fetch_all(
    'SELECT c.*, COUNT(b.id) book_count
     FROM categories c
     LEFT JOIN books b ON b.category_id = c.id
     GROUP BY c.id
     ORDER BY c.category_name'
);

require __DIR__ . '/../includes/header.php';
?>
<section class="card">
    <h2>Yeni Kategori</h2>
    <?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
    <form method="post" class="form inline-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="create">
        <label>Kategori Adı
            <input type="text" name="category_name" required>
        </label>
        <button class="btn primary" type="submit">Ekle</button>
    </form>
</section>

<section class="card">
    <h2>Kategori Listesi</h2>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Kategori</th><th>Kitap Sayısı</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?= e((string) $category['category_name']) ?></td>
                        <td><?= e((string) $category['book_count']) ?></td>
                        <td><span class="<?= (int) $category['is_active'] === 1 ? 'badge success' : 'badge danger' ?>"><?= (int) $category['is_active'] === 1 ? 'Aktif' : 'Pasif' ?></span></td>
                        <td>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= e((string) $category['id']) ?>">
                                <button class="btn ghost" type="submit"><?= (int) $category['is_active'] === 1 ? 'Pasifleştir' : 'Aktifleştir' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

