<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_roles(['admin', 'kasiyer']);

$isAdmin = is_admin();
$onlyMine = (string) ($_GET['mine'] ?? '') === '1';
$isCashierDailyPage = !$isAdmin && $onlyMine;

$activeMenu = $isCashierDailyPage ? 'my-sales' : 'sales';
$pageTitle = $isCashierDailyPage ? 'Günlük Satışlarım' : 'Satış Modülü';

$products = fetch_all(
    'SELECT id, product_name, product_code, sale_price, stock_quantity
     FROM products
     WHERE status = "aktif" AND stock_quantity > 0
     ORDER BY product_name'
);

$from = trim((string) ($_GET['from'] ?? ''));
$to = trim((string) ($_GET['to'] ?? ''));
if ($isCashierDailyPage) {
    $today = date('Y-m-d');
    $from = $from !== '' ? $from : $today;
    $to = $to !== '' ? $to : $today;
}

$where = ['1=1'];
$params = [];
if ($from !== '') {
    $where[] = 'DATE(s.sale_date) >= :from';
    $params['from'] = $from;
}
if ($to !== '') {
    $where[] = 'DATE(s.sale_date) <= :to';
    $params['to'] = $to;
}
if (!$isAdmin || $onlyMine) {
    $where[] = 's.user_id = :user_id';
    $params['user_id'] = (int) current_user()['id'];
}

$sales = fetch_all(
    'SELECT s.*, u.full_name
     FROM sales s
     INNER JOIN users u ON u.id = s.user_id
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY s.sale_date DESC
     LIMIT 100',
    $params
);

$salesCount = count($sales);
$salesTotal = 0.0;
foreach ($sales as $saleRow) {
    if ((string) $saleRow['status'] !== 'iptal') {
        $salesTotal += (float) $saleRow['total_amount'];
    }
}
$avgTicket = $salesCount > 0 ? $salesTotal / $salesCount : 0.0;

require __DIR__ . '/../../includes/header.php';
?>
<?php if (!$isCashierDailyPage): ?>
<section class="card">
    <h3>Hızlı Satış Ekranı</h3>
    <div class="pos-shell">
        <form id="saleForm" method="post" action="<?= e(app_url('modules/sales/process.php')) ?>" class="form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="cart_json" id="cartJson">

            <div class="pos-product-picker">
                <select id="productPicker">
                    <option value="">Ürün seçiniz</option>
                    <?php foreach ($products as $product): ?>
                        <option
                            value="<?= e((string) $product['id']) ?>"
                            data-name="<?= e((string) $product['product_name']) ?>"
                            data-price="<?= e((string) $product['sale_price']) ?>"
                            data-stock="<?= e((string) $product['stock_quantity']) ?>"
                        >
                            <?= e((string) $product['product_name']) ?> | <?= e(format_money((float) $product['sale_price'])) ?> | Stok: <?= e((string) $product['stock_quantity']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="number" id="quantityPicker" min="1" value="1">
                <button class="btn primary" type="button" id="addToCartBtn">Sepete Ekle</button>
            </div>

            <div class="table-wrap">
                <table class="table" id="cartTable">
                    <thead><tr><th>Ürün</th><th>Adet</th><th>Birim</th><th>Tutar</th><th></th></tr></thead>
                    <tbody><tr><td colspan="5">Sepet boş.</td></tr></tbody>
                </table>
            </div>

            <div class="sale-footer">
                <div class="total-box">
                    <span>Genel Toplam</span><br>
                    <strong id="cartTotal">0,00 ₺</strong>
                </div>
                <button class="btn primary" type="submit">Satışı Tamamla</button>
            </div>
        </form>

        <aside class="pos-summary">
            <h4>POS Bilgilendirme</h4>
            <ul class="list">
                <li>Seçilen ürün stoktan otomatik düşülür.</li>
                <li>Satış tamamlandığında fiş detayına ulaşabilirsiniz.</li>
                <li>Yönetici kullanıcıları satış iptal edebilir.</li>
            </ul>
        </aside>
    </div>
</section>
<?php endif; ?>

<?php if ($isCashierDailyPage): ?>
<section class="stats">
    <article class="card stat">
        <div class="kpi-top">
            <div class="kpi-icon">🧾</div>
            <div class="kpi-meta"><h3>Bugünkü İşlem</h3><small>adet</small></div>
        </div>
        <strong><?= e((string) $salesCount) ?></strong>
        <canvas class="sparkline" data-points="2,3,4,3,5,4,6"></canvas>
    </article>
    <article class="card stat">
        <div class="kpi-top">
            <div class="kpi-icon">💰</div>
            <div class="kpi-meta"><h3>Bugünkü Ciro</h3><small>kasiyer toplamı</small></div>
        </div>
        <strong><?= e(format_money($salesTotal)) ?></strong>
        <canvas class="sparkline" data-points="3,4,5,6,5,7,8"></canvas>
    </article>
    <article class="card stat">
        <div class="kpi-top">
            <div class="kpi-icon">📊</div>
            <div class="kpi-meta"><h3>Ortalama Fiş</h3><small>bugün</small></div>
        </div>
        <strong><?= e(format_money($avgTicket)) ?></strong>
        <canvas class="sparkline" data-points="4,3,5,4,6,5,7"></canvas>
    </article>
</section>
<?php endif; ?>

<section class="card">
    <div class="card-head">
        <h3><?= $isCashierDailyPage ? 'Bugünkü Satış Geçmişim' : ($onlyMine ? 'Satış Geçmişim' : 'Satış Geçmişi') ?></h3>
        <form method="get" class="form inline">
            <?php if ($onlyMine): ?><input type="hidden" name="mine" value="1"><?php endif; ?>
            <label>Başlangıç<input type="date" name="from" value="<?= e($from) ?>"></label>
            <label>Bitiş<input type="date" name="to" value="<?= e($to) ?>"></label>
            <button class="btn ghost" type="submit">Filtrele</button>
        </form>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>No</th><th>Tarih</th><th>Kullanıcı</th><th>Tutar</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
            <?php if (!$sales): ?><tr><td colspan="6">Kayıt bulunamadı.</td></tr><?php endif; ?>
            <?php foreach ($sales as $sale): ?>
                <tr>
                    <td><?= e((string) $sale['sale_no']) ?></td>
                    <td><?= e(format_date((string) $sale['sale_date'])) ?></td>
                    <td><?= e((string) $sale['full_name']) ?></td>
                    <td><?= e(format_money((float) $sale['total_amount'])) ?></td>
                    <td><span class="<?= e(badge_status((string) $sale['status'])) ?>"><?= e(status_label((string) $sale['status'])) ?></span></td>
                    <td class="actions">
                        <a href="<?= e(app_url('modules/sales/view.php?id=' . (int) $sale['id'])) ?>">Detay</a>
                        <?php if ($isAdmin && (string) $sale['status'] === 'tamamlandı'): ?>
                            <form method="post" action="<?= e(app_url('modules/sales/cancel.php')) ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= e((string) $sale['id']) ?>">
                                <button class="btn-link danger" type="submit" data-confirm="Satış iptal edilsin mi?">İptal Et</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
