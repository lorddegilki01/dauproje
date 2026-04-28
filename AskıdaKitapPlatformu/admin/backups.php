<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/BackupManager.php';
require_admin();

$activeMenu = 'backups';
$pageTitle = 'Yedekleme Yönetimi';

$manager = new BackupManager();
$settings = $manager->getSettings();

$summary = fetch_one(
    'SELECT
        COUNT(*) AS total_count,
        COALESCE(SUM(file_size), 0) AS total_size,
        MAX(CASE WHEN status = "basarili" THEN created_at END) AS last_success,
        MAX(CASE WHEN status = "basarisiz" THEN created_at END) AS last_failed
     FROM backups'
) ?? ['total_count' => 0, 'total_size' => 0, 'last_success' => null, 'last_failed' => null];

$backups = fetch_all(
    'SELECT b.*, u.username
     FROM backups b
     LEFT JOIN users u ON u.id = b.started_by
     ORDER BY b.created_at DESC
     LIMIT 200'
);

require __DIR__ . '/../includes/header.php';
?>
<section class="stats-grid">
    <article class="card stat"><h3>Otomatik Yedekleme</h3><strong><?= (int) $settings['automatic_enabled'] === 1 ? 'Aktif' : 'Pasif' ?></strong></article>
    <article class="card stat"><h3>Son Başarılı</h3><strong><?= e(format_date($summary['last_success'] ?? null)) ?></strong></article>
    <article class="card stat"><h3>Son Hata</h3><strong><?= e(format_date($summary['last_failed'] ?? null)) ?></strong></article>
    <article class="card stat"><h3>Toplam Yedek</h3><strong><?= e((string) (int) $summary['total_count']) ?></strong></article>
</section>

<section class="card">
    <div class="card-head">
        <h2>Yedekleme Kontrolü</h2>
        <form method="post" action="<?= e(app_url('actions/create-backup.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <button class="btn primary" type="submit">Manuel Yedek Al</button>
        </form>
    </div>
    <div class="list">
        <div>Sonraki Planlanan Çalışma: <strong><?= e(format_date((string) ($settings['next_run_at'] ?? ''), 'd.m.Y H:i')) ?></strong></div>
        <div>Toplam Yedek Boyutu: <strong><?= e(number_format(((int) $summary['total_size']) / (1024 * 1024), 2, ',', '.')) ?> MB</strong></div>
        <div>Son Hata Bilgisi: <strong><?= e((string) ($settings['last_error'] ?: 'Yok')) ?></strong></div>
    </div>
</section>

<section class="card">
    <h2>Otomatik Yedekleme Ayarları</h2>
    <form class="form grid-4" method="post" action="<?= e(app_url('actions/update-backup-settings.php')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label class="inline-check">
            <input type="checkbox" name="automatic_enabled" value="1" <?= (int) $settings['automatic_enabled'] === 1 ? 'checked' : '' ?>>
            Otomatik Yedekleme Aktif
        </label>
        <label>Sıklık
            <select name="frequency">
                <option value="gunluk" <?= $settings['frequency'] === 'gunluk' ? 'selected' : '' ?>>Günlük</option>
                <option value="haftalik" <?= $settings['frequency'] === 'haftalik' ? 'selected' : '' ?>>Haftalık</option>
                <option value="aylik" <?= $settings['frequency'] === 'aylik' ? 'selected' : '' ?>>Aylık</option>
            </select>
        </label>
        <label>Yedek Saati
            <input type="time" name="backup_time" value="<?= e(substr((string) $settings['backup_time'], 0, 5)) ?>">
        </label>
        <label>Maksimum Yedek Sayısı
            <input type="number" min="3" max="500" name="max_backup_count" value="<?= e((string) (int) $settings['max_backup_count']) ?>">
        </label>
        <label class="full">Yedek Klasörü
            <input type="text" name="backup_path" value="<?= e((string) $settings['backup_path']) ?>">
        </label>
        <label class="inline-check">
            <input type="checkbox" name="auto_cleanup_enabled" value="1" <?= (int) $settings['auto_cleanup_enabled'] === 1 ? 'checked' : '' ?>>
            Eski Yedekleri Otomatik Temizle
        </label>
        <div class="full actions"><button class="btn primary" type="submit">Ayarları Kaydet</button></div>
    </form>
</section>

<section class="card">
    <h2>Yedek Geçmişi</h2>
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
                <th>İşlem</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$backups): ?><tr><td colspan="7">Kayıt bulunmuyor.</td></tr><?php endif; ?>
            <?php foreach ($backups as $row): ?>
                <tr>
                    <td><?= e((string) $row['file_name']) ?></td>
                    <td><?= e((string) $row['backup_type']) ?></td>
                    <td><span class="<?= e((string) $row['status'] === 'basarili' ? 'badge success' : 'badge danger') ?>"><?= e((string) $row['status']) ?></span></td>
                    <td><?= e(number_format(((int) $row['file_size']) / 1024, 1, ',', '.')) ?> KB</td>
                    <td><?= e(format_date((string) $row['created_at'])) ?></td>
                    <td><?= e((string) ($row['username'] ?? 'Sistem')) ?></td>
                    <td class="table-actions">
                        <a class="btn ghost" href="<?= e(app_url('actions/download-backup.php?id=' . (int) $row['id'])) ?>">İndir</a>
                        <form method="post" action="<?= e(app_url('actions/delete-backup.php')) ?>" onsubmit="return confirm('Yedek silinsin mi?')">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= e((string) (int) $row['id']) ?>">
                            <button class="btn ghost danger-text" type="submit">Sil</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
