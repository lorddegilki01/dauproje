<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_login();

$pageTitle = 'Genel Arama';
$query = trim((string) ($_GET['q'] ?? ''));
$hasQuery = $query !== '';

$results = [
    'vehicles' => [],
    'personnel' => [],
    'requests' => [],
    'issues' => [],
];

if ($hasQuery) {
    $like = '%' . $query . '%';

    if (is_admin()) {
        $results['vehicles'] = fetch_all(
            'SELECT id, plate_number, brand, model, status
             FROM vehicles
             WHERE plate_number LIKE :q_plate OR brand LIKE :q_brand OR model LIKE :q_model
             ORDER BY plate_number
             LIMIT 10',
            [
                'q_plate' => $like,
                'q_brand' => $like,
                'q_model' => $like,
            ]
        );

        $results['personnel'] = fetch_all(
            'SELECT id, full_name, registration_no, department, duty_title
             FROM personnel
             WHERE full_name LIKE :q_name OR registration_no LIKE :q_reg OR department LIKE :q_department
             ORDER BY full_name
             LIMIT 10',
            [
                'q_name' => $like,
                'q_reg' => $like,
                'q_department' => $like,
            ]
        );

        $results['requests'] = fetch_all(
            "SELECT vr.id, vr.status, vr.usage_purpose, p.full_name, v.plate_number
             FROM vehicle_requests vr
             INNER JOIN personnel p ON p.id = vr.personnel_id
             INNER JOIN vehicles v ON v.id = vr.vehicle_id
             WHERE p.full_name LIKE :q_person OR v.plate_number LIKE :q_plate OR vr.usage_purpose LIKE :q_purpose
             ORDER BY vr.request_date DESC
             LIMIT 10",
            [
                'q_person' => $like,
                'q_plate' => $like,
                'q_purpose' => $like,
            ]
        );

        $results['issues'] = fetch_all(
            "SELECT ir.id, ir.subject, ir.status, v.plate_number, p.full_name
             FROM issue_reports ir
             INNER JOIN vehicles v ON v.id = ir.vehicle_id
             INNER JOIN personnel p ON p.id = ir.personnel_id
             WHERE ir.subject LIKE :q_subject OR ir.description LIKE :q_description OR v.plate_number LIKE :q_plate OR p.full_name LIKE :q_person
             ORDER BY ir.created_at DESC
             LIMIT 10",
            [
                'q_subject' => $like,
                'q_description' => $like,
                'q_plate' => $like,
                'q_person' => $like,
            ]
        );
    } else {
        $personnel = require_personnel_profile();

        $results['vehicles'] = fetch_all(
            "SELECT v.id, v.plate_number, v.brand, v.model, v.status
             FROM vehicle_assignments va
             INNER JOIN vehicles v ON v.id = va.vehicle_id
             WHERE va.personnel_id = :personnel_id
               AND (v.plate_number LIKE :q_plate OR v.brand LIKE :q_brand OR v.model LIKE :q_model)
             GROUP BY v.id
             ORDER BY MAX(va.assigned_at) DESC
             LIMIT 10",
            [
                'personnel_id' => (int) $personnel['id'],
                'q_plate' => $like,
                'q_brand' => $like,
                'q_model' => $like,
            ]
        );

        $results['requests'] = fetch_all(
            "SELECT vr.id, vr.status, vr.usage_purpose, v.plate_number
             FROM vehicle_requests vr
             INNER JOIN vehicles v ON v.id = vr.vehicle_id
             WHERE vr.personnel_id = :personnel_id
               AND (v.plate_number LIKE :q_plate OR vr.usage_purpose LIKE :q_purpose)
             ORDER BY vr.request_date DESC
             LIMIT 10",
            [
                'personnel_id' => (int) $personnel['id'],
                'q_plate' => $like,
                'q_purpose' => $like,
            ]
        );

        $results['issues'] = fetch_all(
            "SELECT ir.id, ir.subject, ir.status, v.plate_number
             FROM issue_reports ir
             INNER JOIN vehicles v ON v.id = ir.vehicle_id
             WHERE ir.personnel_id = :personnel_id
               AND (ir.subject LIKE :q_subject OR ir.description LIKE :q_description OR v.plate_number LIKE :q_plate)
             ORDER BY ir.created_at DESC
             LIMIT 10",
            [
                'personnel_id' => (int) $personnel['id'],
                'q_subject' => $like,
                'q_description' => $like,
                'q_plate' => $like,
            ]
        );
    }
}

