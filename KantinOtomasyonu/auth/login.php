<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/functions.php';

if (is_logged_in()) {
    redirect('index.php');
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
            update_session_user($user);
            redirect('index.php');
        }
        $error = 'Kullanıcı adı veya şifre hatalı.';
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş | <?= e(APP_NAME) ?></title>
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('kantin_theme');
                var fallback = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                document.documentElement.dataset.theme = stored || fallback;
            } catch (e) {
                document.documentElement.dataset.theme = 'dark';
            }
        })();
    </script>
    <link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>">
</head>
<body class="login-body">
<button class="icon-btn theme-btn login-theme-toggle" id="themeToggleBtn" type="button" aria-label="Tema değiştir">
    <span class="theme-icon">🌙</span>
    <span class="theme-label">Koyu</span>
</button>

<div class="login-ambient" aria-hidden="true">
    <span class="login-beam beam-a" data-depth="0.2"></span>
    <span class="login-beam beam-b" data-depth="0.18"></span>
    <span class="login-orb orb-a" data-depth="0.26"></span>
    <span class="login-orb orb-b" data-depth="0.21"></span>
    <span class="login-orb orb-c" data-depth="0.16"></span>
    <span class="login-ring ring-a" data-depth="0.12"></span>
    <span class="login-ring ring-b" data-depth="0.08"></span>
    <span class="login-particles" data-depth="0.06"></span>
    <span class="login-glints" data-depth="0.1"></span>
    <span class="login-wave" data-depth="0.05"></span>
    <span class="login-lens" data-depth="0.14"></span>
</div>

<main class="login-card">
    <div class="login-brand">
        <div class="logo">KO</div>
        <div>
            <h1>Kantin Otomasyon Sistemi</h1>
            <p>Kantin operasyonları için güvenli giriş yapın.</p>
        </div>
    </div>

    <?php $flash = get_flash(); ?>
    <?php if ($flash): ?><div class="alert <?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

    <form method="post" class="form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Kullanıcı Adı
            <input type="text" name="username" autocomplete="username" required>
        </label>
        <label>Şifre
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button class="btn primary" type="submit">Giriş Yap</button>
    </form>

    <p class="hint">Demo Admin: <strong>admin / password</strong><br>Demo Kasiyer: <strong>kasiyer / password</strong></p>
</main>

<script src="<?= e(app_url('assets/js/app.js')) ?>"></script>
</body>
</html>
