<?php
declare(strict_types=1);

ini_set('default_charset', 'UTF-8');
date_default_timezone_set('Europe/Istanbul');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

const APP_NAME = 'Askıda Kitap Platformu';
const BASE_URL = '/DaüYarışma/AskıdaKitapPlatformu';
const SESSION_TIMEOUT = 7200;

const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'askida_kitap';
const DB_USER = 'root';
const DB_PASS = '';

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
    ]);

    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_turkish_ci");
    $pdo->exec("SET collation_connection = 'utf8mb4_turkish_ci'");

    return $pdo;
}

