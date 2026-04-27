<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();
require_once __DIR__ . '/../includes/BackupManager.php';

$activeMenu = 'backups';
$pageTitle = 'Yedek Detayı';
$id = (int) ($_GET['id'] ?? 0);

$manager = new BackupManager(db(), dirname(__DIR__));
$manager->ensureSchema();
$backup = $manager->getBackupById($id);
if (!$backup) {
    set_flash('error', 'Yedek kaydı bulunamadı.');
    redirect('admin/backups.php');
}

$duration = '-';
if (!empty($backup['started_at']) && !empty($backup['finished_at'])) {
    $start = strtotime((string) $backup['started_at']);
    $finish = strtotime((string) $backup['finished_at']);
    if ($start !== false && $finish !== false && $finish >= $start) {
        $duration = (string) ($finish - $start) . ' saniye';
    }
}

require __DIR__ . '/../includes/header.php';
?>
<section class="card">
    <div class="card-head">
        <h3>Yedek Detayı</h3>
        <a class="btn ghost" href="<?= e(app_url('admin/backups.php')) ?>">Geri Dön</a>
    </div>

    <div class="grid two">
        <article class="card">
            <h3>Dosya Bilgisi</h3>
            <p><strong>Dosya Adı:</strong> <?= e((string) $backup['file_name']) ?></p>
            <p><strong>Dosya Yolu:</strong> <?= e((string) $backup['file_path']) ?></p>
            <p><strong>Boyut:</strong> <?= e($manager->humanFileSize((int) $backup['file_size'])) ?></p>
            <p><strong>Checksum (SHA-256):</strong> <?= e((string) ($backup['checksum'] ?? '-')) ?></p>
        </article>
        <article class="card">
            <h3>İşlem Bilgisi</h3>
            <p><strong>Yedek Türü:</strong> <?= e((string) $backup['backup_type']) ?></p>
            <p><strong>Durum:</strong> <span class="<?= e(badge_status((string) $backup['status'])) ?>"><?= e((string) $backup['status']) ?></span></p>
            <p><strong>Başlangıç:</strong> <?= e(format_date((string) $backup['started_at'])) ?></p>
            <p><strong>Bitiş:</strong> <?= e(format_date((string) $backup['finished_at'])) ?></p>
            <p><strong>Süre:</strong> <?= e($duration) ?></p>
            <p><strong>Başlatan:</strong> <?= e((string) ($backup['full_name'] ?? 'Sistem')) ?></p>
        </article>
    </div>

    <?php if (!empty($backup['error_message'])): ?>
        <div class="alert error"><strong>Hata Mesajı:</strong> <?= e((string) $backup['error_message']) ?></div>
    <?php endif; ?>

    <div class="actions">
        <?php if ((string) $backup['status'] === 'başarılı'): ?>
            <a class="btn primary" href="<?= e(app_url('actions/download-backup.php?id=' . (int) $backup['id'])) ?>">Yedeği İndir</a>
        <?php endif; ?>
        <form method="post" action="<?= e(app_url('actions/delete-backup.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= e((string) $backup['id']) ?>">
            <button class="btn ghost" type="submit" data-confirm="Bu yedek silinsin mi?">Yedeği Sil</button>
        </form>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
