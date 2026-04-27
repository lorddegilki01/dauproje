<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();
require_once __DIR__ . '/../includes/BackupManager.php';

$activeMenu = 'backups';
$pageTitle = 'Yedekleme Yönetimi';

$manager = new BackupManager(db(), dirname(__DIR__));
$manager->ensureSchema();
$stats = $manager->getStats();
$settings = $manager->getSettings();
$backups = $manager->listBackups(200);

require __DIR__ . '/../includes/header.php';
?>
<div class="stats">
    <article class="card stat">
        <div class="kpi-top">
            <div class="kpi-icon">🗄️</div>
            <div class="kpi-meta"><h3>Otomatik Durum</h3><small>planlayıcı</small></div>
        </div>
        <strong><?= e((int) $stats['automatic_enabled'] === 1 ? 'Aktif' : 'Pasif') ?></strong>
        <canvas class="sparkline" data-points="5,6,6,7,7,8,8"></canvas>
    </article>
    <article class="card stat">
        <div class="kpi-top">
            <div class="kpi-icon">✅</div>
            <div class="kpi-meta"><h3>Son Başarılı</h3><small>zaman</small></div>
        </div>
        <strong><?= e(format_date((string) ($stats['last_success_at'] ?? ''), 'd.m.Y H:i')) ?></strong>
        <canvas class="sparkline" data-points="4,5,6,6,7,8,9"></canvas>
    </article>
    <article class="card stat">
        <div class="kpi-top">
            <div class="kpi-icon">⚠️</div>
            <div class="kpi-meta"><h3>Son Başarısız</h3><small>zaman</small></div>
        </div>
        <strong><?= e(format_date((string) ($stats['last_failed_at'] ?? ''), 'd.m.Y H:i')) ?></strong>
        <canvas class="sparkline" data-points="9,8,8,7,7,6,6"></canvas>
    </article>
    <article class="card stat">
        <div class="kpi-top">
            <div class="kpi-icon">📦</div>
            <div class="kpi-meta"><h3>Toplam Yedek</h3><small>adet / boyut</small></div>
        </div>
        <strong><?= e((string) $stats['total_count']) ?></strong>
        <small><?= e($manager->humanFileSize((int) $stats['total_size'])) ?></small>
        <canvas class="sparkline" data-points="3,4,4,5,6,6,7"></canvas>
    </article>
</div>

<?php if (!empty($stats['last_error'])): ?>
    <section class="card">
        <h3>Son Hata</h3>
        <div class="alert error"><?= e((string) $stats['last_error']) ?></div>
    </section>
<?php endif; ?>

<section class="card">
    <div class="card-head">
        <h3>Yedekleme Ayarları</h3>
        <form method="post" action="<?= e(app_url('actions/create-backup.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <button class="btn primary" type="submit">Manuel Yedek Al</button>
        </form>
    </div>
    <form method="post" action="<?= e(app_url('actions/update-backup-settings.php')) ?>" class="form grid-2">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>
            <span>Otomatik Yedekleme</span>
            <input type="checkbox" name="automatic_enabled" value="1" <?= (int) $settings['automatic_enabled'] === 1 ? 'checked' : '' ?>>
        </label>
        <label>
            <span>Eski Yedekleri Otomatik Temizle</span>
            <input type="checkbox" name="auto_cleanup_enabled" value="1" <?= (int) $settings['auto_cleanup_enabled'] === 1 ? 'checked' : '' ?>>
        </label>
        <label>Yedekleme Sıklığı
            <select name="frequency">
                <option value="daily" <?= (string) $settings['frequency'] === 'daily' ? 'selected' : '' ?>>Günlük</option>
                <option value="weekly" <?= (string) $settings['frequency'] === 'weekly' ? 'selected' : '' ?>>Haftalık</option>
                <option value="monthly" <?= (string) $settings['frequency'] === 'monthly' ? 'selected' : '' ?>>Aylık</option>
            </select>
        </label>
        <label>Yedekleme Saati
            <input type="time" name="backup_time" value="<?= e((string) $settings['backup_time']) ?>" required>
        </label>
        <label>Saklanacak Maksimum Yedek Sayısı
            <input type="number" min="1" max="500" name="max_backup_count" value="<?= e((string) $settings['max_backup_count']) ?>">
        </label>
        <label>Yedek Klasörü
            <input type="text" name="backup_path" value="<?= e((string) $settings['backup_path']) ?>" placeholder="storage/backups">
        </label>
        <label>Sonraki Planlanan Çalışma
            <input type="text" value="<?= e(format_date((string) ($settings['next_run_at'] ?? ''), 'd.m.Y H:i')) ?>" disabled>
        </label>
        <label>Cron Komutu
            <input type="text" value="<?= e('php ' . dirname(__DIR__) . '/cron/backup_runner.php') ?>" readonly>
        </label>
        <div class="full actions">
            <button class="btn primary" type="submit">Ayarları Kaydet</button>
        </div>
    </form>
</section>

<section class="card">
    <h3>Yedek Geçmişi</h3>
    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr>
                <th>Dosya</th>
                <th>Tür</th>
                <th>Durum</th>
                <th>Boyut</th>
                <th>Tarih</th>
                <th>Başlatan</th>
                <th>Hata</th>
                <th>İşlem</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$backups): ?>
                <tr><td colspan="8">Henüz yedek kaydı bulunmuyor.</td></tr>
            <?php endif; ?>
            <?php foreach ($backups as $row): ?>
                <tr>
                    <td><?= e((string) $row['file_name']) ?></td>
                    <td><?= e((string) $row['backup_type']) ?></td>
                    <td><span class="<?= e(badge_status((string) $row['status'])) ?>"><?= e((string) $row['status']) ?></span></td>
                    <td><?= e($manager->humanFileSize((int) $row['file_size'])) ?></td>
                    <td><?= e(format_date((string) $row['created_at'])) ?></td>
                    <td><?= e((string) ($row['full_name'] ?? 'Sistem')) ?></td>
                    <td><?= e((string) mb_strimwidth((string) ($row['error_message'] ?? '-'), 0, 60, '...')) ?></td>
                    <td class="actions">
                        <a href="<?= e(app_url('admin/backup-detail.php?id=' . (int) $row['id'])) ?>">Detay</a>
                        <?php if ((string) $row['status'] === 'başarılı'): ?>
                            <a href="<?= e(app_url('actions/download-backup.php?id=' . (int) $row['id'])) ?>">İndir</a>
                        <?php endif; ?>
                        <form method="post" action="<?= e(app_url('actions/delete-backup.php')) ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
                            <button type="submit" class="btn-link danger" data-confirm="Yedek silinsin mi?">Sil</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
