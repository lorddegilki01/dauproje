<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? APP_NAME;
$activeMenu = $activeMenu ?? '';
$user = current_user();
$isAdmin = $user && ($user['role'] ?? '') === ROLE_ADMIN;
$notificationCount = $user ? unread_notification_count((int) $user['id']) : 0;
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <meta name="description" content="Askıda Kitap Platformu ile kitap bağışı ve kitap talep süreçlerini güvenle yönetin.">
    <link rel="stylesheet" href="<?= e(app_url('assets/css/style.css?v=' . (string) @filemtime(__DIR__ . '/../assets/css/style.css'))) ?>">
</head>
<body class="app-shell">
<div class="bg-orbs" aria-hidden="true">
    <span class="orb orb-a"></span>
    <span class="orb orb-b"></span>
    <span class="orb orb-c"></span>
</div>

<header class="site-header">
    <div class="container nav">
        <a class="brand" href="<?= e(app_url('index.php')) ?>">
            <span class="brand-logo">AK</span>
            <span class="brand-meta">
                <span class="brand-text">Askıda Kitap</span>
                <small>Dayanışma Platformu</small>
            </span>
        </a>

        <button class="menu-toggle" type="button" data-menu-toggle aria-label="Menüyü Aç/Kapat">☰</button>

        <nav class="menu" data-main-menu>
            <a class="<?= $activeMenu === 'home' ? 'active' : '' ?>" href="<?= e(app_url('index.php')) ?>">Ana Sayfa</a>
            <a class="<?= $activeMenu === 'books' ? 'active' : '' ?>" href="<?= e(app_url('books/index.php')) ?>">Askıdaki Kitaplar</a>
            <?php if ($user): ?>
                <a class="<?= $activeMenu === 'panel' ? 'active' : '' ?>" href="<?= e(app_url('user/dashboard.php')) ?>">Panelim</a>
                <a class="<?= $activeMenu === 'my_books' ? 'active' : '' ?>" href="<?= e(app_url('books/my_books.php')) ?>">Bağışlarım</a>
                <a class="<?= $activeMenu === 'my_requests' ? 'active' : '' ?>" href="<?= e(app_url('requests/index.php')) ?>">Taleplerim</a>
                <a class="<?= $activeMenu === 'incoming_requests' ? 'active' : '' ?>" href="<?= e(app_url('requests/manage.php')) ?>">Gelen Talepler</a>
                <a class="<?= $activeMenu === 'matches' ? 'active' : '' ?>" href="<?= e(app_url('matches/index.php')) ?>">Teslim Süreci</a>
                <?php if ($isAdmin): ?>
                    <a class="<?= $activeMenu === 'password_changes' ? 'active' : '' ?>" href="<?= e(app_url('admin/password_changes.php')) ?>">Şifre Geçmişi</a>
                    <a class="<?= $activeMenu === 'backups' ? 'active' : '' ?>" href="<?= e(app_url('admin/backups.php')) ?>">Yedekleme</a>
                <?php endif; ?>
            <?php endif; ?>
            <a class="<?= $activeMenu === 'about' ? 'active' : '' ?>" href="<?= e(app_url('pages/about.php')) ?>">Hakkımızda</a>
            <a class="<?= $activeMenu === 'faq' ? 'active' : '' ?>" href="<?= e(app_url('pages/faq.php')) ?>">SSS</a>
            <a class="<?= $activeMenu === 'contact' ? 'active' : '' ?>" href="<?= e(app_url('pages/contact.php')) ?>">İletişim</a>
        </nav>

        <div class="auth-links">
            <button type="button" class="btn ghost icon-btn" data-theme-toggle aria-label="Tema Değiştir">◐</button>
            <?php if ($user): ?>
                <a href="<?= e(app_url('notifications/index.php')) ?>" class="icon-btn notify-btn" aria-label="Bildirimler">
                    🔔
                    <?php if ($notificationCount > 0): ?>
                        <span class="badge-dot"><?= e((string) $notificationCount) ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= e(app_url('profile.php')) ?>" class="avatar-link">
                    <span class="avatar"><?= e(mb_substr((string) $user['full_name'], 0, 1, 'UTF-8')) ?></span>
                    <span><?= e((string) $user['full_name']) ?></span>
                </a>
                <?php if ($isAdmin): ?>
                    <a class="btn ghost nav-chip" href="<?= e(app_url('admin/dashboard.php')) ?>">Admin</a>
                <?php endif; ?>
                <form method="post" action="<?= e(app_url('auth/logout.php')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <button class="btn ghost nav-chip" type="submit">Çıkış</button>
                </form>
            <?php else: ?>
                <a class="nav-link-cta" href="<?= e(app_url('auth/login.php')) ?>">Giriş Yap</a>
                <a class="btn primary nav-chip shine" href="<?= e(app_url('auth/register.php')) ?>">Kayıt Ol</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="container page">
    <?php $flash = get_flash(); ?>
    <?php if ($flash): ?>
        <div class="alert <?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div>
    <?php endif; ?>
