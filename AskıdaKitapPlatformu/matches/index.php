<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_login();

$activeMenu = 'matches';
$pageTitle = 'Teslim ve Eşleşmeler';
$user = current_user();

if (is_admin()) {
    $matches = fetch_all(
        'SELECT m.*, b.title, d.full_name donor_name, r.full_name requester_name
         FROM matches m
         INNER JOIN books b ON b.id = m.book_id
         INNER JOIN users d ON d.id = m.donor_user_id
         INNER JOIN users r ON r.id = m.requester_user_id
         ORDER BY m.created_at DESC'
    );
} else {
    $matches = fetch_all(
        'SELECT m.*, b.title, d.full_name donor_name, r.full_name requester_name
         FROM matches m
         INNER JOIN books b ON b.id = m.book_id
         INNER JOIN users d ON d.id = m.donor_user_id
         INNER JOIN users r ON r.id = m.requester_user_id
         WHERE m.donor_user_id = :id OR m.requester_user_id = :id
         ORDER BY m.created_at DESC',
        ['id' => (int) $user['id']]
    );
}

require __DIR__ . '/../includes/header.php';
?>
<section class="card">
    <h2><?= is_admin() ? 'Tüm Eşleşmeler' : 'Teslim Süreçlerim' ?></h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Kitap</th>
                    <th>Bağışçı</th>
                    <th>Talep Eden</th>
                    <th>Durum</th>
                    <th>Teslim Tarihi</th>
                    <th>Not</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$matches): ?>
                    <tr><td colspan="7">Henüz eşleşme kaydı bulunmuyor.</td></tr>
                <?php endif; ?>
                <?php foreach ($matches as $match): ?>
                    <tr>
                        <td><?= e((string) $match['title']) ?></td>
                        <td><?= e((string) $match['donor_name']) ?></td>
                        <td><?= e((string) $match['requester_name']) ?></td>
                        <td><span class="<?= e(badge_class((string) $match['delivery_status'])) ?>"><?= e((string) $match['delivery_status']) ?></span></td>
                        <td><?= e(format_date($match['delivery_date'] ? (string) $match['delivery_date'] : null)) ?></td>
                        <td><?= e((string) ($match['delivery_note'] ?? '-')) ?></td>
                        <td>
                            <?php if ((string) $match['delivery_status'] === 'bekliyor'): ?>
                                <a href="<?= e(app_url('matches/update_status.php?action=delivered&id=' . (int) $match['id'])) ?>">Teslim Edildi</a>
                                ·
                                <a class="danger-link" href="<?= e(app_url('matches/update_status.php?action=cancel&id=' . (int) $match['id'])) ?>">İptal</a>
                            <?php else: ?>
                                <span>-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

