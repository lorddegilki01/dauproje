<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
http_response_code(403);

$homePath = is_logged_in()
    ? role_home_path((string) (current_user()['role'] ?? 'personel'))
    : 'auth/login.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 | Yetkisiz Erişim</title>
    <link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>">
</head>
<body class="error-page">
<div class="error-card">
    <h1>403</h1>
    <p>Bu sayfaya erişim yetkiniz bulunmuyor.</p>
    <a class="button primary" href="<?= e(app_url($homePath)) ?>">Panele Dön</a>
</div>
</body>
</html>
