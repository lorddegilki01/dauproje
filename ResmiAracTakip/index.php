<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_admin();

$pageTitle = 'Yönetim Dashboard';

$activeAt = static function (string $datetime): int {
    return count_value(
        "SELECT COUNT(*)
         FROM vehicle_assignments
         WHERE assigned_at <= :dt_start
           AND (returned_at IS NULL OR returned_at > :dt_end)",
        [
            'dt_start' => $datetime,
            'dt_end' => $datetime,
        ]
    );
};

$maintenanceAt = static function (string $date): int {
    return count_value(
        "SELECT COUNT(*)
         FROM maintenance_records
         WHERE next_maintenance_date < :d",
        ['d' => $date]
    );
};

$totalAt = static function (string $datetime): int {
    return count_value(
        "SELECT COUNT(*)
         FROM vehicles
         WHERE created_at <= :dt",
        ['dt' => $datetime]
    );
};

$toSparklinePoints = static function (array $series): string {
    $series = array_values(array_map(static fn($v) => (float) $v, $series));
    $count = count($series);
    if ($count === 0) {
        return '0,20 100,20';
    }
    if ($count === 1) {
        return '0,12 100,12';
    }

    $min = min($series);
    $max = max($series);
    $range = max(1.0, $max - $min);

    $points = [];
    foreach ($series as $i => $value) {
        $x = (int) round(($i / ($count - 1)) * 100);
        $normalized = ($value - $min) / $range;
        $y = (int) round(22 - ($normalized * 16));
        $points[] = $x . ',' . $y;
    }

    return implode(' ', $points);
};

$currentDate = date('Y-m-d');
$now = date('Y-m-d H:i:s');
$prevMonthStart = date('Y-m-01 00:00:00', strtotime('-1 month'));
$prevMonthEnd = date('Y-m-t 23:59:59', strtotime('-1 month'));
$currentMonthStart = date('Y-m-01 00:00:00');

$stats = [
    'total_vehicles' => count_value('SELECT COUNT(*) FROM vehicles'),
    'active_vehicles' => count_value("SELECT COUNT(*) FROM vehicles WHERE status = 'kullanımda'"),
    'available_vehicles' => count_value("SELECT COUNT(*) FROM vehicles WHERE status = 'müsait'"),
    'maintenance_due' => count_value("SELECT COUNT(*) FROM maintenance_records WHERE next_maintenance_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)"),
    'today_fuel_count' => count_value('SELECT COUNT(*) FROM fuel_logs WHERE purchase_date = CURDATE()'),
    'pending_requests' => count_value("SELECT COUNT(*) FROM vehicle_requests WHERE status = 'bekliyor'"),
    'open_issues' => count_value("SELECT COUNT(*) FROM issue_reports WHERE status IN ('açık', 'inceleniyor')"),
];

$prevTotal = $totalAt($prevMonthEnd);
$prevActive = $activeAt($prevMonthEnd);
$prevMaintenance = $maintenanceAt(date('Y-m-d', strtotime($prevMonthEnd)));
$prevAvailable = max(0, $prevTotal - $prevActive - $prevMaintenance);
$prevMaintenanceDue = count_value(
    "SELECT COUNT(*)
     FROM maintenance_records
     WHERE next_maintenance_date BETWEEN :start_date_from AND DATE_ADD(:start_date_to, INTERVAL 30 DAY)",
    [
        'start_date_from' => date('Y-m-d', strtotime($prevMonthEnd)),
        'start_date_to' => date('Y-m-d', strtotime($prevMonthEnd)),
    ]
);

$formatTrend = static function (int $current, int $previous): array {
    $diff = $current - $previous;
    if ($diff > 0) {
        return ['text' => '+' . $diff . ' bu ay', 'class' => 'up'];
    }
    if ($diff < 0) {
        return ['text' => $diff . ' bu ay', 'class' => 'down'];
    }
    return ['text' => 'değişmedi', 'class' => 'flat'];
};

$trendTotal = $formatTrend($stats['total_vehicles'], $prevTotal);
$trendActive = $formatTrend($stats['active_vehicles'], $prevActive);
$trendAvailable = $formatTrend($stats['available_vehicles'], $prevAvailable);
$trendMaintenance = $formatTrend($stats['maintenance_due'], $prevMaintenanceDue);

$days = [];
$seriesTotal = [];
$seriesActive = [];
$seriesAvailable = [];
$seriesMaintenance = [];
$usageLabels = [];
$usageActive = [];
$usageAvailable = [];
$usageMaintenance = [];

