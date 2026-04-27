<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect_home_by_role();
}

$error = null;

if (is_post()) {
    verify_csrf();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Kullanıcı adı ve şifre zorunludur.';
    } else {
        $user = fetch_one(
            'SELECT * FROM users WHERE username = :username AND is_active = 1 LIMIT 1',
            ['username' => $username]
        );

        if ($user && password_verify($password, (string) $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int) $user['id'],
                'full_name' => (string) $user['full_name'],
                'username' => (string) $user['username'],
                'role' => (string) $user['role'],
            ];

            log_activity('Giriş başarılı', 'Kimlik Doğrulama', (string) $user['username'] . ' oturum açtı.');
            redirect(role_home_path((string) $user['role']));
        }

        $error = 'Kullanıcı adı veya şifre hatalı.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap | <?= e(APP_NAME) ?></title>
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('rat_theme');
                var theme = (saved === 'light' || saved === 'dark') ? saved : 'dark';
                document.documentElement.setAttribute('data-theme', theme);
            } catch (err) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
    <link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>">
</head>
<body class="login-page login-page-animated">
<div class="login-bg" aria-hidden="true">
    <span class="blob blob-1"></span>
    <span class="blob blob-2"></span>
    <span class="blob blob-3"></span>
</div>

<div class="login-card">
    <div class="login-head">
        <h1>Resmi Araç Takip Sistemi</h1>
        <p>Kurum araçlarını güvenli ve düzenli biçimde yönetin.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert error"><span><?= e($error) ?></span></div>
    <?php endif; ?>

    <?php $flash = get_flash(); ?>
    <?php if ($flash): ?>
        <div class="alert <?= e((string) $flash['type']) ?>"><span><?= e((string) $flash['message']) ?></span></div>
    <?php endif; ?>

    <form method="post" accept-charset="UTF-8" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Kullanıcı Adı
            <input type="text" name="username" maxlength="60" value="<?= old('username') ?>" required autocomplete="username">
        </label>
        <label>Şifre
            <input type="password" name="password" maxlength="255" required autocomplete="current-password">
        </label>
        <button class="button primary full-width" type="submit">Giriş Yap</button>
    </form>

    <div class="login-actions">
        <a href="<?= e(app_url('auth/forgot_password.php')) ?>">Şifremi Unuttum</a>
    </div>

    <div class="login-note">
        <strong>Demo Yönetici:</strong> admin / password<br>
        <strong>Demo Personel:</strong> personel / password
    </div>
</div>
</body>
</html>
