<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$activeMenu = 'categories';
$pageTitle = 'Kategori Yönetimi';

$categories = fetch_all('SELECT * FROM categories ORDER BY category_name');
require __DIR__ . '/../../includes/header.php';
?>
<section class="card">
    <div class="card-head">
        <h3>Kategoriler</h3>
        <a class="btn primary" href="<?= e(app_url('modules/categories/form.php')) ?>">Yeni Kategori</a>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Ad</th><th>Açıklama</th><th>İşlem</th></tr></thead>
            <tbody>
            <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?= e((string) $category['category_name']) ?></td>
                    <td><?= e((string) $category['description']) ?></td>
                    <td class="actions">
                        <a href="<?= e(app_url('modules/categories/form.php?id=' . (int) $category['id'])) ?>">Düzenle</a>
                        <form method="post" action="<?= e(app_url('modules/categories/delete.php')) ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= e((string) $category['id']) ?>">
                            <button class="btn-link danger" type="submit" data-confirm="Kategori silinsin mi?">Sil</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

