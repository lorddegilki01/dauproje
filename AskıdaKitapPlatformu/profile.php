<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_login();

$activeMenu = '';
$pageTitle = 'Profilim';
$user = current_user();
$errors = [];

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'profile');

    if ($action === 'profile') {
        $fullName = normalize_text((string) ($_POST['full_name'] ?? ''));
        $email = mb_strtolower(normalize_text((string) ($_POST['email'] ?? '')), 'UTF-8');
        $city = normalize_text((string) ($_POST['city'] ?? ''));
        $phone = normalize_text((string) ($_POST['phone'] ?? ''));

        if ($fullName === '' || $email === '' || $city === '') {
            $errors[] = 'Ad soyad, e-posta ve şehir alanları zorunludur.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Geçerli bir e-posta adresi girin.';
        }
        $duplicate = fetch_one('SELECT id FROM users WHERE email = :email AND id != :id', [
            'email' => $email,
            'id' => (int) $user['id'],
        ]);
        if ($duplicate) {
            $errors[] = 'Bu e-posta adresi başka bir hesapta kullanılıyor.';
        }

        if (!$errors) {
            execute_query(
                'UPDATE users SET full_name=:full_name, email=:email, city=:city, phone=:phone, updated_at=NOW() WHERE id=:id',
                [
                    'full_name' => $fullName,
                    'email' => $email,
                    'city' => $city,
                    'phone' => $phone,
                    'id' => (int) $user['id'],
                ]
            );
            $_SESSION['user']['full_name'] = $fullName;
            set_flash('success', 'Profil bilgileriniz güncellendi.');
            redirect('profile.php');
        }
    }

    if ($action === 'password') {
        $oldPassword = (string) ($_POST['old_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $newPasswordAgain = (string) ($_POST['new_password_again'] ?? '');
        $dbUser = fetch_one('SELECT id, password_hash FROM users WHERE id = :id', ['id' => (int) $user['id']]);

        if (!$dbUser || !password_verify($oldPassword, (string) $dbUser['password_hash'])) {
            $errors[] = 'Mevcut şifre hatalı.';
        }
        if ($newPassword !== $newPasswordAgain) {
            $errors[] = 'Yeni şifre alanları eşleşmiyor.';
        }
        if (mb_strlen($newPassword, 'UTF-8') < 8) {
            $errors[] = 'Yeni şifre en az 8 karakter olmalıdır.';
        }

        if (!$errors) {
            execute_query(
                'UPDATE users SET password_hash=:password_hash, updated_at=NOW() WHERE id=:id',
                [
                    'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                    'id' => (int) $user['id'],
                ]
            );
            log_activity((int) $user['id'], 'Profil', 'Şifre güncelleme', 'Kullanıcı şifresini güncelledi.');
            set_flash('success', 'Şifreniz başarıyla güncellendi.');
            redirect('profile.php');
        }
    }
}

$myStats = [
    'my_books' => count_value('SELECT COUNT(*) FROM books WHERE donor_user_id = :id', ['id' => (int) $user['id']]),
    'my_requests' => count_value('SELECT COUNT(*) FROM book_requests WHERE requester_user_id = :id', ['id' => (int) $user['id']]),
    'waiting_requests' => count_value(
        'SELECT COUNT(*) FROM book_requests WHERE requester_user_id = :id AND request_status = "bekliyor"',
        ['id' => (int) $user['id']]
    ),
    'delivered' => count_value(
        'SELECT COUNT(*) FROM matches WHERE (donor_user_id=:id OR requester_user_id=:id) AND delivery_status = "teslim edildi"',
        ['id' => (int) $user['id']]
    ),
];

$userRow = fetch_one('SELECT * FROM users WHERE id = :id', ['id' => (int) $user['id']]);
require __DIR__ . '/includes/header.php';
?>
<section class="stats-grid">
    <article class="card stat"><h3>Eklediğim Kitap</h3><strong><?= e((string) $myStats['my_books']) ?></strong></article>
    <article class="card stat"><h3>Talep Ettiğim</h3><strong><?= e((string) $myStats['my_requests']) ?></strong></article>
    <article class="card stat"><h3>Onay Bekleyen</h3><strong><?= e((string) $myStats['waiting_requests']) ?></strong></article>
    <article class="card stat"><h3>Teslim Edilen</h3><strong><?= e((string) $myStats['delivered']) ?></strong></article>
</section>

<section class="card">
    <h2>Profil Bilgileri</h2>
    <?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
    <form method="post" class="form grid-2">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="profile">
        <label>Ad Soyad<input type="text" name="full_name" required value="<?= e((string) ($userRow['full_name'] ?? '')) ?>"></label>
        <label>E-posta<input type="email" name="email" required value="<?= e((string) ($userRow['email'] ?? '')) ?>"></label>
        <label>Şehir<input type="text" name="city" required value="<?= e((string) ($userRow['city'] ?? '')) ?>"></label>
        <label>Telefon<input type="text" name="phone" value="<?= e((string) ($userRow['phone'] ?? '')) ?>"></label>
        <label class="full">Rol<input type="text" disabled value="<?= e((string) ($userRow['role'] ?? 'kullanıcı')) ?>"></label>
        <div class="full actions"><button class="btn primary" type="submit">Bilgileri Güncelle</button></div>
    </form>
</section>

<section class="card">
    <h2>Şifre Güncelle</h2>
    <form method="post" class="form grid-2">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="password">
        <label>Mevcut Şifre<input type="password" name="old_password" required></label>
        <label>Yeni Şifre<input type="password" name="new_password" required></label>
        <label class="full">Yeni Şifre (Tekrar)<input type="password" name="new_password_again" required></label>
        <div class="full actions"><button class="btn primary" type="submit">Şifreyi Güncelle</button></div>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

