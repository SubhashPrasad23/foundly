<?php
declare(strict_types=1);

date_default_timezone_set('UTC');

define('APP_ROOT', dirname(__DIR__));
define('APP_BASE_PATH', '/lost-and-found');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once APP_ROOT . '/config/database.php';
require_once __DIR__ . '/helpers.php';
