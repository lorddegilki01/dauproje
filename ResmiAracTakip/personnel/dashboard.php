<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
$personnel = require_personnel_profile();

$pageTitle = 'Personel Paneli';
$personnelId = (int) $personnel['id'];

$activeAssignment = fetch_one(
    "SELECT va.*, v.plate_number, v.brand, v.model, v.vehicle_type, v.fuel_type, v.current_km, v.license_note
     FROM vehicle_assignments va
     INNER JOIN vehicles v ON v.id = va.vehicle_id
     WHERE va.personnel_id = :personnel_id AND va.return_status = 'iade edilmedi'
     ORDER BY va.assigned_at DESC
     LIMIT 1",
    ['personnel_id' => $personnelId]
);

$historyCount = count_value(
    'SELECT COUNT(*) FROM vehicle_assignments WHERE personnel_id = :personnel_id',
    ['personnel_id' => $personnelId]
);

$pendingRequestCount = count_value(
    "SELECT COUNT(*) FROM vehicle_requests WHERE personnel_id = :personnel_id AND status = 'bekliyor'",
    ['personnel_id' => $personnelId]
);

$issueOpenCount = count_value(
    "SELECT COUNT(*) FROM issue_reports WHERE personnel_id = :personnel_id AND status IN ('açık','inceleniyor','aÃ§Ä±k')",
    ['personnel_id' => $personnelId]
);

$latestFuel = fetch_one(
    "SELECT fl.purchase_date, fl.amount, fl.litre, fl.station_name, v.plate_number
     FROM fuel_logs fl
     INNER JOIN vehicles v ON v.id = fl.vehicle_id
     WHERE fl.personnel_id = :personnel_id
     ORDER BY fl.purchase_date DESC, fl.id DESC
     LIMIT 1",
    ['personnel_id' => $personnelId]
);

$latestIssue = fetch_one(
    "SELECT ir.subject, ir.status, ir.created_at, v.plate_number
     FROM issue_reports ir
     INNER JOIN vehicles v ON v.id = ir.vehicle_id
     WHERE ir.personnel_id = :personnel_id
     ORDER BY ir.created_at DESC
     LIMIT 1",
    ['personnel_id' => $personnelId]
);

$recentAssignments = fetch_all(
    "SELECT va.id, va.assigned_at, va.expected_return_at, va.returned_at, va.return_status, v.plate_number, v.brand, v.model
     FROM vehicle_assignments va
     INNER JOIN vehicles v ON v.id = va.vehicle_id
     WHERE va.personnel_id = :personnel_id
     ORDER BY va.assigned_at DESC
     LIMIT 6",
    ['personnel_id' => $personnelId]
);

$recentRequests = fetch_all(
    "SELECT vr.id, vr.request_date, vr.usage_purpose, vr.status, v.plate_number
     FROM vehicle_requests vr
     INNER JOIN vehicles v ON v.id = vr.vehicle_id
     WHERE vr.personnel_id = :personnel_id
     ORDER BY vr.request_date DESC
     LIMIT 6",
    ['personnel_id' => $personnelId]
);

$announcements = fetch_all(
    "SELECT title, content, created_at
     FROM announcements
     WHERE is_active = 1 AND target_role IN ('tümü', 'tÃ¼mÃ¼', 'personel')
     ORDER BY created_at DESC
     LIMIT 5"
);

$recentNotifications = notification_recent_list((int) current_user()['id'], 6);

$usageLabels = [];
$usageValues = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} day"));
    $usageLabels[] = date('d M', strtotime($day));
    $usageValues[] = count_value(
        "SELECT COUNT(*) FROM vehicle_assignments
         WHERE personnel_id = :personnel_id
           AND DATE(assigned_at) = :day",
        ['personnel_id' => $personnelId, 'day' => $day]
    );
}

$requestStatusRows = fetch_all(
    "SELECT status, COUNT(*) AS total
     FROM vehicle_requests
     WHERE personnel_id = :personnel_id
     GROUP BY status",
    ['personnel_id' => $personnelId]
);
$requestStatusMap = ['bekliyor' => 0, 'onaylandı' => 0, 'reddedildi' => 0];
foreach ($requestStatusRows as $row) {
    $status = (string) $row['status'];
    if ($status === 'onaylandÄ±') {
        $status = 'onaylandı';
    }
    $requestStatusMap[$status] = (int) $row['total'];
}

