<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$activeMenu = 'reports';
$pageTitle = 'Raporlar';

$from = trim((string) ($_GET['from'] ?? date('Y-m-01')));
$to = trim((string) ($_GET['to'] ?? date('Y-m-d')));

$salesTotal = sum_value(
    'SELECT SUM(total_amount) FROM sales
     WHERE status IN ("tamamlandı","tamamlandÄ±") AND DATE(sale_date) BETWEEN :from AND :to',
    ['from' => $from, 'to' => $to]
);
$expenseTotal = sum_value(
    'SELECT SUM(amount) FROM expenses
     WHERE DATE(expense_date) BETWEEN :from AND :to',
    ['from' => $from, 'to' => $to]
);
$profit = $salesTotal - $expenseTotal;

$productReport = fetch_all(
    'SELECT p.product_name,
            SUM(si.quantity) AS sold_qty,
            SUM(si.line_total) AS sales_amount,
            SUM((si.unit_price - p.purchase_price) * si.quantity) AS gross_profit
     FROM sale_items si
     INNER JOIN sales s ON s.id = si.sale_id
     INNER JOIN products p ON p.id = si.product_id
     WHERE s.status IN ("tamamlandı","tamamlandÄ±") AND DATE(s.sale_date) BETWEEN :from AND :to
     GROUP BY si.product_id, p.product_name
     ORDER BY sold_qty DESC',
    ['from' => $from, 'to' => $to]
);

$criticalStock = fetch_all('SELECT product_name, stock_quantity, critical_level FROM products WHERE stock_quantity <= critical_level ORDER BY stock_quantity ASC');

$reportLabels = [];
$reportSales = [];
$reportProfit = [];
foreach (array_slice($productReport, 0, 8) as $row) {
    $reportLabels[] = $row['product_name'];
    $reportSales[] = (float) $row['sales_amount'];
    $reportProfit[] = (float) $row['gross_profit'];
}

require __DIR__ . '/../../includes/header.php';
?>
<section class="card">
    <div class="card-head">
        <h3>Gelir - Gider Raporu</h3>
        <form method="get" class="form inline">
            <label>Başlangıç<input type="date" name="from" value="<?= e($from) ?>"></label>
            <label>Bitiş<input type="date" name="to" value="<?= e($to) ?>"></label>
            <button class="btn ghost" type="submit">Uygula</button>
            <a class="btn ghost" target="_blank" href="<?= e(app_url('modules/reports/print.php?from=' . urlencode($from) . '&to=' . urlencode($to))) ?>">Yazdır</a>
        </form>
    </div>
    <div class="stats">
        <article class="card stat"><h3>Toplam Satış</h3><strong><?= e(format_money($salesTotal)) ?></strong></article>
        <article class="card stat"><h3>Toplam Gider</h3><strong><?= e(format_money($expenseTotal)) ?></strong></article>
        <article class="card stat"><h3>Net Sonuç</h3><strong><?= e(format_money($profit)) ?></strong></article>
    </div>
</section>

<section class="card">
    <h3>Ürün Bazlı Satış ve Kâr</h3>
    <div style="height:280px;margin-bottom:1rem;">
        <canvas
            id="reportChart"
            data-labels="<?= e(json_encode($reportLabels, JSON_UNESCAPED_UNICODE)) ?>"
            data-sales="<?= e(json_encode($reportSales, JSON_UNESCAPED_UNICODE)) ?>"
            data-profit="<?= e(json_encode($reportProfit, JSON_UNESCAPED_UNICODE)) ?>"
        ></canvas>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Ürün</th><th>Satılan Adet</th><th>Satış Tutarı</th><th>Brüt Kâr</th></tr></thead>
            <tbody>
            <?php if (!$productReport): ?><tr><td colspan="4">Veri bulunmuyor.</td></tr><?php endif; ?>
            <?php foreach ($productReport as $row): ?>
                <tr>
                    <td><?= e((string) $row['product_name']) ?></td>
                    <td><?= e((string) $row['sold_qty']) ?></td>
                    <td><?= e(format_money((float) $row['sales_amount'])) ?></td>
                    <td><?= e(format_money((float) $row['gross_profit'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <h3>Kritik Stok Raporu</h3>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Ürün</th><th>Stok</th><th>Kritik Seviye</th></tr></thead>
            <tbody>
            <?php if (!$criticalStock): ?><tr><td colspan="3">Kritik stok kaydı yok.</td></tr><?php endif; ?>
            <?php foreach ($criticalStock as $row): ?>
                <tr><td><?= e((string) $row['product_name']) ?></td><td><?= e((string) $row['stock_quantity']) ?></td><td><?= e((string) $row['critical_level']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
