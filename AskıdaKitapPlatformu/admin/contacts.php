<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$activeMenu = '';
$pageTitle = 'İletişim Mesajları';

if (is_post()) {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $status = (string) ($_POST['status'] ?? 'okundu');
    if (in_array($status, ['yeni', 'okundu', 'yanıtlandı'], true)) {
        execute_query('UPDATE contacts SET status = :status WHERE id = :id', ['status' => $status, 'id' => $id]);
        set_flash('success', 'Mesaj durumu güncellendi.');
        redirect('admin/contacts.php');
    }
}

$contacts = fetch_all('SELECT * FROM contacts ORDER BY created_at DESC');
require __DIR__ . '/../includes/header.php';
?>
<section class="card">
    <h2>İletişim Mesajları</h2>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Tarih</th><th>Ad Soyad</th><th>E-posta</th><th>Konu</th><th>Mesaj</th><th>Durum</th></tr></thead>
            <tbody>
            <?php foreach ($contacts as $contact): ?>
                <tr>
                    <td><?= e(format_date((string) $contact['created_at'])) ?></td>
                    <td><?= e((string) $contact['full_name']) ?></td>
                    <td><?= e((string) $contact['email']) ?></td>
                    <td><?= e((string) $contact['subject']) ?></td>
                    <td><?= e((string) $contact['message']) ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= e((string) $contact['id']) ?>">
                            <select name="status" onchange="this.form.submit()">
                                <option value="yeni" <?= $contact['status'] === 'yeni' ? 'selected' : '' ?>>Yeni</option>
                                <option value="okundu" <?= $contact['status'] === 'okundu' ? 'selected' : '' ?>>Okundu</option>
                                <option value="yanıtlandı" <?= $contact['status'] === 'yanıtlandı' ? 'selected' : '' ?>>Yanıtlandı</option>
                            </select>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

