<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$category = $id ? fetch_one('SELECT * FROM categories WHERE id = :id', ['id' => $id]) : null;
if ($id && !$category) {
    set_flash('error', 'Kategori bulunamadı.');
    redirect('modules/categories/index.php');
}

$activeMenu = 'categories';
$pageTitle = $category ? 'Kategori Düzenle' : 'Kategori Ekle';
$errors = [];

if (is_post()) {
    verify_csrf();
    $name = trim((string) ($_POST['category_name'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));

    if ($name === '') {
        $errors[] = 'Kategori adı zorunludur.';
    }

    $duplicate = fetch_one('SELECT id FROM categories WHERE category_name = :name AND id != :id', ['name' => $name, 'id' => $id]);
    if ($duplicate) {
        $errors[] = 'Bu kategori adı zaten var.';
    }

    if (!$errors) {
        if ($category) {
            execute_query('UPDATE categories SET category_name=:name, description=:description WHERE id=:id', ['name' => $name, 'description' => $description, 'id' => $id]);
            set_flash('success', 'Kategori güncellendi.');
        } else {
            execute_query('INSERT INTO categories (category_name, description) VALUES (:name,:description)', ['name' => $name, 'description' => $description]);
            set_flash('success', 'Kategori eklendi.');
        }
        redirect('modules/categories/index.php');
    }
}

require __DIR__ . '/../../includes/header.php';
?>
<section class="card">
    <h3><?= e($pageTitle) ?></h3>
    <?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
    <form method="post" class="form grid-2">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Kategori Adı
            <input type="text" name="category_name" value="<?= e((string) ($_POST['category_name'] ?? $category['category_name'] ?? '')) ?>" required>
        </label>
        <label>Açıklama
            <input type="text" name="description" value="<?= e((string) ($_POST['description'] ?? $category['description'] ?? '')) ?>">
        </label>
        <div class="full actions">
            <button class="btn primary" type="submit">Kaydet</button>
            <a class="btn ghost" href="<?= e(app_url('modules/categories/index.php')) ?>">Geri</a>
        </div>
    </form>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

