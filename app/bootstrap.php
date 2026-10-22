<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('APP_VERSION', '1.2.1');
define('APP_NAME', 'Plataforma de Accionistas CONSA');

if (!is_file(APP_ROOT . '/config.php')) {
    header('Location: install.php');
    exit;
}
$CONFIG = require APP_ROOT . '/config.php';
date_default_timezone_set($CONFIG['timezone'] ?? 'America/Santiago');
define('STORAGE', rtrim($CONFIG['storage_path'] ?? (APP_ROOT . '/storage'), '/'));
define('BASE_URL', rtrim($CONFIG['base_url'] ?? '', '/'));

ini_set('display_errors', !empty($CONFIG['debug']) ? '1' : '0');
error_reporting(E_ALL);

require __DIR__ . '/helpers.php';
require __DIR__ . '/SimplePdf.php';
require __DIR__ . '/SimpleXlsx.php';

// Sesión segura
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_name('CNACCSESS');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
session_start();

// Expiración por inactividad (minutos)
$idle = (int)($CONFIG['session_idle_minutes'] ?? 30);
if (isset($_SESSION['uid'], $_SESSION['last']) && time() - $_SESSION['last'] > $idle * 60) {
    $_SESSION = [];
    session_destroy();
    session_start();
    $_SESSION['flash'][] = ['warn', 'Su sesión expiró por inactividad. Ingrese nuevamente.'];
}
$_SESSION['last'] = time();

// Cabeceras de seguridad
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store, private, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
if ($secure) header('Strict-Transport-Security: max-age=31536000');

function db(): PDO
{
    static $pdo = null;
    global $CONFIG;
    if ($pdo === null) {
        $d = $CONFIG['db'];
        $dsn = 'mysql:host=' . $d['host'] . ';port=' . ($d['port'] ?? 3306) . ';dbname=' . $d['name'] . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $d['user'], $d['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '" . date('P') . "'");
    }
    return $pdo;
}