$fuelLabels = [];
$fuelValues = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-{$i} month"));
    $fuelLabels[] = date('M Y', strtotime($month . '-01'));
    $start = $month . '-01';
    $end = date('Y-m-t', strtotime($start));
    $fuelValues[] = round((float) sum_value(
        "SELECT SUM(amount) FROM fuel_logs
         WHERE personnel_id = :personnel_id
           AND purchase_date BETWEEN :start_date AND :end_date",
        ['personnel_id' => $personnelId, 'start_date' => $start, 'end_date' => $end]
    ), 2);
}

$personnelCharts = [
    'usage' => ['labels' => $usageLabels, 'values' => $usageValues],
    'requests' => [
        'labels' => ['Bekliyor', 'Onaylandı', 'Reddedildi'],
        'values' => [
            $requestStatusMap['bekliyor'] ?? 0,
            $requestStatusMap['onaylandı'] ?? 0,
            $requestStatusMap['reddedildi'] ?? 0,
        ],
    ],
    'fuel' => ['labels' => $fuelLabels, 'values' => $fuelValues],
];

include __DIR__ . '/../includes/header.php';
?>

<section class="stats-grid">
    <article class="stat-card stat-card-pro tone-blue">
        <div class="stat-pro-head">
            <span class="stat-pro-icon" aria-hidden="true">🚘</span>
            <h3>Aktif Araç</h3>
        </div>
        <strong><?= e($activeAssignment['plate_number'] ?? 'Yok') ?></strong>
        <div class="stat-pro-foot">
            <span class="stat-trend <?= $activeAssignment ? 'up' : 'flat' ?>">
                <?= e($activeAssignment ? 'Kullanımda' : 'Atama bekliyor') ?>
            </span>
            <svg class="stat-sparkline" viewBox="0 0 100 26" preserveAspectRatio="none" aria-hidden="true">
                <polyline points="0,20 25,18 50,16 75,12 100,8"></polyline>
            </svg>
        </div>
    </article>

    <article class="stat-card stat-card-pro tone-green">
        <div class="stat-pro-head">
            <span class="stat-pro-icon" aria-hidden="true">📚</span>
            <h3>Geçmiş Kullanım</h3>
        </div>
        <strong><?= e((string) $historyCount) ?></strong>
        <div class="stat-pro-foot">
            <span class="stat-trend up">Toplam kayıt</span>
            <svg class="stat-sparkline" viewBox="0 0 100 26" preserveAspectRatio="none" aria-hidden="true">
                <polyline points="0,22 20,20 40,18 60,15 80,12 100,10"></polyline>
            </svg>
        </div>
    </article>

    <article class="stat-card stat-card-pro tone-orange">
        <div class="stat-pro-head">
            <span class="stat-pro-icon" aria-hidden="true">📌</span>
            <h3>Bekleyen Talep</h3>
        </div>
        <strong><?= e((string) $pendingRequestCount) ?></strong>
        <div class="stat-pro-foot">
            <span class="stat-trend <?= $pendingRequestCount > 0 ? 'down' : 'up' ?>">
                <?= e($pendingRequestCount > 0 ? 'Onay bekliyor' : 'Bekleyen yok') ?>
            </span>
            <svg class="stat-sparkline" viewBox="0 0 100 26" preserveAspectRatio="none" aria-hidden="true">
                <polyline points="0,20 30,20 60,16 80,14 100,10"></polyline>
            </svg>
        </div>
    </article>

    <article class="stat-card stat-card-pro tone-red">
        <div class="stat-pro-head">
            <span class="stat-pro-icon" aria-hidden="true">🛠</span>
            <h3>Açık Arıza Bildirimi</h3>
        </div>
        <strong><?= e((string) $issueOpenCount) ?></strong>
        <div class="stat-pro-foot">
            <span class="stat-trend <?= $issueOpenCount > 0 ? 'down' : 'up' ?>">
                <?= e($issueOpenCount > 0 ? 'Takip gerekiyor' : 'Sorun yok') ?>
            </span>
            <svg class="stat-sparkline" viewBox="0 0 100 26" preserveAspectRatio="none" aria-hidden="true">
                <polyline points="0,18 25,17 50,16 75,14 100,11"></polyline>
            </svg>
        </div>
    </article>
</section>

