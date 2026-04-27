<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_login();

$pageTitle = 'Tüm Bildirimler';
$user = current_user();

if ($user && is_admin()) {
    sync_admin_notifications((int) $user['id']);
}

if (is_post()) {
    verify_csrf();
    notification_mark_all_read((int) ($user['id'] ?? 0));
    set_flash('success', 'Tüm bildirimler okundu olarak işaretlendi.');
    redirect('notifications/index.php');
}

$items = notification_recent_list((int) ($user['id'] ?? 0), 200);

include __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <div class="panel-head">
        <h2>Bildirim Geçmişi</h2>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <button class="button ghost" type="submit">Tümünü Okundu Yap</button>
        </form>
    </div>
    <div class="panel-body">
        <?php if (!$items): ?>
            <p class="empty-state">Bildirim bulunmuyor.</p>
        <?php else: ?>
            <ul class="notification-list-page">
                <?php foreach ($items as $item): ?>
                    <li class="notification-item <?= (int) $item['is_read'] === 0 ? 'unread' : '' ?>">
                        <span class="notification-item-icon <?= e((string) ($item['color_class'] ?: 'neutral')) ?>"><?= e((string) ($item['icon'] ?: '•')) ?></span>
                        <div class="notification-item-content">
                            <strong><?= e((string) $item['title']) ?></strong>
                            <p><?= e((string) $item['message']) ?></p>
                            <small><?= e((string) $item['time_ago']) ?></small>
                        </div>
                        <?php if (!empty($item['url'])): ?>
                            <a class="button ghost small" href="<?= e((string) $item['url']) ?>">Aç</a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
