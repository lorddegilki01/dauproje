<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect_home_by_role();
}

$error = null;

if (!password_reset_schema_ready()) {
    $error = 'Şifre yenileme modülü için veritabanı güncellemesi gerekiyor. Lütfen güncel SQL dosyasını içe aktarın.';
}

if (is_post() && $error === null) {
    verify_csrf();

    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')), 'UTF-8');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir e-posta adresi giriniz.';
    } else {
        $user = fetch_one(
            'SELECT id, full_name, username, email FROM users WHERE email = :email AND is_active = 1 LIMIT 1',
            ['email' => $email]
        );

        $limit = secure_password_reset_request_is_allowed($user);
        if ($limit['allowed'] === true) {
            log_security_event(
                'password_reset_requested',
                'success',
                $user ? (int) $user['id'] : null,
                $email,
                'Şifre sıfırlama talebi alındı.'
            );
            if ($user) {
                $token = secure_create_password_reset_token($user);
                $resetUrl = app_full_url('auth/reset_password.php?token=' . urlencode($token));
                $sent = secure_send_password_reset_email((string) $user['email'], (string) $user['full_name'], $resetUrl);

                if (!$sent) {
                    log_security_event('password_reset_failed', 'failed', (int) $user['id'], (string) $user['email'], 'SMTP gönderimi başarısız.');
                    error_log('Şifre sıfırlama e-postası gönderilemedi: ' . (string) $user['email']);
                } else {
                    log_security_event('password_reset_sent', 'success', (int) $user['id'], (string) $user['email'], 'Sıfırlama e-postası gönderildi.');
                }
            }
            secure_register_password_reset_attempt();
            set_flash('success', 'E-posta sistemde kayıtlıysa şifre sıfırlama bağlantısı gönderildi.');
            redirect('auth/login.php');
        }

        log_security_event(
            'password_reset_requested',
            'blocked',
            $user ? (int) $user['id'] : null,
            $email,
            'Rate limit nedeniyle engellendi.'
        );
        set_flash('warning', 'Yeni bağlantı için lütfen ' . (int) $limit['retry_after'] . ' saniye bekleyin.');
        redirect('auth/forgot_password.php');
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifremi Unuttum | <?= e(APP_NAME) ?></title>
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
        <h1>Şifremi Unuttum</h1>
        <p>Kayıtlı e-posta adresinizi girin. Size tek kullanımlık şifre sıfırlama bağlantısı gönderelim.</p>
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
        <label>E-posta
            <input type="email" name="email" maxlength="120" value="<?= old('email') ?>" required autocomplete="email">
        </label>
        <button class="button primary full-width" type="submit">Sıfırlama Bağlantısı Gönder</button>
    </form>

    <div class="login-actions">
        <a href="<?= e(app_url('auth/login.php')) ?>">Giriş ekranına dön</a>
    </div>
</div>
</body>
</html>