<section class="dashboard-chart-grid">
    <article class="chart-card">
        <div class="panel-head"><h2>Kullanım Trendim (7 Gün)</h2></div>
        <div class="panel-body"><canvas id="personnel-chart-usage"></canvas></div>
    </article>
    <article class="chart-card">
        <div class="panel-head"><h2>Talep Durumu Dağılımı</h2></div>
        <div class="panel-body"><canvas id="personnel-chart-requests"></canvas></div>
    </article>
    <article class="chart-card">
        <div class="panel-head"><h2>Aylık Yakıt Giderim (6 Ay)</h2></div>
        <div class="panel-body"><canvas id="personnel-chart-fuel"></canvas></div>
    </article>
</section>

<section class="panel-grid">
    <article class="panel">
        <div class="panel-head"><h2>Aktif Kullanım Özeti</h2></div>
        <div class="panel-body info-list">
            <div><span>Profil Durumu</span><strong><?= e((string) $personnel['status']) ?></strong></div>
            <div><span>Departman / Görev</span><strong><?= e((string) $personnel['department'] . ' / ' . (string) $personnel['duty_title']) ?></strong></div>
            <div><span>Yaklaşan Teslim</span><strong><?= e(format_date($activeAssignment['expected_return_at'] ?? null, 'd.m.Y H:i')) ?></strong></div>
            <div><span>Son Yakıt Kaydı</span><strong><?= e($latestFuel ? format_money((float) $latestFuel['amount']) . ' • ' . $latestFuel['plate_number'] : '-') ?></strong></div>
            <div class="full-width"><span>Son Arıza Bildirimi</span><strong><?= e($latestIssue ? $latestIssue['plate_number'] . ' • ' . $latestIssue['subject'] : '-') ?></strong></div>
        </div>
    </article>

    <article class="panel">
        <div class="panel-head"><h2>Son Bildirimlerim</h2></div>
        <div class="panel-body">
            <?php if (!$recentNotifications): ?>
                <p class="empty-state">Bildirim bulunmuyor.</p>
            <?php else: ?>
                <ul class="alert-list">
                    <?php foreach ($recentNotifications as $item): ?>
                        <li class="<?= ((int) $item['is_read'] === 0) ? 'warning' : '' ?>">
                            <strong><?= e($item['title']) ?></strong><br>
                            <?= e($item['message']) ?><br>
                            <small><?= e($item['time_ago']) ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <div class="form-actions" style="margin-top:12px;">
                <a class="button ghost small" href="<?= e(app_url('notifications/index.php')) ?>">Tüm Bildirimleri Gör</a>
            </div>
        </div>
    </article>
</section>

<section class="panel-grid">
    <article class="panel">
        <div class="panel-head"><h2>Son Kullanım Kayıtlarım</h2></div>
        <div class="panel-body">
            <table class="table">
                <thead><tr><th>Araç</th><th>Başlangıç</th><th>Tahmini Teslim</th><th>Durum</th></tr></thead>
                <tbody>
                <?php foreach ($recentAssignments as $row): ?>
                    <tr>
                        <td><?= e($row['plate_number'] . ' - ' . $row['brand'] . ' ' . $row['model']) ?></td>
                        <td><?= e(format_date($row['assigned_at'], 'd.m.Y H:i')) ?></td>
                        <td><?= e(format_date($row['expected_return_at'], 'd.m.Y H:i')) ?></td>
                        <td><span class="<?= e(badge_class((string) $row['return_status'])) ?>"><?= e($row['return_status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="panel">
        <div class="panel-head"><h2>Talep Durumlarım</h2></div>
        <div class="panel-body">
            <table class="table">
                <thead><tr><th>Araç</th><th>Tarih</th><th>Amaç</th><th>Durum</th></tr></thead>
                <tbody>
                <?php foreach ($recentRequests as $request): ?>
                    <tr>
                        <td><?= e($request['plate_number']) ?></td>
                        <td><?= e(format_date($request['request_date'], 'd.m.Y H:i')) ?></td>
                        <td><?= e($request['usage_purpose']) ?></td>
                        <td><span class="<?= e(badge_class((string) $request['status'])) ?>"><?= e($request['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<section class="panel">
    <div class="panel-head"><h2>Duyurular ve Uyarılar</h2></div>
    <div class="panel-body">
        <?php if (!$announcements): ?>
            <p class="empty-state">Güncel duyuru bulunmuyor.</p>
        <?php else: ?>
            <ul class="alert-list">
                <?php foreach ($announcements as $announcement): ?>
                    <li class="warning">
                        <strong><?= e($announcement['title']) ?></strong><br>
                        <?= e($announcement['content']) ?><br>
                        <small><?= e(format_date($announcement['created_at'], 'd.m.Y H:i')) ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
window.personnelCharts = <?= json_encode($personnelCharts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
