<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_login();

$activeMenu = '';
$pageTitle = 'Bildirimler';
$user = current_user();

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'read_all') {
        execute_query('UPDATE notifications SET is_read = 1 WHERE user_id IS NULL OR user_id = :id', ['id' => (int) $user['id']]);
        set_flash('success', 'Bildirimler okundu olarak işaretlendi.');
        redirect('notifications/index.php');
    }
}

$notifications = fetch_all(
    'SELECT * FROM notifications WHERE user_id IS NULL OR user_id = :id ORDER BY created_at DESC',
    ['id' => (int) $user['id']]
);
$unread = count_value('SELECT COUNT(*) FROM notifications WHERE is_read = 0 AND (user_id IS NULL OR user_id = :id)', ['id' => (int) $user['id']]);

require __DIR__ . '/../includes/header.php';
?>
<section class="card">
    <div class="card-head">
        <h2>Bildirimlerim</h2>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="read_all">
            <button class="btn ghost" type="submit">Tümünü Okundu Yap</button>
        </form>
    </div>
    <p class="muted">Okunmamış bildirim: <strong><?= e((string) $unread) ?></strong></p>
    <ul class="list">
        <?php if (!$notifications): ?><li>Bildirim bulunmuyor.</li><?php endif; ?>
        <?php foreach ($notifications as $note): ?>
            <li>
                <div>
                    <a href="<?= e((string) $note['target_url']) ?>"><?= e((string) $note['title']) ?></a>
                    <div class="muted"><?= e((string) $note['message']) ?></div>
                </div>
                <div>
                    <span class="<?= e((int) $note['is_read'] === 1 ? 'badge neutral' : 'badge warning') ?>"><?= (int) $note['is_read'] === 1 ? 'Okundu' : 'Yeni' ?></span>
                    <small><?= e(format_date((string) $note['created_at'])) ?></small>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

