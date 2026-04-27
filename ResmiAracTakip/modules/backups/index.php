<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
require_admin();

if (!backup_tables_ready() || !security_events_schema_ready()) {
    set_flash('error', 'Yedek/güvenlik modülü için SQL güncellemesi gerekiyor.');
    redirect('index.php');
}

$user = current_user();

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'manual_backup') {
        $result = run_database_backup((int) ($user['id'] ?? 0), 'manual');
        set_flash($result['ok'] ? 'success' : 'error', $result['message']);
        redirect('modules/backups/index.php');
    }

    if ($action === 'save_schedule') {
        $frequency = (string) ($_POST['frequency'] ?? 'daily');
        $isActive = ((string) ($_POST['is_active'] ?? '1')) === '1';
        update_backup_schedule($frequency, $isActive);
        set_flash('success', 'Yedekleme zamanlaması güncellendi.');
        redirect('modules/backups/index.php');
    }
}

$schedule = backup_schedule();
$backupLogs = fetch_all('SELECT * FROM backup_logs ORDER BY started_at DESC LIMIT 15');

$eventType = (string) ($_GET['event_type'] ?? '');
$status = (string) ($_GET['status'] ?? '');
$dateFrom = (string) ($_GET['date_from'] ?? '');
$dateTo = (string) ($_GET['date_to'] ?? '');

$sql = 'SELECT se.*, u.username
        FROM security_events se
        LEFT JOIN users u ON u.id = se.user_id
        WHERE 1=1';
$params = [];

if ($eventType !== '') {
    $sql .= ' AND se.event_type = :event_type';
    $params['event_type'] = $eventType;
}
if ($status !== '') {
    $sql .= ' AND se.status = :status';
    $params['status'] = $status;
}
if ($dateFrom !== '') {
    $sql .= ' AND se.created_at >= :date_from';
    $params['date_from'] = $dateFrom . ' 00:00:00';
}
if ($dateTo !== '') {
    $sql .= ' AND se.created_at <= :date_to';
    $params['date_to'] = $dateTo . ' 23:59:59';
}

$sql .= ' ORDER BY se.created_at DESC LIMIT 100';
$securityEvents = fetch_all($sql, $params);

$pageTitle = 'Yedek ve Güvenlik';
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="stats-grid">
    <article class="stat-card stat-card-pro stat-card-blue">
        <div class="stat-card-top"><span class="stat-card-icon">🗂️</span><h3>Son Yedek</h3></div>
        <strong><?= e($schedule['last_run_at'] ? format_date((string) $schedule['last_run_at'], 'd.m.Y H:i') : '-') ?></strong>
        <span><?= e((string) ($schedule['last_status'] ?? 'pending')) ?></span>
    </article>
    <article class="stat-card stat-card-pro stat-card-green">
        <div class="stat-card-top"><span class="stat-card-icon">⏭️</span><h3>Sonraki Yedek</h3></div>
        <strong><?= e($schedule['next_run_at'] ? format_date((string) $schedule['next_run_at'], 'd.m.Y H:i') : '-') ?></strong>
        <span><?= e((string) ($schedule['frequency'] ?? '-')) ?></span>
    </article>
    <article class="stat-card stat-card-pro stat-card-red">
        <div class="stat-card-top"><span class="stat-card-icon">⚠️</span><h3>Son Hata</h3></div>
        <strong><?= e((string) ($schedule['last_error'] ?: '-')) ?></strong>
        <span>Durum: <?= e((string) ($schedule['last_status'] ?? '-')) ?></span>
    </article>
</section>

