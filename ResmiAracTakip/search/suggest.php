<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_login();

header('Content-Type: application/json; charset=UTF-8');

$query = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($query, 'UTF-8') < 2) {
    echo json_encode(['ok' => true, 'items' => []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$like = '%' . $query . '%';
$items = [];
$personnelProfile = null;

if (is_admin()) {
    $vehicles = fetch_all(
        'SELECT id, plate_number, brand, model, status
         FROM vehicles
         WHERE plate_number LIKE :q_plate OR brand LIKE :q_brand OR model LIKE :q_model
         ORDER BY plate_number
         LIMIT 5',
        ['q_plate' => $like, 'q_brand' => $like, 'q_model' => $like]
    );
    foreach ($vehicles as $row) {
        $items[] = [
            'group' => 'Araçlar',
            'title' => (string) $row['plate_number'],
            'subtitle' => trim((string) $row['brand'] . ' ' . (string) $row['model']),
            'meta' => (string) $row['status'],
            'url' => app_url('modules/vehicles/view.php?id=' . (int) $row['id']),
        ];
    }

    $personnel = fetch_all(
        'SELECT id, full_name, registration_no, department
         FROM personnel
         WHERE full_name LIKE :q_name OR registration_no LIKE :q_reg OR department LIKE :q_dept
         ORDER BY full_name
         LIMIT 5',
        ['q_name' => $like, 'q_reg' => $like, 'q_dept' => $like]
    );
    foreach ($personnel as $row) {
        $items[] = [
            'group' => 'Personel',
            'title' => (string) $row['full_name'],
            'subtitle' => 'Sicil: ' . (string) $row['registration_no'],
            'meta' => (string) $row['department'],
            'url' => app_url('modules/personnel/view.php?id=' . (int) $row['id']),
        ];
    }
} else {
    $personnelProfile = require_personnel_profile();
    $vehicles = fetch_all(
        "SELECT v.id, v.plate_number, v.brand, v.model, va.return_status
         FROM vehicle_assignments va
         INNER JOIN vehicles v ON v.id = va.vehicle_id
         WHERE va.personnel_id = :personnel_id
           AND (v.plate_number LIKE :q_plate OR v.brand LIKE :q_brand OR v.model LIKE :q_model)
         GROUP BY v.id
         ORDER BY MAX(va.assigned_at) DESC
         LIMIT 5",
        [
            'personnel_id' => (int) $personnelProfile['id'],
            'q_plate' => $like,
            'q_brand' => $like,
            'q_model' => $like,
        ]
    );
    foreach ($vehicles as $row) {
        $items[] = [
            'group' => 'Araçlarım',
            'title' => (string) $row['plate_number'],
            'subtitle' => trim((string) $row['brand'] . ' ' . (string) $row['model']),
            'meta' => (string) $row['return_status'],
            'url' => app_url('personnel/vehicles.php'),
        ];
    }
}

$requests = is_admin()
    ? fetch_all(
        "SELECT vr.id, vr.status, vr.usage_purpose, p.full_name, v.plate_number
         FROM vehicle_requests vr
         INNER JOIN personnel p ON p.id = vr.personnel_id
         INNER JOIN vehicles v ON v.id = vr.vehicle_id
         WHERE p.full_name LIKE :q_person OR v.plate_number LIKE :q_plate OR vr.usage_purpose LIKE :q_purpose
         ORDER BY vr.request_date DESC
         LIMIT 5",
        ['q_person' => $like, 'q_plate' => $like, 'q_purpose' => $like]
    )
    : fetch_all(
        "SELECT vr.id, vr.status, vr.usage_purpose, v.plate_number
         FROM vehicle_requests vr
         INNER JOIN vehicles v ON v.id = vr.vehicle_id
         WHERE vr.personnel_id = :personnel_id
           AND (v.plate_number LIKE :q_plate OR vr.usage_purpose LIKE :q_purpose)
         ORDER BY vr.request_date DESC
         LIMIT 5",
        [
            'personnel_id' => (int) ($personnelProfile['id'] ?? require_personnel_profile()['id']),
            'q_plate' => $like,
            'q_purpose' => $like,
        ]
    );

foreach ($requests as $row) {
    $items[] = [
        'group' => 'Talepler',
        'title' => (string) $row['plate_number'],
        'subtitle' => (string) $row['usage_purpose'],
        'meta' => (string) $row['status'] . (is_admin() ? ' • ' . (string) $row['full_name'] : ''),
        'url' => app_url(is_admin() ? 'modules/requests/index.php' : 'personnel/requests.php'),
    ];
}

$issues = is_admin()
    ? fetch_all(
        "SELECT ir.id, ir.subject, ir.status, v.plate_number, p.full_name
         FROM issue_reports ir
         INNER JOIN vehicles v ON v.id = ir.vehicle_id
         INNER JOIN personnel p ON p.id = ir.personnel_id
         WHERE ir.subject LIKE :q_subject OR ir.description LIKE :q_desc OR v.plate_number LIKE :q_plate OR p.full_name LIKE :q_person
         ORDER BY ir.created_at DESC
         LIMIT 5",
        ['q_subject' => $like, 'q_desc' => $like, 'q_plate' => $like, 'q_person' => $like]
    )
    : fetch_all(
        "SELECT ir.id, ir.subject, ir.status, v.plate_number
         FROM issue_reports ir
         INNER JOIN vehicles v ON v.id = ir.vehicle_id
         WHERE ir.personnel_id = :personnel_id
           AND (ir.subject LIKE :q_subject OR ir.description LIKE :q_desc OR v.plate_number LIKE :q_plate)
         ORDER BY ir.created_at DESC
         LIMIT 5",
        [
            'personnel_id' => (int) ($personnelProfile['id'] ?? require_personnel_profile()['id']),
            'q_subject' => $like,
            'q_desc' => $like,
            'q_plate' => $like,
        ]
    );

foreach ($issues as $row) {
    $items[] = [
        'group' => 'Arıza Kayıtları',
        'title' => (string) $row['plate_number'],
        'subtitle' => (string) $row['subject'],
        'meta' => (string) $row['status'] . (is_admin() ? ' • ' . (string) $row['full_name'] : ''),
        'url' => app_url(is_admin() ? 'modules/issues/index.php' : 'personnel/issues.php'),
    ];
}

echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
