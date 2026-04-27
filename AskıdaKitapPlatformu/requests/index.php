<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_login();

$activeMenu = 'my_requests';
$pageTitle = 'Taleplerim';
$user = current_user();

$myRequests = fetch_all(
    'SELECT r.*, b.title, b.author, b.city, b.status book_status, u.full_name donor_name
     FROM book_requests r
     INNER JOIN books b ON b.id = r.book_id
     INNER JOIN users u ON u.id = b.donor_user_id
     WHERE r.requester_user_id = :id
     ORDER BY r.created_at DESC',
    ['id' => (int) $user['id']]
);

require __DIR__ . '/../includes/header.php';
?>
<section class="card">
    <div class="card-head">
        <h2>Taleplerim</h2>
        <a class="btn ghost" href="<?= e(app_url('books/index.php')) ?>">Yeni Talep</a>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Kitap</th>
                    <th>Bağışçı</th>
                    <th>Talep Tarihi</th>
                    <th>Talep Durumu</th>
                    <th>Kitap Durumu</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$myRequests): ?>
                    <tr><td colspan="6">Henüz talep kaydınız yok.</td></tr>
                <?php endif; ?>
                <?php foreach ($myRequests as $request): ?>
                    <tr>
                        <td><?= e((string) $request['title']) ?></td>
                        <td><?= e((string) $request['donor_name']) ?></td>
                        <td><?= e(format_date((string) $request['created_at'])) ?></td>
                        <td><span class="<?= e(badge_class((string) $request['request_status'])) ?>"><?= e(request_status_label((string) $request['request_status'])) ?></span></td>
                        <td><span class="<?= e(badge_class((string) $request['book_status'])) ?>"><?= e(book_status_label((string) $request['book_status'])) ?></span></td>
                        <td>
                            <a href="<?= e(app_url('books/view.php?id=' . (int) $request['book_id'])) ?>">Kitabı Gör</a>
                            <?php if ((string) $request['request_status'] === 'bekliyor'): ?>
                                · <a class="danger-link" href="<?= e(app_url('requests/update_status.php?action=cancel&id=' . (int) $request['id'])) ?>" onclick="return confirm('Talebi iptal etmek istediğinize emin misiniz?')">İptal Et</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if (!empty($request['donor_note'])): ?>
                        <tr><td colspan="6"><strong>Bağışçı Notu:</strong> <?= e((string) $request['donor_note']) ?></td></tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