<section class="panel">
    <div class="panel-head">
        <h2>Yedekleme Yönetimi</h2>
    </div>
    <div class="panel-body">
        <form method="post" class="form-grid" accept-charset="UTF-8">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save_schedule">

            <label>Otomatik Yedekleme Sıklığı
                <select name="frequency" required>
                    <option value="daily" <?= ($schedule['frequency'] ?? '') === 'daily' ? 'selected' : '' ?>>Günlük</option>
                    <option value="weekly" <?= ($schedule['frequency'] ?? '') === 'weekly' ? 'selected' : '' ?>>Haftalık</option>
                    <option value="monthly" <?= ($schedule['frequency'] ?? '') === 'monthly' ? 'selected' : '' ?>>Aylık</option>
                </select>
            </label>

            <label>Durum
                <select name="is_active">
                    <option value="1" <?= (int) ($schedule['is_active'] ?? 0) === 1 ? 'selected' : '' ?>>Aktif</option>
                    <option value="0" <?= (int) ($schedule['is_active'] ?? 0) === 0 ? 'selected' : '' ?>>Pasif</option>
                </select>
            </label>

            <div class="form-actions full-width">
                <button class="button primary" type="submit">Zamanlamayı Kaydet</button>
            </div>
        </form>

        <form method="post" accept-charset="UTF-8" style="margin-top:16px;">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="manual_backup">
            <button class="button secondary" type="submit">Manuel Yedek Al</button>
        </form>
    </div>
</section>

<section class="panel">
    <div class="panel-head">
        <h2>Yedek Geçmişi</h2>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Tür</th>
                    <th>Durum</th>
                    <th>Dosya</th>
                    <th>Hata</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($backupLogs as $log): ?>
                    <tr>
                        <td><?= e(format_date((string) $log['started_at'], 'd.m.Y H:i')) ?></td>
                        <td><?= e((string) $log['run_type']) ?></td>
                        <td><span class="<?= e(badge_class((string) $log['status'])) ?>"><?= e((string) $log['status']) ?></span></td>
                        <td><?= e((string) ($log['backup_file'] ?? '-')) ?></td>
                        <td><?= e((string) ($log['error_message'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="panel">
    <div class="panel-head">
        <h2>Şifre Güvenlik Kayıtları</h2>
    </div>
    <div class="panel-body">
        <form method="get" class="filter-bar" accept-charset="UTF-8">
            <label>Olay
                <select name="event_type">
                    <option value="">Tümü</option>
                    <option value="password_reset_requested" <?= $eventType === 'password_reset_requested' ? 'selected' : '' ?>>Sıfırlama Talebi</option>
                    <option value="password_reset_sent" <?= $eventType === 'password_reset_sent' ? 'selected' : '' ?>>E-posta Gönderildi</option>
                    <option value="password_reset_failed" <?= $eventType === 'password_reset_failed' ? 'selected' : '' ?>>Gönderim Hatası</option>
                    <option value="password_changed" <?= $eventType === 'password_changed' ? 'selected' : '' ?>>Şifre Değişti</option>
                </select>
            </label>
            <label>Durum
                <select name="status">
                    <option value="">Tümü</option>
                    <option value="success" <?= $status === 'success' ? 'selected' : '' ?>>Başarılı</option>
                    <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Hatalı</option>
                    <option value="blocked" <?= $status === 'blocked' ? 'selected' : '' ?>>Engellendi</option>
                </select>
            </label>
            <label>Başlangıç
                <input type="date" name="date_from" value="<?= e($dateFrom) ?>">
            </label>
            <label>Bitiş
                <input type="date" name="date_to" value="<?= e($dateTo) ?>">
            </label>
            <button class="button primary" type="submit">Filtrele</button>
        </form>

        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Olay</th>
                    <th>Durum</th>
                    <th>Kullanıcı</th>
                    <th>E-posta</th>
                    <th>IP</th>
                    <th>Ayrıntı</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$securityEvents): ?>
                    <tr><td colspan="7" class="empty-state">Kayıt bulunamadı.</td></tr>
                <?php endif; ?>
                <?php foreach ($securityEvents as $event): ?>
                    <tr>
                        <td><?= e(format_date((string) $event['created_at'], 'd.m.Y H:i')) ?></td>
                        <td><?= e((string) $event['event_type']) ?></td>
                        <td><span class="<?= e(badge_class((string) $event['status'])) ?>"><?= e((string) $event['status']) ?></span></td>
                        <td><?= e((string) ($event['username'] ?? '-')) ?></td>
                        <td><?= e((string) ($event['email'] ?? '-')) ?></td>
                        <td><?= e((string) ($event['ip_address'] ?? '-')) ?></td>
                        <td><?= e((string) ($event['details'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
