<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$product = fetch_one(
    'SELECT p.*, c.category_name
     FROM products p LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.id = :id',
    ['id' => $id]
);
if (!$product) {
    set_flash('error', 'Ürün bulunamadı.');
    redirect('modules/products/index.php');
}

$movements = fetch_all(
    'SELECT sm.*, u.full_name
     FROM stock_movements sm
     LEFT JOIN users u ON u.id = sm.user_id
     WHERE sm.product_id = :id
     ORDER BY sm.created_at DESC
     LIMIT 20',
    ['id' => $id]
);

$activeMenu = 'products';
$pageTitle = 'Ürün Detayı';
require __DIR__ . '/../../includes/header.php';
?>
<section class="card">
    <h3><?= e((string) $product['product_name']) ?></h3>
    <div class="grid two">
        <div>
            <p><b>Kategori:</b> <?= e((string) ($product['category_name'] ?? '-')) ?></p>
            <p><b>Ürün Kodu:</b> <?= e((string) $product['product_code']) ?></p>
            <p><b>Barkod:</b> <?= e((string) $product['barcode']) ?></p>
            <p><b>Durum:</b> <span class="<?= e(badge_status((string) $product['status'])) ?>"><?= e(status_label((string) $product['status'])) ?></span></p>
        </div>
        <div>
            <p><b>Alış Fiyatı:</b> <?= e(format_money((float) $product['purchase_price'])) ?></p>
            <p><b>Satış Fiyatı:</b> <?= e(format_money((float) $product['sale_price'])) ?></p>
            <p><b>Stok:</b> <?= e((string) $product['stock_quantity']) ?> <?= e((string) $product['unit_type']) ?></p>
            <p><b>Kritik Seviye:</b> <?= e((string) $product['critical_level']) ?></p>
        </div>
    </div>
    <p><b>Açıklama:</b> <?= e((string) $product['description']) ?></p>
</section>

<section class="card">
    <h3>Son Stok Hareketleri</h3>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Tarih</th><th>Tür</th><th>Miktar</th><th>Kullanıcı</th><th>Not</th></tr></thead>
            <tbody>
            <?php foreach ($movements as $move): ?>
                <tr>
                    <td><?= e(format_date((string) $move['created_at'])) ?></td>
                    <td><?= e($move['movement_type'] === 'in' ? 'Stok Giriş' : 'Stok Çıkış') ?></td>
                    <td><?= e((string) $move['quantity']) ?></td>
                    <td><?= e((string) ($move['full_name'] ?? '-')) ?></td>
                    <td><?= e((string) $move['note']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

