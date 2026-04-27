<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$pageTitle = 'Araç Yönetimi';
$canManage = is_admin();
$vehicles = fetch_all('SELECT * FROM vehicles ORDER BY created_at DESC');

include __DIR__ . '/../../includes/header.php';
?>
<section class="panel">
    <div class="panel-head">
        <h2>Araç Listesi</h2>
        <?php if ($canManage): ?>
            <a class="button primary" href="<?= e(app_url('modules/vehicles/form.php')) ?>">Yeni Araç Ekle</a>
        <?php endif; ?>
    </div>
    <div class="panel-body">
        <table class="table">
            <thead>
            <tr>
                <th>Plaka</th>
                <th>Marka / Model</th>
                <th>Yıl</th>
                <th>Yakıt</th>
                <th>Kilometre</th>
                <th>Durum</th>
                <th>İşlem</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($vehicles as $vehicle): ?>
                <tr>
                    <td><?= e($vehicle['plate_number']) ?></td>
                    <td><?= e($vehicle['brand'] . ' ' . $vehicle['model']) ?></td>
                    <td><?= e((string) $vehicle['model_year']) ?></td>
                    <td><?= e($vehicle['fuel_type']) ?></td>
                    <td><?= e(number_format((int) $vehicle['current_km'], 0, ',', '.')) ?> km</td>
                    <td><span class="<?= e(badge_class($vehicle['status'])) ?>"><?= e($vehicle['status']) ?></span></td>
                    <td class="actions">
                        <a href="<?= e(app_url('modules/vehicles/view.php?id=' . $vehicle['id'])) ?>">Detay</a>
                        <?php if ($canManage): ?>
                            <a href="<?= e(app_url('modules/vehicles/form.php?id=' . $vehicle['id'])) ?>">Düzenle</a>
                            <form accept-charset="UTF-8" method="post" action="<?= e(app_url('modules/vehicles/delete.php')) ?>" class="inline-form">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= e((string) $vehicle['id']) ?>">
                                <button class="link-button danger-link" type="submit" data-confirm="Bu araç kaydı silinsin mi?">Sil</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