for ($i = 7; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} day"));
    $dayEnd = $day . ' 23:59:59';

    $totalCount = $totalAt($dayEnd);
    $activeCount = $activeAt($dayEnd);
    $maintenanceCount = $maintenanceAt($day);
    $availableCount = max(0, $totalCount - $activeCount - $maintenanceCount);

    $days[] = $day;
    $seriesTotal[] = $totalCount;
    $seriesActive[] = $activeCount;
    $seriesMaintenance[] = $maintenanceCount;
    $seriesAvailable[] = $availableCount;

    if ($i <= 6) {
        $usageLabels[] = date('d M', strtotime($day));
        $usageActive[] = $activeCount;
        $usageAvailable[] = $availableCount;
        $usageMaintenance[] = $maintenanceCount;
    }
}

$cards = [
    [
        'title' => 'Toplam Araç',
        'value' => $stats['total_vehicles'],
        'tone' => 'blue',
        'icon' => '🚘',
        'trend' => $trendTotal['text'],
        'trend_class' => $trendTotal['class'],
        'sparkline' => $toSparklinePoints($seriesTotal),
    ],
    [
        'title' => 'Aktif Kullanılan',
        'value' => $stats['active_vehicles'],
        'tone' => 'green',
        'icon' => '🚙',
        'trend' => $trendActive['text'],
        'trend_class' => $trendActive['class'],
        'sparkline' => $toSparklinePoints($seriesActive),
    ],
    [
        'title' => 'Müsait Araç',
        'value' => $stats['available_vehicles'],
        'tone' => 'orange',
        'icon' => '🚗',
        'trend' => $trendAvailable['text'],
        'trend_class' => $trendAvailable['class'],
        'sparkline' => $toSparklinePoints($seriesAvailable),
    ],
    [
        'title' => 'Bakıma Yaklaşan',
        'value' => $stats['maintenance_due'],
        'tone' => 'red',
        'icon' => '🛠',
        'trend' => $trendMaintenance['text'],
        'trend_class' => $trendMaintenance['class'],
        'sparkline' => $toSparklinePoints($seriesMaintenance),
    ],
];

$recentUsage = fetch_all(
    "SELECT va.id, va.assigned_at, va.expected_return_at, va.return_status, v.plate_number, p.full_name
     FROM vehicle_assignments va
     INNER JOIN vehicles v ON v.id = va.vehicle_id
     INNER JOIN personnel p ON p.id = va.personnel_id
     ORDER BY va.assigned_at DESC
     LIMIT 8"
);

$pendingRequests = fetch_all(
    "SELECT vr.id, vr.request_date, vr.planned_start_at, vr.planned_end_at, vr.usage_purpose, p.full_name, v.plate_number
     FROM vehicle_requests vr
     INNER JOIN personnel p ON p.id = vr.personnel_id
     INNER JOIN vehicles v ON v.id = vr.vehicle_id
     WHERE vr.status = 'bekliyor'
     ORDER BY vr.request_date DESC
     LIMIT 8"
);

$notifications = fetch_all(
    "SELECT 'Bakım' AS type, v.plate_number AS target, mr.next_maintenance_date AS date_value,
            CASE WHEN mr.next_maintenance_date < CURDATE() THEN 'gecikti' ELSE 'yaklaşıyor' END AS status_value
     FROM maintenance_records mr
     INNER JOIN vehicles v ON v.id = mr.vehicle_id
     WHERE mr.next_maintenance_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
     UNION ALL
     SELECT 'Geciken Teslim', v.plate_number, DATE(va.expected_return_at), 'gecikti'
     FROM vehicle_assignments va
     INNER JOIN vehicles v ON v.id = va.vehicle_id
     WHERE va.return_status = 'iade edilmedi'
       AND va.expected_return_at IS NOT NULL
       AND va.expected_return_at < NOW()
     ORDER BY date_value ASC
     LIMIT 12"
);

$statusRows = fetch_all('SELECT status, COUNT(*) AS total FROM vehicles GROUP BY status');
$statusMap = ['kullanımda' => 0, 'müsait' => 0, 'bakımda' => 0, 'pasif' => 0];
foreach ($statusRows as $row) {
    $statusMap[(string) $row['status']] = (int) $row['total'];
}

$fuelLabels = [];
$fuelValues = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-{$i} month"));
    $fuelLabels[] = date('M Y', strtotime($month . '-01'));
    $monthStart = $month . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    $fuelValues[] = sum_value(
        'SELECT SUM(amount) FROM fuel_logs WHERE purchase_date BETWEEN :month_start AND :month_end',
        [
            'month_start' => $monthStart,
            'month_end' => $monthEnd,
        ]
    );
}

