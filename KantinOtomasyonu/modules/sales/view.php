<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_roles(['admin', 'kasiyer']);

$id = (int) ($_GET['id'] ?? 0);
$sale = fetch_one(
    'SELECT s.*, u.full_name
     FROM sales s
     INNER JOIN users u ON u.id = s.user_id
     WHERE s.id = :id',
    ['id' => $id]
);
if (!$sale) {
    set_flash('error', 'Satış bulunamadı.');
    redirect('modules/sales/index.php');
}

$items = fetch_all(
    'SELECT si.*, p.product_name, p.product_code
     FROM sale_items si
     INNER JOIN products p ON p.id = si.product_id
     WHERE si.sale_id = :id',
    ['id' => $id]
);

$activeMenu = 'sales';
$pageTitle = 'Satış Detayı';
require __DIR__ . '/../../includes/header.php';
?>
<section class="card">
    <h3>Satış Bilgisi</h3>
    <div class="grid two">
        <p><b>Satış No:</b> <?= e((string) $sale['sale_no']) ?></p>
        <p><b>Tarih:</b> <?= e(format_date((string) $sale['sale_date'])) ?></p>
        <p><b>Kullanıcı:</b> <?= e((string) $sale['full_name']) ?></p>
        <p><b>Durum:</b> <span class="<?= e(badge_status((string) $sale['status'])) ?>"><?= e(status_label((string) $sale['status'])) ?></span></p>
        <p><b>Toplam:</b> <?= e(format_money((float) $sale['total_amount'])) ?></p>
    </div>
</section>
<section class="card">
    <h3>Satış Kalemleri</h3>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Ürün</th><th>Kod</th><th>Adet</th><th>Birim</th><th>Tutar</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e((string) $item['product_name']) ?></td>
                    <td><?= e((string) $item['product_code']) ?></td>
                    <td><?= e((string) $item['quantity']) ?></td>
                    <td><?= e(format_money((float) $item['unit_price'])) ?></td>
                    <td><?= e(format_money((float) $item['line_total'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>

