<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$activeMenu = '';
$pageTitle = 'Raporlar';

$from = (string) ($_GET['from'] ?? date('Y-m-01'));
$to = (string) ($_GET['to'] ?? date('Y-m-d'));

$summary = [
    'new_books' => count_value('SELECT COUNT(*) FROM books WHERE DATE(created_at) BETWEEN :from AND :to', ['from' => $from, 'to' => $to]),
    'new_requests' => count_value('SELECT COUNT(*) FROM book_requests WHERE DATE(created_at) BETWEEN :from AND :to', ['from' => $from, 'to' => $to]),
    'delivered' => count_value('SELECT COUNT(*) FROM matches WHERE delivery_status = "teslim edildi" AND DATE(updated_at) BETWEEN :from AND :to', ['from' => $from, 'to' => $to]),
];

$booksByCategory = fetch_all(
    'SELECT c.category_name, COUNT(b.id) total
     FROM categories c
     LEFT JOIN books b ON b.category_id = c.id AND DATE(b.created_at) BETWEEN :from AND :to
     GROUP BY c.id
     ORDER BY total DESC',
    ['from' => $from, 'to' => $to]
);

$requestStatus = fetch_all(
    'SELECT request_status, COUNT(*) total
     FROM book_requests
     WHERE DATE(created_at) BETWEEN :from AND :to
     GROUP BY request_status',
    ['from' => $from, 'to' => $to]
);

require __DIR__ . '/../includes/header.php';
?>
<section class="card">
    <h2>Rapor Filtreleri</h2>
    <form method="get" class="form inline-form">
        <label>Başlangıç <input type="date" name="from" value="<?= e($from) ?>"></label>
        <label>Bitiş <input type="date" name="to" value="<?= e($to) ?>"></label>
        <button class="btn primary" type="submit">Uygula</button>
        <button class="btn ghost" type="button" onclick="window.print()">Yazdır</button>
    </form>
</section>

<section class="stats-grid">
    <article class="card stat"><h3>Yeni Kitap</h3><strong><?= e((string) $summary['new_books']) ?></strong></article>
    <article class="card stat"><h3>Yeni Talep</h3><strong><?= e((string) $summary['new_requests']) ?></strong></article>
    <article class="card stat"><h3>Teslim Edilen</h3><strong><?= e((string) $summary['delivered']) ?></strong></article>
</section>

<section class="dashboard-grid">
    <article class="card">
        <h2>Kategori Bazlı Kitap</h2>
        <ul class="list">
            <?php foreach ($booksByCategory as $row): ?>
                <li><?= e((string) $row['category_name']) ?> <strong><?= e((string) $row['total']) ?></strong></li>
            <?php endforeach; ?>
        </ul>
    </article>
    <article class="card">
        <h2>Talep Durum Dağılımı</h2>
        <ul class="list">
            <?php foreach ($requestStatus as $row): ?>
                <li><?= e(request_status_label((string) $row['request_status'])) ?> <strong><?= e((string) $row['total']) ?></strong></li>
            <?php endforeach; ?>
        </ul>
    </article>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

