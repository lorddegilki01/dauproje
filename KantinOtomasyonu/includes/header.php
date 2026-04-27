<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
require_login();

$user = current_user();
$pageTitle = $pageTitle ?? 'Dashboard';

$criticalCount = count_value('SELECT COUNT(*) FROM products WHERE status = "aktif" AND stock_quantity <= critical_level');
$outStockCount = count_value('SELECT COUNT(*) FROM products WHERE status = "aktif" AND stock_quantity <= 0');
$todaySalesCount = count_value('SELECT COUNT(*) FROM sales WHERE DATE(sale_date) = CURDATE() AND status != "iptal"');

$topAlerts = [];
if ($criticalCount > 0) {
    $topAlerts[] = [
        'title' => 'Kritik stok uyarısı',
        'text' => $criticalCount . ' ürün kritik seviyede.',
        'url' => app_url('modules/stock/index.php'),
    ];
}
if ($outStockCount > 0) {
    $topAlerts[] = [
        'title' => 'Stok bitti',
        'text' => $outStockCount . ' ürünün stoğu tükendi.',
        'url' => app_url('modules/stock/index.php'),
    ];
}
$topAlerts[] = [
    'title' => 'Günlük satış',
    'text' => 'Bugün tamamlanan satış adedi: ' . $todaySalesCount,
    'url' => app_url('modules/sales/index.php'),
];
$notificationCount = $criticalCount + $outStockCount;
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('kantin_theme');
                var fallback = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                document.documentElement.dataset.theme = stored || fallback;
            } catch (e) {
                document.documentElement.dataset.theme = 'dark';
            }
        })();
    </script>
    <link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>">
</head>
<body class="app-shell">
<div class="ambient-bg" aria-hidden="true">
    <span class="orb orb-a"></span>
    <span class="orb orb-b"></span>
    <span class="orb orb-c"></span>
    <span class="grid-glow"></span>
</div>

<div class="layout">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <main class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button class="menu-button" id="openSidebarBtn" type="button" aria-label="Menüyü aç">&#9776;</button>
                <div>
                    <h1><?= e($pageTitle) ?></h1>
                    <p>Kantin satış, stok ve gider süreçlerini tek panelde yönetin.</p>
                </div>
            </div>

            <div class="topbar-right">
                <label class="quick-search">
                    <span>&#128269;</span>
                    <input id="globalQuickSearch" type="search" placeholder="Sayfada hızlı ara...">
                </label>

                <button class="icon-btn theme-btn" id="themeToggleBtn" type="button" aria-label="Tema değiştir">
                    <span class="theme-icon">🌙</span>
                    <span class="theme-label">Koyu</span>
                </button>

                <div class="notif-wrap">
                    <button class="icon-btn" id="notificationBtn" type="button" aria-label="Bildirimler">
                        &#128276;
                        <?php if ($notificationCount > 0): ?>
                            <span class="notif-count"><?= e((string) $notificationCount) ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="notif-panel" id="notificationPanel">
                        <div class="notif-head">
                            <strong>Bildirim Merkezi</strong>
                            <a href="<?= e(app_url('modules/notifications/index.php')) ?>">Tümünü Gör</a>
                        </div>
                        <ul>
                            <?php foreach ($topAlerts as $alert): ?>
                                <li>
                                    <a href="<?= e($alert['url']) ?>">
                                        <span class="notif-title"><?= e($alert['title']) ?></span>
                                        <small><?= e($alert['text']) ?></small>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div class="user-chip">
                    <span class="avatar"><?= e(mb_substr((string) $user['full_name'], 0, 1, 'UTF-8')) ?></span>
                    <div>
                        <strong><?= e((string) $user['full_name']) ?></strong>
                        <small><?= e(mb_strtoupper((string) $user['role'], 'UTF-8')) ?></small>
                    </div>
                </div>

                <form method="post" action="<?= e(app_url('auth/logout.php')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <button class="btn ghost" type="submit">Çıkış</button>
                </form>
            </div>
        </header>

        <section class="content">
            <?php $flash = get_flash(); ?>
            <?php if ($flash): ?>
                <div class="alert <?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div>
            <?php endif; ?>
