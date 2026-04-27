<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 | Sunucu Hatası</title>
    <link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>">
</head>
<body class="error-page">
<div class="error-card">
    <h1>500</h1>
    <p>Beklenmeyen bir sunucu hatası oluştu.</p>
    <?php if (!empty($dbErrorMessage)): ?>
        <p class="muted"><?= e($dbErrorMessage) ?></p>
    <?php endif; ?>
    <a class="button primary" href="<?= e(app_url('index.php')) ?>">Dashboard'a Dön</a>
</div>
</body>
</html>
