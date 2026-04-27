<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$activeMenu = 'products';
$pageTitle = 'Ürün Yönetimi';

$q = trim((string) ($_GET['q'] ?? ''));
$categoryId = (int) ($_GET['category_id'] ?? 0);

$where = ['1=1'];
$params = [];
if ($q !== '') {
    $where[] = '(p.product_name LIKE :q OR p.product_code LIKE :q OR p.barcode LIKE :q)';
    $params['q'] = '%' . $q . '%';
}
if ($categoryId > 0) {
    $where[] = 'p.category_id = :category_id';
    $params['category_id'] = $categoryId;
}

$products = fetch_all(
    'SELECT p.*, c.category_name
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY p.product_name',
    $params
);
$categories = fetch_all('SELECT id, category_name FROM categories ORDER BY category_name');
require __DIR__ . '/../../includes/header.php';
?>
<section class="card">
    <div class="card-head">
        <h3>Ürün Listesi</h3>
        <a class="btn primary" href="<?= e(app_url('modules/products/form.php')) ?>">Yeni Ürün</a>
    </div>
    <form method="get" class="form inline">
        <input type="text" name="q" placeholder="Ürün adı / kod / barkod ara..." value="<?= e($q) ?>">
        <select name="category_id">
            <option value="0">Tüm Kategoriler</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= e((string) $category['id']) ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>><?= e((string) $category['category_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn ghost" type="submit">Filtrele</button>
    </form>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Ürün</th><th>Kategori</th><th>Fiyat</th><th>Stok</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
            <?php if (!$products): ?><tr><td colspan="6">Kayıt bulunamadı.</td></tr><?php endif; ?>
            <?php foreach ($products as $row): ?>
                <tr>
                    <td><?= e((string) $row['product_name']) ?><br><small><?= e((string) $row['product_code']) ?></small></td>
                    <td><?= e((string) ($row['category_name'] ?? '-')) ?></td>
                    <td><?= e(format_money((float) $row['sale_price'])) ?></td>
                    <td><?= e((string) $row['stock_quantity']) ?> <?= e((string) $row['unit_type']) ?></td>
                    <td><span class="<?= e(badge_status((string) $row['status'])) ?>"><?= e(status_label((string) $row['status'])) ?></span></td>
                    <td class="actions">
                        <a href="<?= e(app_url('modules/products/view.php?id=' . (int) $row['id'])) ?>">Detay</a>
                        <a href="<?= e(app_url('modules/products/form.php?id=' . (int) $row['id'])) ?>">Düzenle</a>
                        <form method="post" action="<?= e(app_url('modules/products/delete.php')) ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
                            <button class="btn-link danger" type="submit" data-confirm="Ürün silinsin mi?">Sil</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
