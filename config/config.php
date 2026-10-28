<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'rch_uiu');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME',    'Research Collaboration Hub UIU');
define('SITE_TAGLINE', 'Empowering Scholarly Growth');

define('ROOT_PATH',   dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads');

if (!defined('BASE_URL')) {
    $docRoot = str_replace('\\', '/', rtrim((string)realpath($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\'));
    $appDir  = str_replace('\\', '/', rtrim((string)realpath(ROOT_PATH), '/\\'));
    if ($docRoot !== '' && strpos($appDir, $docRoot) === 0) {
        $base = substr($appDir, strlen($docRoot));
    } else {

        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $base   = rtrim(dirname($script), '/');
        if (substr($base, -6) === '/admin') { $base = substr($base, 0, -6); }
    }
    define('BASE_URL', rtrim($base, '/') . '/');
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

date_default_timezone_set('Asia/Dhaka');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';
require_once ROOT_PATH . '/includes/functions.php';
