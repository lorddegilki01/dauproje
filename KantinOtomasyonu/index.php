<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_login();

$activeMenu = 'dashboard';
$isAdmin = is_admin();
$userId = (int) current_user()['id'];
$pageTitle = $isAdmin ? 'Admin Dashboard' : 'Kasiyer Dashboard';

$totalProducts = count_value('SELECT COUNT(*) FROM products WHERE status = "aktif"');
$criticalStock = count_value('SELECT COUNT(*) FROM products WHERE status = "aktif" AND stock_quantity <= critical_level');
$todaySales = $isAdmin
    ? sum_value('SELECT SUM(total_amount) FROM sales WHERE DATE(sale_date) = CURDATE() AND status != "iptal"')
    : sum_value('SELECT SUM(total_amount) FROM sales WHERE DATE(sale_date) = CURDATE() AND status != "iptal" AND user_id = :user_id', ['user_id' => $userId]);
$monthlySales = $isAdmin
    ? sum_value('SELECT SUM(total_amount) FROM sales WHERE YEAR(sale_date)=YEAR(CURDATE()) AND MONTH(sale_date)=MONTH(CURDATE()) AND status != "iptal"')
    : sum_value('SELECT SUM(total_amount) FROM sales WHERE YEAR(sale_date)=YEAR(CURDATE()) AND MONTH(sale_date)=MONTH(CURDATE()) AND status != "iptal" AND user_id = :user_id', ['user_id' => $userId]);

$topProducts = fetch_all(
    'SELECT p.product_name, SUM(si.quantity) AS qty
     FROM sale_items si
     INNER JOIN products p ON p.id = si.product_id
     INNER JOIN sales s ON s.id = si.sale_id
     WHERE s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND s.status != "iptal"
     GROUP BY si.product_id, p.product_name
     ORDER BY qty DESC
     LIMIT 5'
);

$recentSales = $isAdmin
    ? fetch_all(
        'SELECT s.id, s.sale_no, s.total_amount, s.sale_date, s.status, u.full_name
         FROM sales s
         INNER JOIN users u ON u.id = s.user_id
         ORDER BY s.sale_date DESC
         LIMIT 8'
    )
    : fetch_all(
        'SELECT s.id, s.sale_no, s.total_amount, s.sale_date, s.status, u.full_name
         FROM sales s
         INNER JOIN users u ON u.id = s.user_id
         WHERE s.user_id = :user_id
         ORDER BY s.sale_date DESC
         LIMIT 8',
        ['user_id' => $userId]
    );

$alerts = fetch_all(
    'SELECT product_name, stock_quantity, critical_level
     FROM products
     WHERE status="aktif" AND stock_quantity <= critical_level
     ORDER BY stock_quantity ASC
     LIMIT 8'
);

$dailyRows = $isAdmin
    ? fetch_all(
        'SELECT DATE(sale_date) AS d, SUM(total_amount) AS total
         FROM sales
         WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND status != "iptal"
         GROUP BY DATE(sale_date)
         ORDER BY d'
    )
    : fetch_all(
        'SELECT DATE(sale_date) AS d, SUM(total_amount) AS total
         FROM sales
         WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND status != "iptal" AND user_id = :user_id
         GROUP BY DATE(sale_date)
         ORDER BY d',
        ['user_id' => $userId]
    );

$dailyMap = [];
foreach ($dailyRows as $row) {
    $dailyMap[(string) $row['d']] = (float) $row['total'];
}
$trendLabels = [];
$trendValues = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} day"));
    $trendLabels[] = date('d M', strtotime($date));
    $trendValues[] = $dailyMap[$date] ?? 0;
}
$trendHasData = array_sum(array_map('floatval', $trendValues)) > 0;

$stockDistRows = fetch_all(
    'SELECT
        SUM(CASE WHEN stock_quantity <= 0 THEN 1 ELSE 0 END) AS bitti,
        SUM(CASE WHEN stock_quantity > 0 AND stock_quantity <= critical_level THEN 1 ELSE 0 END) AS kritik,
        SUM(CASE WHEN stock_quantity > critical_level THEN 1 ELSE 0 END) AS normal
     FROM products
     WHERE status = "aktif"'
);
$stockDist = $stockDistRows[0] ?? ['bitti' => 0, 'kritik' => 0, 'normal' => 0];
$stockDistValues = [(int) $stockDist['normal'], (int) $stockDist['kritik'], (int) $stockDist['bitti']];
$stockHasData = array_sum($stockDistValues) > 0;

