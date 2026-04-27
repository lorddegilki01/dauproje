<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$pageTitle = 'Duyuru Yönetimi';
$errors = [];

if (is_post()) {
    verify_csrf();

    if (isset($_POST['toggle_id'])) {
        $toggleId = (int) $_POST['toggle_id'];
        execute_query(
            'UPDATE announcements SET is_active = IF(is_active = 1, 0, 1) WHERE id = :id',
            ['id' => $toggleId]
        );
        set_flash('success', 'Duyuru durumu güncellendi.');
        redirect('modules/announcements/index.php');
    }

    $title = trim((string) ($_POST['title'] ?? ''));
    $content = trim((string) ($_POST['content'] ?? ''));
    $targetRole = trim((string) ($_POST['target_role'] ?? 'tümü'));

    if ($title === '' || $content === '') {
        $errors[] = 'Başlık ve içerik zorunludur.';
    }

    if (!$errors) {
        execute_query(
            'INSERT INTO announcements (title, content, target_role, is_active, created_by)
             VALUES (:title, :content, :target_role, 1, :created_by)',
            [
                'title' => $title,
                'content' => $content,
                'target_role' => $targetRole,
                'created_by' => (int) current_user()['id'],
            ]
        );
        log_activity('Duyuru eklendi', 'Duyuru Yönetimi', 'Yeni duyuru yayınlandı.');
        set_flash('success', 'Duyuru oluşturuldu.');
        redirect('modules/announcements/index.php');
    }
}

$rows = fetch_all(
    'SELECT a.*, u.full_name AS creator_name
     FROM announcements a
     LEFT JOIN users u ON u.id = a.created_by
     ORDER BY a.created_at DESC'
);

include __DIR__ . '/../../includes/header.php';
?>
<section class="panel">
    <div class="panel-head"><h2>Yeni Duyuru</h2></div>
    <div class="panel-body">
        <?php foreach ($errors as $error): ?>
            <div class="alert error"><span><?= e($error) ?></span></div>
        <?php endforeach; ?>
        <form accept-charset="UTF-8" class="form-grid" method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>Başlık
                <input type="text" name="title" maxlength="160" required>
            </label>
            <label>Hedef Rol
                <select name="target_role" required>
                    <option value="tümü">Tümü</option>
                    <option value="admin">Sadece Admin</option>
                    <option value="personel">Sadece Personel</option>
                </select>
            </label>
            <label class="full-width">İçerik
                <textarea name="content" rows="4" required></textarea>
            </label>
            <div class="form-actions full-width">
                <button class="button primary" type="submit">Yayınla</button>
            </div>
        </form>
    </div>
</section>

<section class="panel">
    <div class="panel-head"><h2>Duyuru Listesi</h2></div>
    <div class="panel-body">
        <table class="table">
            <thead><tr><th>Başlık</th><th>Hedef</th><th>Durum</th><th>Yayınlayan</th><th>Tarih</th><th>İşlem</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['title']) ?></td>
                    <td><?= e($row['target_role']) ?></td>
                    <td><?= e($row['is_active'] ? 'Aktif' : 'Pasif') ?></td>
                    <td><?= e($row['creator_name'] ?? 'Sistem') ?></td>
                    <td><?= e(format_date($row['created_at'], 'd.m.Y H:i')) ?></td>
                    <td>
                        <form accept-charset="UTF-8" method="post" class="inline-form">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="toggle_id" value="<?= e((string) $row['id']) ?>">
                            <button class="link-button" type="submit"><?= e($row['is_active'] ? 'Pasife Al' : 'Aktife Al') ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
