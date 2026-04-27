<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_roles(['admin', 'kasiyer']);

$activeMenu = 'stock';
$pageTitle = is_admin() ? 'Stok Takibi' : 'Stok Görünümü';
$errors = [];

if (is_admin() && is_post()) {
    verify_csrf();
    $productId = (int) ($_POST['product_id'] ?? 0);
    $movementType = (string) ($_POST['movement_type'] ?? '');
    $quantity = ensure_positive_number((string) ($_POST['quantity'] ?? '0'));
    $note = trim((string) ($_POST['note'] ?? ''));

    if ($productId <= 0 || !in_array($movementType, ['in', 'out'], true) || $quantity <= 0) {
        $errors[] = 'Ürün, hareket tipi ve miktar bilgisi zorunludur.';
    }

    if (!$errors) {
        try {
            update_product_stock($productId, $quantity, $movementType, $note, (int) current_user()['id']);
            set_flash('success', 'Stok hareketi kaydedildi.');
            redirect('modules/stock/index.php');
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$products = fetch_all('SELECT id, product_name, stock_quantity, unit_type FROM products WHERE status="aktif" ORDER BY product_name');
$criticalProducts = fetch_all('SELECT product_name, stock_quantity, critical_level FROM products WHERE status="aktif" AND stock_quantity <= critical_level ORDER BY stock_quantity ASC');
$outProducts = fetch_all('SELECT product_name FROM products WHERE status="aktif" AND stock_quantity <= 0 ORDER BY product_name');
$movements = fetch_all(
    'SELECT sm.*, p.product_name, u.full_name
     FROM stock_movements sm
     INNER JOIN products p ON p.id = sm.product_id
     LEFT JOIN users u ON u.id = sm.user_id
     ORDER BY sm.created_at DESC
     LIMIT 30'
);

require __DIR__ . '/../../includes/header.php';
?>
<?php if (is_admin()): ?>
<section class="card">
    <h3>Stok Hareketi Ekle</h3>
    <?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
    <form method="post" class="form grid-2">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Ürün
            <select name="product_id" required>
                <option value="0">Seçiniz</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= e((string) $product['id']) ?>"><?= e((string) $product['product_name']) ?> (<?= e((string) $product['stock_quantity']) ?> <?= e((string) $product['unit_type']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Hareket Tipi
            <select name="movement_type" required>
                <option value="in">Stok Giriş</option>
                <option value="out">Stok Çıkış</option>
            </select>
        </label>
        <label>Miktar<input type="text" name="quantity" required></label>
        <label>Not<input type="text" name="note"></label>
        <div class="full actions"><button class="btn primary" type="submit">Kaydet</button></div>
    </form>
</section>
<?php endif; ?>

<div class="grid two">
    <section class="card">
        <h3>Kritik Stoktaki Ürünler</h3>
        <ul class="list">
            <?php if (!$criticalProducts): ?><li>Kritik stok ürünü yok.</li><?php endif; ?>
            <?php foreach ($criticalProducts as $row): ?><li><?= e((string) $row['product_name']) ?> (<?= e((string) $row['stock_quantity']) ?>/<?= e((string) $row['critical_level']) ?>)</li><?php endforeach; ?>
        </ul>
    </section>
    <section class="card">
        <h3>Stoğu Biten Ürünler</h3>
        <ul class="list">
            <?php if (!$outProducts): ?><li>Stoğu biten ürün yok.</li><?php endif; ?>
            <?php foreach ($outProducts as $row): ?><li><?= e((string) $row['product_name']) ?></li><?php endforeach; ?>
        </ul>
    </section>
</div>

<section class="card">
    <h3>Son Stok Hareketleri</h3>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Tarih</th><th>Ürün</th><th>Tür</th><th>Miktar</th><th>Kullanıcı</th><th>Not</th></tr></thead>
            <tbody>
            <?php foreach ($movements as $row): ?>
                <tr>
                    <td><?= e(format_date((string) $row['created_at'])) ?></td>
                    <td><?= e((string) $row['product_name']) ?></td>
                    <td><?= e($row['movement_type'] === 'in' ? 'Giriş' : 'Çıkış') ?></td>
                    <td><?= e((string) $row['quantity']) ?></td>
                    <td><?= e((string) ($row['full_name'] ?? '-')) ?></td>
                    <td><?= e((string) $row['note']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
