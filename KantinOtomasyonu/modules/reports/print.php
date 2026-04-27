<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$from = trim((string) ($_GET['from'] ?? date('Y-m-01')));
$to = trim((string) ($_GET['to'] ?? date('Y-m-d')));

$sales = fetch_all(
    'SELECT sale_no, sale_date, total_amount
     FROM sales
     WHERE status IN ("tamamlandı","tamamlandÄ±") AND DATE(sale_date) BETWEEN :from AND :to
     ORDER BY sale_date DESC',
    ['from' => $from, 'to' => $to]
);
$salesTotal = sum_value('SELECT SUM(total_amount) FROM sales WHERE status IN ("tamamlandı","tamamlandÄ±") AND DATE(sale_date) BETWEEN :from AND :to', ['from' => $from, 'to' => $to]);
$expenseTotal = sum_value('SELECT SUM(amount) FROM expenses WHERE DATE(expense_date) BETWEEN :from AND :to', ['from' => $from, 'to' => $to]);
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Rapor Çıktısı</title>
    <style>
        body{font-family:Arial,sans-serif;padding:20px}
        table{width:100%;border-collapse:collapse}
        th,td{border:1px solid #ccc;padding:8px}
    </style>
</head>
<body onload="window.print()">
<h2>Kantin Satış Raporu</h2>
<p>Tarih Aralığı: <?= e($from) ?> - <?= e($to) ?></p>
<p>Toplam Satış: <?= e(format_money($salesTotal)) ?> | Toplam Gider: <?= e(format_money($expenseTotal)) ?> | Net: <?= e(format_money($salesTotal - $expenseTotal)) ?></p>
<table>
    <thead><tr><th>Satış No</th><th>Tarih</th><th>Tutar</th></tr></thead>
    <tbody>
    <?php foreach ($sales as $sale): ?>
        <tr><td><?= e((string) $sale['sale_no']) ?></td><td><?= e(format_date((string) $sale['sale_date'])) ?></td><td><?= e(format_money((float) $sale['total_amount'])) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>
