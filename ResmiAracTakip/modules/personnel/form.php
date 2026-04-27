<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$person = $id ? fetch_one('SELECT * FROM personnel WHERE id = :id', ['id' => $id]) : null;

if ($id && !$person) {
    include __DIR__ . '/../../errors/404.php';
    exit;
}

$pageTitle = $person ? 'Personel Düzenle' : 'Yeni Personel Ekle';
$errors = [];

$availableUsers = fetch_all(
    "SELECT u.id, u.full_name, u.username
     FROM users u
     LEFT JOIN personnel p ON p.user_id = u.id
     WHERE u.role = 'personel'
       AND (p.id IS NULL OR p.id = :current_personnel_id)
     ORDER BY u.full_name",
    ['current_personnel_id' => $id]
);

if (is_post()) {
    verify_csrf();

    $rawUserId = trim((string) ($_POST['user_id'] ?? ''));
    $selectedUserId = ctype_digit($rawUserId) ? (int) $rawUserId : 0;

    $data = [
        'user_id' => $selectedUserId,
        'full_name' => trim((string) ($_POST['full_name'] ?? '')),
        'registration_no' => trim((string) ($_POST['registration_no'] ?? '')),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'department' => trim((string) ($_POST['department'] ?? '')),
        'duty_title' => trim((string) ($_POST['duty_title'] ?? '')),
        'license_class' => trim((string) ($_POST['license_class'] ?? '')),
        'status' => trim((string) ($_POST['status'] ?? 'aktif')),
    ];

    if ($data['full_name'] === '' || $data['registration_no'] === '' || $data['department'] === '' || $data['duty_title'] === '') {
        $errors[] = 'Ad soyad, sicil no, departman ve görev alanları zorunludur.';
    }

    if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'E-posta adresi geçerli biçimde girilmelidir.';
    }

    if ($data['status'] !== 'aktif' && $data['status'] !== 'pasif') {
        $errors[] = 'Durum bilgisi geçersiz.';
    }

    $duplicateRegistration = fetch_one(
        'SELECT id FROM personnel WHERE registration_no = :registration_no AND id != :id LIMIT 1',
        ['registration_no' => $data['registration_no'], 'id' => $id]
    );
    if ($duplicateRegistration) {
        $errors[] = 'Bu sicil numarası zaten kayıtlı.';
    }

    if ($data['user_id'] > 0) {
        $duplicateUserLink = fetch_one(
            'SELECT id FROM personnel WHERE user_id = :user_id AND id != :id LIMIT 1',
            ['user_id' => $data['user_id'], 'id' => $id]
        );
        if ($duplicateUserLink) {
            $errors[] = 'Seçilen kullanıcı zaten başka bir personel kaydına bağlı.';
        }

        $userRow = fetch_one(
            "SELECT id, role FROM users WHERE id = :id LIMIT 1",
            ['id' => $data['user_id']]
        );
        if (!$userRow || $userRow['role'] !== 'personel') {
            $errors[] = 'Seçilen kullanıcı personel rolünde olmalıdır.';
        }
    }

    if (!$errors) {
        $dbData = array_merge($data, ['user_id' => $data['user_id'] > 0 ? $data['user_id'] : null]);

        if ($person) {
            execute_query(
                'UPDATE personnel
                 SET user_id = :user_id,
                     full_name = :full_name,
                     registration_no = :registration_no,
                     phone = :phone,
                     email = :email,
                     department = :department,
                     duty_title = :duty_title,
                     license_class = :license_class,
                     status = :status
                 WHERE id = :id',
                $dbData + ['id' => $id]
            );
            log_activity('Personel güncellendi', 'Personel Yönetimi', $data['full_name'] . ' bilgileri güncellendi.');
            set_flash('success', 'Personel bilgileri güncellendi.');
        } else {
            execute_query(
                'INSERT INTO personnel
                 (user_id, full_name, registration_no, phone, email, department, duty_title, license_class, status)
                 VALUES
                 (:user_id, :full_name, :registration_no, :phone, :email, :department, :duty_title, :license_class, :status)',
                $dbData
            );
            log_activity('Personel eklendi', 'Personel Yönetimi', $data['full_name'] . ' sisteme eklendi.');
            set_flash('success', 'Yeni personel kaydı oluşturuldu.');
        }

        redirect('modules/personnel/index.php');
    }
}

include __DIR__ . '/../../includes/header.php';
?>
<section class="panel">
    <div class="panel-head"><h2><?= e($pageTitle) ?></h2></div>
    <div class="panel-body">
        <?php foreach ($errors as $error): ?>
            <div class="alert error"><span><?= e($error) ?></span></div>
        <?php endforeach; ?>

        <form accept-charset="UTF-8" class="form-grid" method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <label>Bağlı Kullanıcı (personel)
                <select name="user_id">
                    <option value="">Seçilmedi</option>
                    <?php foreach ($availableUsers as $user): ?>
                        <?php $selected = (int) ($_POST['user_id'] ?? $person['user_id'] ?? 0) === (int) $user['id'] ? 'selected' : ''; ?>
                        <option value="<?= e((string) $user['id']) ?>" <?= e($selected) ?>>
                            <?= e($user['full_name'] . ' (' . $user['username'] . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Ad Soyad
                <input type="text" name="full_name" value="<?= e($_POST['full_name'] ?? $person['full_name'] ?? '') ?>" required>
            </label>

            <label>TC / Kurum Sicil No
                <input type="text" name="registration_no" value="<?= e($_POST['registration_no'] ?? $person['registration_no'] ?? '') ?>" required>
            </label>

            <label>Telefon
                <input type="text" name="phone" value="<?= e($_POST['phone'] ?? $person['phone'] ?? '') ?>">
            </label>

            <label>E-posta
                <input type="email" name="email" value="<?= e($_POST['email'] ?? $person['email'] ?? '') ?>">
            </label>

            <label>Departman
                <input type="text" name="department" value="<?= e($_POST['department'] ?? $person['department'] ?? '') ?>" required>
            </label>

            <label>Görev
                <input type="text" name="duty_title" value="<?= e($_POST['duty_title'] ?? $person['duty_title'] ?? '') ?>" required>
            </label>

            <label>Sürücü Belgesi Sınıfı
                <input type="text" name="license_class" value="<?= e($_POST['license_class'] ?? $person['license_class'] ?? '') ?>">
            </label>

            <label>Durum
                <select name="status">
                    <?php foreach (['aktif', 'pasif'] as $status): ?>
                        <?php $selectedStatus = ($_POST['status'] ?? $person['status'] ?? 'aktif') === $status ? 'selected' : ''; ?>
                        <option value="<?= e($status) ?>" <?= e($selectedStatus) ?>><?= e(mb_convert_case($status, MB_CASE_TITLE, 'UTF-8')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div class="form-actions full-width">
                <button class="button primary" type="submit">Kaydet</button>
                <a class="button ghost" href="<?= e(app_url('modules/personnel/index.php')) ?>">Vazgeç</a>
            </div>
        </form>
    </div>
</section>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