$dashboardCharts = [
    'usage' => [
        'labels' => $usageLabels,
        'active' => $usageActive,
        'available' => $usageAvailable,
        'maintenance' => $usageMaintenance,
    ],
    'status' => [
        'labels' => ['Kullanımda', 'Müsait', 'Bakımda', 'Pasif'],
        'values' => [$statusMap['kullanımda'], $statusMap['müsait'], $statusMap['bakımda'], $statusMap['pasif']],
    ],
    'fuel' => [
        'labels' => $fuelLabels,
        'values' => array_map(static fn($v) => round((float) $v, 2), $fuelValues),
    ],
];

include __DIR__ . '/includes/header.php';
?>

<section class="stats-grid">
    <?php foreach ($cards as $card): ?>
        <article class="stat-card stat-card-pro tone-<?= e($card['tone']) ?>">
            <div class="stat-pro-head">
                <span class="stat-pro-icon" aria-hidden="true"><?= e($card['icon']) ?></span>
                <h3><?= e($card['title']) ?></h3>
            </div>
            <strong><?= e((string) $card['value']) ?></strong>
            <div class="stat-pro-foot">
                <span class="stat-trend <?= e($card['trend_class']) ?>"><?= e($card['trend']) ?></span>
                <svg class="stat-sparkline" viewBox="0 0 100 26" preserveAspectRatio="none" aria-hidden="true">
                    <polyline points="<?= e($card['sparkline']) ?>"></polyline>
                </svg>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<section class="dashboard-chart-grid">
    <article class="chart-card">
        <div class="panel-head"><h2>Araç Kullanım Trendi (7 Gün)</h2></div>
        <div class="panel-body"><canvas id="chart-usage"></canvas></div>
    </article>
    <article class="chart-card">
        <div class="panel-head"><h2>Durum Dağılımı</h2></div>
        <div class="panel-body"><canvas id="chart-status"></canvas></div>
    </article>
    <article class="chart-card">
        <div class="panel-head"><h2>Aylık Yakıt Gideri (6 Ay)</h2></div>
        <div class="panel-body"><canvas id="chart-fuel"></canvas></div>
    </article>
</section>

<section class="panel-grid">
    <article class="panel">
        <div class="panel-head"><h2>Son Kullanım Kayıtları</h2></div>
        <div class="panel-body">
            <table class="table">
                <thead><tr><th>Araç</th><th>Personel</th><th>Başlangıç</th><th>Tahmini Teslim</th><th>Durum</th></tr></thead>
                <tbody>
                <?php foreach ($recentUsage as $row): ?>
                    <tr>
                        <td><?= e($row['plate_number']) ?></td>
                        <td><?= e($row['full_name']) ?></td>
                        <td><?= e(format_date($row['assigned_at'], 'd.m.Y H:i')) ?></td>
                        <td><?= e(format_date($row['expected_return_at'], 'd.m.Y H:i')) ?></td>
                        <td><span class="<?= e(badge_class($row['return_status'])) ?>"><?= e($row['return_status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="panel">
        <div class="panel-head"><h2>Bekleyen Araç Talepleri</h2></div>
        <div class="panel-body">
            <table class="table">
                <thead><tr><th>Personel</th><th>Araç</th><th>Talep</th><th>Kullanım Aralığı</th><th>İşlem</th></tr></thead>
                <tbody>
                <?php foreach ($pendingRequests as $row): ?>
                    <tr>
                        <td><?= e($row['full_name']) ?></td>
                        <td><?= e($row['plate_number']) ?></td>
                        <td><?= e(format_date($row['request_date'], 'd.m.Y H:i')) ?></td>
                        <td><?= e(format_date($row['planned_start_at'], 'd.m.Y H:i')) ?> - <?= e(format_date($row['planned_end_at'], 'd.m.Y H:i')) ?></td>
                        <td><a href="<?= e(app_url('modules/requests/index.php')) ?>">Yönet</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<section class="panel">
    <div class="panel-head"><h2>Kritik Uyarılar</h2></div>
    <div class="panel-body">
        <?php if (!$notifications): ?>
            <p class="empty-state">Aktif kritik bildirim bulunmuyor.</p>
        <?php else: ?>
            <ul class="alert-list">
                <?php foreach ($notifications as $notification): ?>
                    <li class="<?= $notification['status_value'] === 'gecikti' ? 'danger' : 'warning' ?>">
                        <strong><?= e($notification['type']) ?>:</strong>
                        <?= e($notification['target']) ?> - <?= e(format_date($notification['date_value'])) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
window.dashboardCharts = <?= json_encode($dashboardCharts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