include __DIR__ . '/../includes/header.php';
?>

<section class="panel">
    <div class="panel-head">
        <h2>Genel Arama</h2>
    </div>
    <div class="panel-body">
        <form class="filter-bar" method="get">
            <label class="full-width">Aranacak metin
                <input type="search" name="q" value="<?= e($query) ?>" placeholder="Plaka, kişi, talep, arıza konusu...">
            </label>
            <button class="button primary" type="submit">Ara</button>
        </form>
    </div>
</section>

<?php if ($hasQuery): ?>
    <section class="panel">
        <div class="panel-head"><h2>Araç Sonuçları</h2></div>
        <div class="panel-body">
            <?php if (!$results['vehicles']): ?>
                <p class="empty-state">Araç sonucu bulunamadı.</p>
            <?php else: ?>
                <table class="table">
                    <thead><tr><th>Plaka</th><th>Araç</th><th>Durum</th><th>İşlem</th></tr></thead>
                    <tbody>
                    <?php foreach ($results['vehicles'] as $row): ?>
                        <tr>
                            <td><?= e($row['plate_number']) ?></td>
                            <td><?= e($row['brand'] . ' ' . $row['model']) ?></td>
                            <td><span class="<?= e(badge_class((string) $row['status'])) ?>"><?= e($row['status']) ?></span></td>
                            <td><a href="<?= e(app_url('modules/vehicles/view.php?id=' . $row['id'])) ?>">Detay</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </section>

    <?php if (is_admin()): ?>
        <section class="panel">
            <div class="panel-head"><h2>Personel Sonuçları</h2></div>
            <div class="panel-body">
                <?php if (!$results['personnel']): ?>
                    <p class="empty-state">Personel sonucu bulunamadı.</p>
                <?php else: ?>
                    <table class="table">
                        <thead><tr><th>Ad Soyad</th><th>Sicil No</th><th>Departman</th><th>Görev</th><th>İşlem</th></tr></thead>
                        <tbody>
                        <?php foreach ($results['personnel'] as $row): ?>
                            <tr>
                                <td><?= e($row['full_name']) ?></td>
                                <td><?= e($row['registration_no']) ?></td>
                                <td><?= e($row['department']) ?></td>
                                <td><?= e($row['duty_title']) ?></td>
                                <td><a href="<?= e(app_url('modules/personnel/view.php?id=' . $row['id'])) ?>">Detay</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="panel">
        <div class="panel-head"><h2>Araç Talepleri</h2></div>
        <div class="panel-body">
            <?php if (!$results['requests']): ?>
                <p class="empty-state">Talep sonucu bulunamadı.</p>
            <?php else: ?>
                <table class="table">
                    <thead><tr><th>Araç</th><?php if (is_admin()): ?><th>Personel</th><?php endif; ?><th>Amaç</th><th>Durum</th><th>İşlem</th></tr></thead>
                    <tbody>
                    <?php foreach ($results['requests'] as $row): ?>
                        <tr>
                            <td><?= e($row['plate_number']) ?></td>
                            <?php if (is_admin()): ?><td><?= e($row['full_name']) ?></td><?php endif; ?>
                            <td><?= e($row['usage_purpose']) ?></td>
                            <td><span class="<?= e(badge_class((string) $row['status'])) ?>"><?= e($row['status']) ?></span></td>
                            <td><a href="<?= e(app_url(is_admin() ? 'modules/requests/index.php' : 'personnel/requests.php')) ?>">Aç</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head"><h2>Arıza Sonuçları</h2></div>
        <div class="panel-body">
            <?php if (!$results['issues']): ?>
                <p class="empty-state">Arıza sonucu bulunamadı.</p>
            <?php else: ?>
                <table class="table">
                    <thead><tr><th>Araç</th><?php if (is_admin()): ?><th>Personel</th><?php endif; ?><th>Konu</th><th>Durum</th><th>İşlem</th></tr></thead>
                    <tbody>
                    <?php foreach ($results['issues'] as $row): ?>
                        <tr>
                            <td><?= e($row['plate_number']) ?></td>
                            <?php if (is_admin()): ?><td><?= e($row['full_name']) ?></td><?php endif; ?>
                            <td><?= e($row['subject']) ?></td>
                            <td><span class="<?= e(badge_class((string) $row['status'])) ?>"><?= e($row['status']) ?></span></td>
                            <td><a href="<?= e(app_url(is_admin() ? 'modules/issues/index.php' : 'personnel/issues.php')) ?>">Aç</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