require __DIR__ . '/includes/header.php';
?>
<div class="stats">
    <article class="card stat">
        <div class="kpi-top">
            <div class="kpi-icon">&#128230;</div>
            <div class="kpi-meta">
                <h3><?= $isAdmin ? 'Toplam Ürün' : 'Aktif Ürün' ?></h3>
                <small>envanter</small>
            </div>
        </div>
        <strong><?= e((string) $totalProducts) ?></strong>
        <canvas class="sparkline" data-points="4,6,7,9,8,10,11"></canvas>
    </article>

    <article class="card stat">
        <div class="kpi-top">
            <div class="kpi-icon">&#9888;&#65039;</div>
            <div class="kpi-meta">
                <h3>Kritik Stok</h3>
                <small>tedarik gerekli</small>
            </div>
        </div>
        <strong><?= e((string) $criticalStock) ?></strong>
        <canvas class="sparkline" data-points="9,8,7,6,6,5,4"></canvas>
    </article>

    <article class="card stat">
        <div class="kpi-top">
            <div class="kpi-icon">&#128176;</div>
            <div class="kpi-meta">
                <h3>Günlük Ciro</h3>
                <small><?= $isAdmin ? 'bugün' : 'kendi satışınız' ?></small>
            </div>
        </div>
        <strong><?= e(format_money($todaySales)) ?></strong>
        <canvas class="sparkline" data-points="5,8,7,10,9,12,13"></canvas>
    </article>

    <article class="card stat">
        <div class="kpi-top">
            <div class="kpi-icon">&#128200;</div>
            <div class="kpi-meta">
                <h3>Aylık Ciro</h3>
                <small><?= $isAdmin ? 'kümülatif' : 'kendi toplamınız' ?></small>
            </div>
        </div>
        <strong><?= e(format_money($monthlySales)) ?></strong>
        <canvas class="sparkline" data-points="2,3,5,6,9,11,14"></canvas>
    </article>
</div>

<div class="dashboard-grid">
    <section class="card chart-box">
        <div class="card-head">
            <h3>Son 7 Gün Satış Trendi</h3>
        </div>
        <div class="chart-area">
            <?php if (!$trendHasData): ?>
                <div class="chart-empty">Son 7 günde satış verisi bulunmuyor.</div>
            <?php endif; ?>
            <canvas
                id="salesTrendChart"
                class="chart-canvas"
                data-empty="<?= $trendHasData ? '0' : '1' ?>"
                data-labels="<?= e(json_encode($trendLabels, JSON_UNESCAPED_UNICODE)) ?>"
                data-values="<?= e(json_encode($trendValues, JSON_UNESCAPED_UNICODE)) ?>"
            ></canvas>
        </div>
    </section>

    <section class="card chart-box">
        <div class="card-head">
            <h3>Stok Durum Dağılımı</h3>
        </div>
        <div class="chart-area">
            <?php if (!$stockHasData): ?>
                <div class="chart-empty">Stok dağılımı için aktif ürün verisi bulunmuyor.</div>
            <?php endif; ?>
            <canvas
                id="stockDistChart"
                class="chart-canvas"
                data-empty="<?= $stockHasData ? '0' : '1' ?>"
                data-labels='["Normal","Kritik","Biten"]'
                data-values="<?= e(json_encode($stockDistValues, JSON_UNESCAPED_UNICODE)) ?>"
            ></canvas>
        </div>
    </section>
</div>

<div class="grid two">
    <section class="card">
        <h3><?= $isAdmin ? 'En Çok Satan Ürünler (30 Gün)' : 'Son Satışlarım (Özet)' ?></h3>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th><?= $isAdmin ? 'Ürün' : 'Satış No' ?></th><th><?= $isAdmin ? 'Satış Adedi' : 'Tutar' ?></th></tr></thead>
                <tbody>
                <?php if ($isAdmin && !$topProducts): ?><tr><td colspan="2">Kayıt yok</td></tr><?php endif; ?>
                <?php if (!$isAdmin && !$recentSales): ?><tr><td colspan="2">Kayıt yok</td></tr><?php endif; ?>
                <?php if ($isAdmin): ?>
                    <?php foreach ($topProducts as $row): ?>
                        <tr><td><?= e((string) $row['product_name']) ?></td><td><?= e((string) $row['qty']) ?></td></tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php foreach (array_slice($recentSales, 0, 5) as $sale): ?>
                        <tr><td><?= e((string) $sale['sale_no']) ?></td><td><?= e(format_money((float) $sale['total_amount'])) ?></td></tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h3>Stok Uyarıları</h3>
        <ul class="list">
            <?php if (!$alerts): ?><li>Kritik stok uyarısı bulunmuyor.</li><?php endif; ?>
            <?php foreach ($alerts as $alert): ?>
                <li>
                    <b><?= e((string) $alert['product_name']) ?></b>
                    <span class="badge warning">Stok: <?= e((string) $alert['stock_quantity']) ?> / Kritik: <?= e((string) $alert['critical_level']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>

<section class="card">
    <h3><?= $isAdmin ? 'Son Satışlar' : 'Kendi Satış Geçmişim' ?></h3>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>No</th><th>Tarih</th><th>Kullanıcı</th><th>Tutar</th><th>Durum</th><th>İşlem</th></tr></thead>
            <tbody>
            <?php if (!$recentSales): ?><tr><td colspan="6">Satış kaydı bulunmuyor.</td></tr><?php endif; ?>
            <?php foreach ($recentSales as $sale): ?>
                <tr>
                    <td><?= e((string) $sale['sale_no']) ?></td>
                    <td><?= e(format_date((string) $sale['sale_date'])) ?></td>
                    <td><?= e((string) $sale['full_name']) ?></td>
                    <td><?= e(format_money((float) $sale['total_amount'])) ?></td>
                    <td><span class="<?= e(badge_status((string) $sale['status'])) ?>"><?= e(status_label((string) $sale['status'])) ?></span></td>
                    <td><a href="<?= e(app_url('modules/sales/view.php?id=' . (int) $sale['id'])) ?>">Detay</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
