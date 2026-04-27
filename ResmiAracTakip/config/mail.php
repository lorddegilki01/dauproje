<?php
declare(strict_types=1);

function rat_mail_config(): array
{
    $config = [
        'host' => GMAIL_SMTP_HOST,
        'port' => (int) GMAIL_SMTP_PORT,
        'encryption' => GMAIL_SMTP_ENCRYPTION,
        'username' => GMAIL_SMTP_USER,
        'password' => GMAIL_SMTP_PASS,
        'from_address' => MAIL_FROM_ADDRESS,
        'from_name' => MAIL_FROM_NAME,
        'timeout' => (int) SMTP_TIMEOUT_SECONDS,
    ];

    $envMap = [
        'host' => getenv('RAT_SMTP_HOST') ?: null,
        'port' => getenv('RAT_SMTP_PORT') ?: null,
        'encryption' => getenv('RAT_SMTP_ENCRYPTION') ?: null,
        'username' => getenv('RAT_SMTP_USER') ?: null,
        'password' => getenv('RAT_SMTP_PASS') ?: null,
        'from_address' => getenv('RAT_SMTP_FROM') ?: null,
        'from_name' => getenv('RAT_SMTP_FROM_NAME') ?: null,
        'timeout' => getenv('RAT_SMTP_TIMEOUT') ?: null,
    ];

    foreach ($envMap as $key => $value) {
        if ($value !== null && $value !== '') {
            $config[$key] = $value;
        }
    }

    $localFile = __DIR__ . '/mail.local.php';
    if (is_file($localFile)) {
        $localConfig = require $localFile;
        if (is_array($localConfig)) {
            $config = array_merge($config, $localConfig);
        }
    }

    $config['port'] = (int) $config['port'];
    $config['timeout'] = (int) $config['timeout'];

    return $config;
}
