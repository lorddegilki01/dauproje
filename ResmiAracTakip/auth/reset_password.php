<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect_home_by_role();
}

if (!password_reset_schema_ready()) {
    set_flash('error', 'Şifre yenileme modülü için veritabanı güncellemesi gerekiyor.');
    redirect('auth/login.php');
}

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$resetRecord = find_valid_reset_record($token);

if (!$resetRecord) {
    set_flash('error', 'Şifre sıfırlama bağlantısı geçersiz veya süresi dolmuş.');
    redirect('auth/login.php');
}

$errors = [];

if (is_post()) {
    verify_csrf();

    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
    $errors = secure_password_strength_messages($password);

    if ($password !== $passwordConfirm) {
        $errors[] = 'Yeni şifre ve tekrar alanı eşleşmiyor.';
    }

    if (!$errors) {
        execute_query(
            'UPDATE users SET password_hash = :password_hash WHERE id = :id',
            [
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'id' => (int) $resetRecord['account_id'],
            ]
        );

        mark_reset_token_used((int) $resetRecord['id']);
        secure_invalidate_password_reset_tokens((int) $resetRecord['account_id']);
        log_security_event(
            'password_changed',
            'success',
            (int) $resetRecord['account_id'],
            null,
            'Şifre sıfırlama bağlantısı ile parola değiştirildi.'
        );

        set_flash('success', 'Şifreniz başarıyla güncellendi. Yeni şifrenizle giriş yapabilirsiniz.');
        redirect('auth/login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Şifre Belirle | <?= e(APP_NAME) ?></title>
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
        <h1>Yeni Şifre Belirle</h1>
        <p>Güçlü bir şifre seçin. Bağlantı tek kullanımlıktır.</p>
    </div>

    <?php foreach ($errors as $error): ?>
        <div class="alert error"><span><?= e($error) ?></span></div>
    <?php endforeach; ?>

    <form method="post" accept-charset="UTF-8" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="token" value="<?= e($token) ?>">

        <label>Yeni Şifre
            <div class="password-wrap">
                <input id="new-password" type="password" name="password" maxlength="255" required autocomplete="new-password">
                <button type="button" class="password-toggle" data-toggle-password="#new-password">Göster</button>
            </div>
        </label>

        <label>Yeni Şifre (Tekrar)
            <div class="password-wrap">
                <input id="new-password-confirm" type="password" name="password_confirm" maxlength="255" required autocomplete="new-password">
                <button type="button" class="password-toggle" data-toggle-password="#new-password-confirm">Göster</button>
            </div>
        </label>

        <div class="password-strength" id="password-strength" aria-live="polite">Şifre gücü: -</div>
        <button class="button primary full-width" type="submit">Şifreyi Güncelle</button>
    </form>

    <div class="login-actions">
        <a href="<?= e(app_url('auth/login.php')) ?>">Giriş ekranına dön</a>
    </div>
</div>

<script>
    (function () {
        var input = document.getElementById('new-password');
        var strength = document.getElementById('password-strength');
        var toggles = document.querySelectorAll('[data-toggle-password]');

        function calculateScore(value) {
            var score = 0;
            if (value.length >= 10) score++;
            if (/[A-ZÇĞİÖŞÜ]/.test(value)) score++;
            if (/[a-zçğıöşü]/.test(value)) score++;
            if (/\d/.test(value)) score++;
            if (/[^A-Za-z0-9çğıöşüÇĞİÖŞÜ]/.test(value)) score++;
            return score;
        }

        function updateStrength() {
            var value = input.value || '';
            var score = calculateScore(value);
            var text = 'Şifre gücü: ';
            if (score <= 1) text += 'Zayıf';
            else if (score <= 3) text += 'Orta';
            else text += 'Güçlü';
            strength.textContent = text;
            strength.classList.toggle('is-strong', score >= 4);
        }

        input.addEventListener('input', updateStrength);
        updateStrength();

        toggles.forEach(function (button) {
            button.addEventListener('click', function () {
                var target = document.querySelector(button.getAttribute('data-toggle-password'));
                if (!target) return;
                var show = target.type === 'password';
                target.type = show ? 'text' : 'password';
                button.textContent = show ? 'Gizle' : 'Göster';
            });
        });
    })();
</script>
</body>
</html>
