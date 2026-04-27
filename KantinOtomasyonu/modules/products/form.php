<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$product = $id ? fetch_one('SELECT * FROM products WHERE id = :id', ['id' => $id]) : null;
if ($id && !$product) {
    set_flash('error', 'Ürün bulunamadı.');
    redirect('modules/products/index.php');
}

$activeMenu = 'products';
$pageTitle = $product ? 'Ürün Düzenle' : 'Ürün Ekle';
$categories = fetch_all('SELECT id, category_name FROM categories ORDER BY category_name');
$errors = [];

if (is_post()) {
    verify_csrf();
    $data = [
        'category_id' => (int) ($_POST['category_id'] ?? 0),
        'product_name' => trim((string) ($_POST['product_name'] ?? '')),
        'product_code' => trim((string) ($_POST['product_code'] ?? '')),
        'barcode' => trim((string) ($_POST['barcode'] ?? '')),
        'purchase_price' => ensure_positive_number((string) ($_POST['purchase_price'] ?? '0')),
        'sale_price' => ensure_positive_number((string) ($_POST['sale_price'] ?? '0')),
        'stock_quantity' => ensure_positive_number((string) ($_POST['stock_quantity'] ?? '0')),
        'critical_level' => ensure_positive_number((string) ($_POST['critical_level'] ?? '0')),
        'unit_type' => trim((string) ($_POST['unit_type'] ?? 'adet')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'status' => trim((string) ($_POST['status'] ?? 'aktif')),
    ];

    if ($data['category_id'] <= 0 || $data['product_name'] === '' || $data['product_code'] === '') {
        $errors[] = 'Kategori, ürün adı ve ürün kodu zorunludur.';
    }
    if ($data['sale_price'] <= 0) {
        $errors[] = 'Satış fiyatı 0’dan büyük olmalıdır.';
    }
    if (!in_array($data['status'], ['aktif', 'pasif'], true)) {
        $errors[] = 'Durum bilgisi geçersiz.';
    }
    $duplicateCode = fetch_one('SELECT id FROM products WHERE product_code=:code AND id!=:id', ['code' => $data['product_code'], 'id' => $id]);
    if ($duplicateCode) {
        $errors[] = 'Ürün kodu benzersiz olmalıdır.';
    }

    if (!$errors) {
        if ($product) {
            execute_query(
                'UPDATE products
                 SET category_id=:category_id, product_name=:product_name, product_code=:product_code, barcode=:barcode,
                     purchase_price=:purchase_price, sale_price=:sale_price, stock_quantity=:stock_quantity, critical_level=:critical_level,
                     unit_type=:unit_type, description=:description, status=:status
                 WHERE id=:id',
                $data + ['id' => $id]
            );
            set_flash('success', 'Ürün güncellendi.');
        } else {
            execute_query(
                'INSERT INTO products
                 (category_id,product_name,product_code,barcode,purchase_price,sale_price,stock_quantity,critical_level,unit_type,description,status,created_at)
                 VALUES
                 (:category_id,:product_name,:product_code,:barcode,:purchase_price,:sale_price,:stock_quantity,:critical_level,:unit_type,:description,:status,NOW())',
                $data
            );
            set_flash('success', 'Yeni ürün eklendi.');
        }
        redirect('modules/products/index.php');
    }
}

require __DIR__ . '/../../includes/header.php';
?>
<section class="card">
    <h3><?= e($pageTitle) ?></h3>
    <?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
    <form method="post" class="form grid-2">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Kategori
            <select name="category_id" required>
                <option value="0">Seçiniz</option>
                <?php foreach ($categories as $category): ?>
                    <?php $selected = (int) ($_POST['category_id'] ?? $product['category_id'] ?? 0) === (int) $category['id']; ?>
                    <option value="<?= e((string) $category['id']) ?>" <?= $selected ? 'selected' : '' ?>><?= e((string) $category['category_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Ürün Adı<input type="text" name="product_name" required value="<?= e((string) ($_POST['product_name'] ?? $product['product_name'] ?? '')) ?>"></label>
        <label>Ürün Kodu<input type="text" name="product_code" required value="<?= e((string) ($_POST['product_code'] ?? $product['product_code'] ?? '')) ?>"></label>
        <label>Barkod<input type="text" name="barcode" value="<?= e((string) ($_POST['barcode'] ?? $product['barcode'] ?? '')) ?>"></label>
        <label>Alış Fiyatı<input type="text" name="purchase_price" value="<?= e((string) ($_POST['purchase_price'] ?? $product['purchase_price'] ?? '0')) ?>"></label>
        <label>Satış Fiyatı<input type="text" name="sale_price" required value="<?= e((string) ($_POST['sale_price'] ?? $product['sale_price'] ?? '0')) ?>"></label>
        <label>Stok Miktarı<input type="text" name="stock_quantity" value="<?= e((string) ($_POST['stock_quantity'] ?? $product['stock_quantity'] ?? '0')) ?>"></label>
        <label>Kritik Seviye<input type="text" name="critical_level" value="<?= e((string) ($_POST['critical_level'] ?? $product['critical_level'] ?? '0')) ?>"></label>
        <label>Birim Türü<input type="text" name="unit_type" value="<?= e((string) ($_POST['unit_type'] ?? $product['unit_type'] ?? 'adet')) ?>"></label>
        <label>Durum
            <select name="status">
                <?php $status = $_POST['status'] ?? $product['status'] ?? 'aktif'; ?>
                <option value="aktif" <?= $status === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                <option value="pasif" <?= $status === 'pasif' ? 'selected' : '' ?>>Pasif</option>
            </select>
        </label>
        <label class="full">Açıklama
            <textarea name="description"><?= e((string) ($_POST['description'] ?? $product['description'] ?? '')) ?></textarea>
        </label>
        <div class="full actions">
            <button class="btn primary" type="submit">Kaydet</button>
            <a class="btn ghost" href="<?= e(app_url('modules/products/index.php')) ?>">Geri</a>
        </div>
    </form>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

