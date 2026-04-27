<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_login();

$activeMenu = 'incoming_requests';
$pageTitle = 'Gelen Talepler';
$user = current_user();

if (is_admin()) {
    $requests = fetch_all(
        'SELECT r.*, b.title, b.status book_status, b.donor_user_id, u.full_name requester_name, d.full_name donor_name
         FROM book_requests r
         INNER JOIN books b ON b.id = r.book_id
         INNER JOIN users u ON u.id = r.requester_user_id
         INNER JOIN users d ON d.id = b.donor_user_id
         ORDER BY r.created_at DESC'
    );
} else {
    $requests = fetch_all(
        'SELECT r.*, b.title, b.status book_status, b.donor_user_id, u.full_name requester_name, d.full_name donor_name
         FROM book_requests r
         INNER JOIN books b ON b.id = r.book_id
         INNER JOIN users u ON u.id = r.requester_user_id
         INNER JOIN users d ON d.id = b.donor_user_id
         WHERE b.donor_user_id = :user_id
         ORDER BY r.created_at DESC',
        ['user_id' => (int) $user['id']]
    );
}

require __DIR__ . '/../includes/header.php';
?>
<section class="card">
    <h2><?= is_admin() ? 'Tüm Kitap Talepleri' : 'Kitaplarıma Gelen Talepler' ?></h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Kitap</th>
                    <th>Talep Eden</th>
                    <th>Talep Notu</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$requests): ?>
                    <tr><td colspan="6">Yönetilecek talep kaydı yok.</td></tr>
                <?php endif; ?>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?= e((string) $request['title']) ?></td>
                        <td><?= e((string) $request['requester_name']) ?></td>
                        <td><?= e((string) $request['request_note']) ?></td>
                        <td><span class="<?= e(badge_class((string) $request['request_status'])) ?>"><?= e(request_status_label((string) $request['request_status'])) ?></span></td>
                        <td><?= e(format_date((string) $request['created_at'])) ?></td>
                        <td>
                            <?php if ((string) $request['request_status'] === 'bekliyor'): ?>
                                <a href="<?= e(app_url('requests/update_status.php?action=approve&id=' . (int) $request['id'])) ?>">Onayla</a> ·
                                <a class="danger-link" href="<?= e(app_url('requests/update_status.php?action=reject&id=' . (int) $request['id'])) ?>">Reddet</a>
                            <?php else: ?>
                                <span>-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if (!empty($request['donor_note'])): ?>
                        <tr><td colspan="6"><strong>Not:</strong> <?= e((string) $request['donor_note']) ?></td></tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
