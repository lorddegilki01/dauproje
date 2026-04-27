<?php
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$basePath = rtrim(str_replace('\\', '/', BASE_URL), '/');
$relativePath = ltrim(str_starts_with($scriptPath, $basePath) ? substr($scriptPath, strlen($basePath)) : $scriptPath, '/');
$user = current_user();
$isAdmin = ($user['role'] ?? '') === 'admin';

$menuGroups = $isAdmin
    ? [
        [
            'title' => 'Genel',
            'items' => [
                ['label' => 'Dashboard', 'path' => 'index.php', 'icon' => '&#9670;'],
            ],
        ],
        [
            'title' => 'Operasyon',
            'items' => [
                ['label' => 'Araç Yönetimi', 'path' => 'modules/vehicles/index.php', 'icon' => '&#9673;'],
                ['label' => 'Personel Yönetimi', 'path' => 'modules/personnel/index.php', 'icon' => '&#9674;'],
                ['label' => 'Kullanıcı Yönetimi', 'path' => 'modules/users/index.php', 'icon' => '&#9634;'],
                ['label' => 'Araç Zimmetleri', 'path' => 'modules/assignments/index.php', 'icon' => '&#11034;'],
                ['label' => 'Araç Talepleri', 'path' => 'modules/requests/index.php', 'icon' => '&#9671;'],
            ],
        ],
        [
            'title' => 'Bakım ve Gider',
            'items' => [
                ['label' => 'Yakıt Takibi', 'path' => 'modules/fuel/index.php', 'icon' => '&#9679;'],
                ['label' => 'Bakım Takibi', 'path' => 'modules/maintenance/index.php', 'icon' => '&#9681;'],
                ['label' => 'Arıza Bildirimleri', 'path' => 'modules/issues/index.php', 'icon' => '&#9680;'],
                ['label' => 'Arıza ve Masraf', 'path' => 'modules/expenses/index.php', 'icon' => '&#9642;'],
            ],
        ],
        [
            'title' => 'Raporlama',
            'items' => [
                ['label' => 'Duyurular', 'path' => 'modules/announcements/index.php', 'icon' => '&#9688;'],
                ['label' => 'Raporlar', 'path' => 'modules/reports/index.php', 'icon' => '&#9635;'],
                ['label' => 'Yedek ve Güvenlik', 'path' => 'modules/backups/index.php', 'icon' => '&#128274;'],
            ],
        ],
    ]
    : [
        [
            'title' => 'Personel Paneli',
            'items' => [
                ['label' => 'Panelim', 'path' => 'personnel/dashboard.php', 'icon' => '&#9670;'],
                ['label' => 'Araçlarım', 'path' => 'personnel/vehicles.php', 'icon' => '&#9673;'],
                ['label' => 'Araç Taleplerim', 'path' => 'personnel/requests.php', 'icon' => '&#9671;'],
                ['label' => 'Teslim Alma', 'path' => 'personnel/receive_vehicle.php', 'icon' => '&#9681;'],
                ['label' => 'Araç İade', 'path' => 'personnel/return_vehicle.php', 'icon' => '&#9680;'],
                ['label' => 'Yakıt Kayıtlarım', 'path' => 'personnel/fuel.php', 'icon' => '&#9679;'],
                ['label' => 'Arıza Bildirimlerim', 'path' => 'personnel/issues.php', 'icon' => '&#9642;'],
                ['label' => 'Kullanım Geçmişim', 'path' => 'personnel/history.php', 'icon' => '&#9688;'],
                ['label' => 'Profilim', 'path' => 'personnel/profile.php', 'icon' => '&#9635;'],
            ],
        ],
    ];
?>
<aside class="sidebar" data-sidebar>
    <div class="sidebar-brand">
        <div class="brand-icon">RT</div>
        <div>
            <strong><?= $isAdmin ? 'Resmi Araç Takip' : 'Personel Paneli' ?></strong>
            <small><?= $isAdmin ? 'Kurumsal Yönetim Paneli' : 'Kendi kayıtlarınız ve işlemleriniz' ?></small>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($menuGroups as $group): ?>
            <p class="sidebar-section-title"><?= e($group['title']) ?></p>
            <?php foreach ($group['items'] as $item): ?>
                <?php $active = $relativePath === $item['path'] ? 'active' : ''; ?>
                <a class="<?= e($active) ?>" href="<?= e(app_url($item['path'])) ?>">
                    <span class="menu-icon" aria-hidden="true"><?= $item['icon'] ?></span>
                    <span class="menu-label"><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>
</aside>
