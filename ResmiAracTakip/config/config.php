<?php
declare(strict_types=1);

ini_set('default_charset', 'UTF-8');

$sessionPath = session_save_path() ?: sys_get_temp_dir();
if (!is_dir($sessionPath) || !is_writable($sessionPath)) {
    $fallbackSessionPath = __DIR__ . '/../storage/sessions';
    if (!is_dir($fallbackSessionPath)) {
        mkdir($fallbackSessionPath, 0777, true);
    }
    session_save_path($fallbackSessionPath);
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

date_default_timezone_set('Europe/Istanbul');

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}
mb_internal_encoding('UTF-8');

const APP_NAME = 'Resmi Araç Takip Sistemi';
const BASE_URL = '/DaüYarışma/ResmiAracTakip';
const SESSION_TIMEOUT = 7200;

const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'resmi_arac_takip';
const DB_USER = 'root';
const DB_PASS = '';

const MAIL_FROM_ADDRESS = 'noreply@resmiaractakip.local';
const MAIL_FROM_NAME = 'Resmi Araç Takip Sistemi';
const GMAIL_SMTP_HOST = 'smtp.gmail.com';
const GMAIL_SMTP_PORT = 465;
const GMAIL_SMTP_ENCRYPTION = 'ssl'; // ssl veya tls
const GMAIL_SMTP_USER = '';
const GMAIL_SMTP_PASS = '';
const SMTP_TIMEOUT_SECONDS = 20;
const MYSQLDUMP_PATH = '';

const PASSWORD_RESET_TOKEN_TTL_MINUTES = 15;
const PASSWORD_RESET_RESEND_COOLDOWN_SECONDS = 60;
const PASSWORD_RESET_MAX_ATTEMPTS_PER_HOUR = 5;

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_turkish_ci",
    ]);

    $pdo->exec("SET CHARACTER SET utf8mb4");
    $pdo->exec("SET collation_connection = 'utf8mb4_turkish_ci'");

    return $pdo;
}
