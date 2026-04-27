<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
http_response_code(419);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 | Oturum Doğrulama Hatası</title>
    <link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>">
</head>
<body class="error-page">
<div class="error-card">
    <h1>419</h1>
    <p>İşlem doğrulanamadı. Lütfen sayfayı yenileyip tekrar deneyin.</p>
    <a class="button primary" href="<?= e(app_url('index.php')) ?>">Ana Sayfaya Dön</a>
</div>
</body>
</html>
