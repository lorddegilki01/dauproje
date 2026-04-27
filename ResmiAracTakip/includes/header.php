<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_login();

$pageTitle = $pageTitle ?? APP_NAME;
$flash = get_flash();
$user = current_user();
$searchQuery = trim((string) ($_GET['q'] ?? ''));
$notificationCount = 0;

if ($user && notifications_schema_ready()) {
    if (is_admin()) {
        sync_admin_notifications((int) $user['id']);
    } elseif (is_personnel()) {
        sync_personnel_notifications((int) $user['id']);
    }
    $notificationCount = notification_unread_count((int) $user['id']);
}

$fullName = (string) ($user['full_name'] ?? '');
$initials = '';
if ($fullName !== '') {
    $parts = preg_split('/\s+/u', $fullName) ?: [];
    $first = $parts[0] ?? '';
    $last = $parts[count($parts) - 1] ?? '';
    $initials = mb_substr($first, 0, 1, 'UTF-8') . mb_substr($last, 0, 1, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('rat_theme');
                var theme = (saved === 'light' || saved === 'dark') ? saved : 'dark';
                document.documentElement.setAttribute('data-theme', theme);
            } catch (err) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
    <link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>">
</head>
<body
    class="<?= e(is_admin() ? 'role-admin' : 'role-personnel') ?>"
    data-csrf-token="<?= e(csrf_token()) ?>"
    data-base-url="<?= e(rtrim(app_url(''), '/')) ?>"
>
<div class="app-shell">
    <?php
    $sidebarFile = __DIR__ . DIRECTORY_SEPARATOR . 'sidebar.php';
    if (!is_file($sidebarFile)) {
        clearstatcache(true, $sidebarFile);
    }

    if (!is_file($sidebarFile)) {
        $candidate = null;
        foreach (scandir(__DIR__) ?: [] as $entry) {
            if (mb_strtolower((string) $entry, 'UTF-8') === 'sidebar.php') {
                $candidate = __DIR__ . DIRECTORY_SEPARATOR . $entry;
                break;
            }
        }
        if ($candidate && is_file($candidate)) {
            $sidebarFile = $candidate;
        }
    }

    if (is_file($sidebarFile)) {
        require $sidebarFile;
    } else {
        echo '<aside class="sidebar" data-sidebar><div class="sidebar-brand"><div class="brand-icon">RT</div><div><strong>Resmi Araç Takip</strong><small>Menü yüklenemedi</small></div></div></aside>';
    }
    ?>
    <div class="app-main">
        <div class="app-bg" aria-hidden="true">
            <span class="blob blob-1"></span>
            <span class="blob blob-2"></span>
            <span class="blob blob-3"></span>
        </div>
        <header class="topbar">
            <button class="menu-toggle" type="button" data-menu-toggle aria-label="Menüyü Aç">&#9776;</button>

            <div class="topbar-title">
                <h1><?= e($pageTitle) ?></h1>
                <p class="topbar-subtitle">Kurum araç hareketleri, bakım planları ve gider kayıtları tek ekranda.</p>
            </div>

            <div class="topbar-tools">
                <form class="topbar-search" method="get" action="<?= e(app_url('search/index.php')) ?>" autocomplete="off" data-quick-search-form>
                    <input
                        type="search"
                        name="q"
                        value="<?= e($searchQuery) ?>"
                        placeholder="Sayfada hızlı ara..."
                        autocomplete="off"
                        spellcheck="false"
                        autocapitalize="off"
                        data-lpignore="true"
                        data-quick-search-input
                    >
                    <div class="quick-search-dropdown" data-quick-search-dropdown>
                        <div class="quick-search-head">
                            <strong data-quick-search-title>Arama Önerileri</strong>
                            <span class="quick-search-shortcut">Ctrl + K</span>
                        </div>
                        <div class="quick-search-list" data-quick-search-list>
                            <p class="empty-state">Aramaya başlayın...</p>
                        </div>
                    </div>
                </form>

                <button class="icon-button theme-toggle" type="button" data-theme-toggle title="Tema Değiştir">
                    <span data-theme-icon aria-hidden="true">🌙</span>
                    <span class="theme-toggle-label" data-theme-label>Koyu</span>
                </button>

                <div class="notification-wrap" data-notification-wrap>
                    <button class="icon-button" type="button" title="Bildirimler" data-notification-toggle>
                        <span aria-hidden="true">&#128276;</span>
                        <span class="icon-badge <?= $notificationCount > 0 ? '' : 'hidden' ?>" data-notification-badge><?= e((string) $notificationCount) ?></span>
                    </button>
                    <div class="notification-dropdown" data-notification-dropdown>
                        <div class="notification-head">
                            <strong>Bildirimler</strong>
                            <button type="button" class="link-button" data-mark-all-read>Tümünü okundu yap</button>
                        </div>
                        <div class="notification-list" data-notification-list>
                            <p class="empty-state">Yükleniyor...</p>
                        </div>
                        <div class="notification-foot">
                            <a href="<?= e(app_url('notifications/index.php')) ?>">Tüm bildirimleri gör</a>
                        </div>
                    </div>
                </div>

                <div class="topbar-user">
                    <div class="topbar-avatar"><?= e($initials ?: 'RT') ?></div>
                    <div>
                        <span><?= e($fullName) ?></span>
                        <small><?= e(mb_strtoupper((string) ($user['role'] ?? ''), 'UTF-8')) ?></small>
                    </div>
                    <form accept-charset="UTF-8" method="post" action="<?= e(app_url('auth/logout.php')) ?>" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <button class="button ghost small" type="submit">Çıkış</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="content">
            <?php if ($flash): ?>
                <div class="alert <?= e((string) $flash['type']) ?>" data-alert>
                    <span><?= e((string) $flash['message']) ?></span>
                    <button type="button" data-dismiss-alert aria-label="Mesajı Kapat">&times;</button>
                </div>
            <?php endif; ?>
