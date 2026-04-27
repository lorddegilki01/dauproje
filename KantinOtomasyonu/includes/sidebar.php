<?php
declare(strict_types=1);
$user = current_user();
$isAdmin = ($user['role'] ?? '') === 'admin';
?>
<aside class="sidebar" id="sidebarPanel">
    <div class="brand">
        <div class="logo">KO</div>
        <div>
            <strong>Kantin Yönetim</strong>
            <small><?= $isAdmin ? 'Admin Paneli' : 'Kasiyer Paneli' ?></small>
        </div>
        <button class="sidebar-close" type="button" id="closeSidebarBtn" aria-label="Menüyü kapat">&times;</button>
    </div>

    <div class="menu-group">
        <span class="menu-title">Genel</span>
        <nav class="menu-list">
            <a class="<?= e(active_menu('dashboard')) ?>" href="<?= e(app_url('index.php')) ?>">
                <span class="nav-icon">&#127968;</span><span>Dashboard</span>
            </a>
            <a class="<?= e(active_menu('sales')) ?>" href="<?= e(app_url('modules/sales/index.php')) ?>">
                <span class="nav-icon">&#129534;</span><span>Satışlar</span>
            </a>
            <a class="<?= e(active_menu('notifications')) ?>" href="<?= e(app_url('modules/notifications/index.php')) ?>">
                <span class="nav-icon">&#128276;</span><span>Bildirimler</span>
            </a>
        </nav>
    </div>

    <?php if ($isAdmin): ?>
        <div class="menu-group">
            <span class="menu-title">Yönetim</span>
            <nav class="menu-list">
                <a class="<?= e(active_menu('products')) ?>" href="<?= e(app_url('modules/products/index.php')) ?>">
                    <span class="nav-icon">&#128230;</span><span>Ürünler</span>
                </a>
                <a class="<?= e(active_menu('categories')) ?>" href="<?= e(app_url('modules/categories/index.php')) ?>">
                    <span class="nav-icon">&#128451;</span><span>Kategoriler</span>
                </a>
                <a class="<?= e(active_menu('stock')) ?>" href="<?= e(app_url('modules/stock/index.php')) ?>">
                    <span class="nav-icon">&#128200;</span><span>Stok Takibi</span>
                </a>
                <a class="<?= e(active_menu('expenses')) ?>" href="<?= e(app_url('modules/expenses/index.php')) ?>">
                    <span class="nav-icon">&#128184;</span><span>Giderler</span>
                </a>
                <a class="<?= e(active_menu('reports')) ?>" href="<?= e(app_url('modules/reports/index.php')) ?>">
                    <span class="nav-icon">&#128202;</span><span>Raporlar</span>
                </a>
                <a class="<?= e(active_menu('users')) ?>" href="<?= e(app_url('modules/users/index.php')) ?>">
                    <span class="nav-icon">&#128101;</span><span>Kullanıcı Yönetimi</span>
                </a>
                <a class="<?= e(active_menu('cashiers')) ?>" href="<?= e(app_url('modules/users/index.php?role=kasiyer')) ?>">
                    <span class="nav-icon">&#128179;</span><span>Kasiyer Yönetimi</span>
                </a>
            </nav>
        </div>
    <?php else: ?>
        <div class="menu-group">
            <span class="menu-title">Kasiyer</span>
            <nav class="menu-list">
                <a class="<?= e(active_menu('stock')) ?>" href="<?= e(app_url('modules/stock/index.php')) ?>">
                    <span class="nav-icon">&#128202;</span><span>Stok Görünümü</span>
                </a>
                <a class="<?= e(active_menu('my-sales')) ?>" href="<?= e(app_url('modules/sales/index.php?mine=1')) ?>">
                    <span class="nav-icon">&#128221;</span><span>Günlük Satışlarım</span>
                </a>
            </nav>
        </div>
    <?php endif; ?>

    <div class="menu-group">
        <span class="menu-title">Hesap</span>
        <nav class="menu-list">
            <a class="<?= e(active_menu('profile')) ?>" href="<?= e(app_url('modules/profile/index.php')) ?>">
                <span class="nav-icon">&#128100;</span><span>Profilim</span>
            </a>
            <a class="<?= e(active_menu('password')) ?>" href="<?= e(app_url('modules/profile/password.php')) ?>">
                <span class="nav-icon">&#128274;</span><span>Şifre Değiştir</span>
            </a>
        </nav>
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
