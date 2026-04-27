<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$pageTitle = 'Personel Yönetimi';
$rows = fetch_all(
    'SELECT p.*,
            u.username,
            u.role,
            u.is_active AS user_is_active
     FROM personnel p
     LEFT JOIN users u ON u.id = p.user_id
     ORDER BY p.created_at DESC'
);

include __DIR__ . '/../../includes/header.php';
?>
<section class="panel">
    <div class="panel-head">
        <h2>Personel Listesi</h2>
        <a class="button primary" href="<?= e(app_url('modules/personnel/form.php')) ?>">Yeni Personel Ekle</a>
    </div>
    <div class="panel-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Ad Soyad</th>
                    <th>Sicil No</th>
                    <th>Departman</th>
                    <th>Görev</th>
                    <th>Kullanıcı</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['full_name']) ?></td>
                    <td><?= e($row['registration_no']) ?></td>
                    <td><?= e($row['department']) ?></td>
                    <td><?= e($row['duty_title']) ?></td>
                    <td>
                        <?php if ($row['username']): ?>
                            <span><?= e($row['username']) ?></span>
                            <span class="<?= e((int) $row['user_is_active'] === 1 ? 'badge success' : 'badge danger') ?>">
                                <?= e((int) $row['user_is_active'] === 1 ? 'aktif' : 'pasif') ?>
                            </span>
                        <?php else: ?>
                            <span class="badge neutral">bağlı değil</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="<?= e(badge_class($row['status'])) ?>"><?= e($row['status']) ?></span></td>
                    <td class="actions">
                        <a href="<?= e(app_url('modules/personnel/view.php?id=' . $row['id'])) ?>">Detay</a>
                        <a href="<?= e(app_url('modules/personnel/form.php?id=' . $row['id'])) ?>">Düzenle</a>
                        <form accept-charset="UTF-8" method="post" action="<?= e(app_url('modules/personnel/delete.php')) ?>" class="inline-form">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
                            <button class="link-button danger-link" type="submit" data-confirm="Personel kaydı silinsin mi?">Sil</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
