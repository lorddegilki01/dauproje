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
     WHERE s.status IN ("tamamlandı","tamamlandÄ±") AND s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY si.product_id, p.product_name
     ORDER BY qty DESC LIMIT 5'
);
$notSold = fetch_all(
    'SELECT p.product_name
     FROM products p
     LEFT JOIN sale_items si ON si.product_id = p.id
     LEFT JOIN sales s ON s.id = si.sale_id AND s.status IN ("tamamlandı","tamamlandÄ±") AND s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     WHERE p.status="aktif"
     GROUP BY p.id, p.product_name
     HAVING COUNT(s.id) = 0
     ORDER BY p.product_name'
);

$backupEvents = [];
if (is_admin()) {
    require_once __DIR__ . '/../../includes/BackupManager.php';
    $backupManager = new BackupManager(db(), dirname(__DIR__, 2));
    $backupManager->ensureSchema();
    $backupEvents = $backupManager->getRecentLogs(15);
}

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
<?php if (is_admin()): ?>
<section class="card">
    <h3>Yedekleme Bildirimleri</h3>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Tarih</th><th>İşlem</th><th>Durum</th><th>Mesaj</th></tr></thead>
            <tbody>
            <?php if (!$backupEvents): ?><tr><td colspan="4">Yedekleme bildirimi bulunmuyor.</td></tr><?php endif; ?>
            <?php foreach ($backupEvents as $event): ?>
                <tr>
                    <td><?= e(format_date((string) $event['created_at'])) ?></td>
                    <td><?= e((string) $event['action']) ?></td>
                    <td><span class="<?= e(badge_status((string) $event['status'])) ?>"><?= e((string) $event['status']) ?></span></td>
                    <td><?= e((string) $event['message']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
