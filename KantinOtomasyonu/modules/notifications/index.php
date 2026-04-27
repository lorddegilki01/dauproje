<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_roles(['admin', 'kasiyer']);

$activeMenu = 'notifications';
$pageTitle = 'Bildirimler';

$critical = fetch_all('SELECT product_name, stock_quantity, critical_level FROM products WHERE status="aktif" AND stock_quantity <= critical_level');
$outStock = fetch_all('SELECT product_name FROM products WHERE status="aktif" AND stock_quantity <= 0');
$topSold = fetch_all(
    'SELECT p.product_name, SUM(si.quantity) AS qty
     FROM sale_items si
     INNER JOIN sales s ON s.id = si.sale_id
     INNER JOIN products p ON p.id = si.product_id
     WHERE s.status = "tamamlandı" AND s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY si.product_id, p.product_name
     ORDER BY qty DESC LIMIT 5'
);
$notSold = fetch_all(
    'SELECT p.product_name
     FROM products p
     LEFT JOIN sale_items si ON si.product_id = p.id
     LEFT JOIN sales s ON s.id = si.sale_id AND s.status = "tamamlandı" AND s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     WHERE p.status="aktif"
     GROUP BY p.id, p.product_name
     HAVING COUNT(s.id) = 0
     ORDER BY p.product_name'
);

require __DIR__ . '/../../includes/header.php';
?>
<div class="grid two">
    <section class="card">
        <h3>Kritik Stok Uyarıları</h3>
        <ul class="list">
            <?php if (!$critical): ?><li>Kritik stok uyarısı bulunmuyor.</li><?php endif; ?>
            <?php foreach ($critical as $row): ?>
                <li><?= e((string) $row['product_name']) ?> - <?= e((string) $row['stock_quantity']) ?>/<?= e((string) $row['critical_level']) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <section class="card">
        <h3>Stoğu Biten Ürünler</h3>
        <ul class="list">
            <?php if (!$outStock): ?><li>Stoğu biten ürün bulunmuyor.</li><?php endif; ?>
            <?php foreach ($outStock as $row): ?>
                <li><?= e((string) $row['product_name']) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
<div class="grid two">
    <section class="card">
        <h3>Çok Satan Ürünler (7 Gün)</h3>
        <ul class="list">
            <?php if (!$topSold): ?><li>Bu hafta satış verisi yok.</li><?php endif; ?>
            <?php foreach ($topSold as $row): ?>
                <li><?= e((string) $row['product_name']) ?> (<?= e((string) $row['qty']) ?> adet)</li>
            <?php endforeach; ?>
        </ul>
    </section>
    <section class="card">
        <h3>Uzun Süredir Satılmayanlar (30 Gün)</h3>
        <ul class="list">
            <?php if (!$notSold): ?><li>Tüm ürünlerde son 30 gün satış var.</li><?php endif; ?>
            <?php foreach ($notSold as $row): ?>
                <li><?= e((string) $row['product_name']) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
