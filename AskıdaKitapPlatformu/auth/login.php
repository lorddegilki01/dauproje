<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = null;
if (is_post()) {
    verify_csrf();
    $username = normalize_text((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if ($username === '' || $password === '') {
        $error = 'Kullanıcı adı ve şifre zorunludur.';
    } else {
        $user = fetch_one('SELECT * FROM users WHERE username = :username AND is_active = 1 LIMIT 1', ['username' => $username]);
        if ($user && password_verify($password, (string) $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int) $user['id'],
                'full_name' => (string) $user['full_name'],
                'username' => (string) $user['username'],
                'role' => (string) $user['role'],
            ];
            log_activity((int) $user['id'], 'Kimlik', 'Giriş', 'Kullanıcı giriş yaptı.');
            redirect('index.php');
        }
        $error = 'Kullanıcı adı veya şifre hatalı.';
    }
}

$activeMenu = '';
$pageTitle = 'Giriş Yap';
require __DIR__ . '/../includes/header.php';
?>
<section class="card form-card">
    <h2>Hesabınıza Giriş Yapın</h2>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Kullanıcı Adı<input type="text" name="username" required></label>
        <label>Şifre<input type="password" name="password" required></label>
        <div class="actions">
            <button class="btn primary" type="submit">Giriş Yap</button>
            <a class="btn ghost" href="<?= e(app_url('auth/register.php')) ?>">Kayıt Ol</a>
        </div>
    </form>
    <div class="hint">
        Demo Admin: <b>admin / password</b> · Demo Kullanıcı: <b>okur / password</b>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

