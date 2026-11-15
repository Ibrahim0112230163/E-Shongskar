<?php
/**
 * E-Shongskar application configuration.
 * Edit the database settings below to match your XAMPP / MySQL setup.
 */

define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'e_shongskar');
define('DB_USER', 'root');
define('DB_PASS', '');

define('APP_NAME', 'E-Shongskar');
define('APP_TAGLINE', "Let's reform together");
define('APP_TIMEZONE', 'Asia/Dhaka');

// Uploads
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads');
define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024); // 5 MB

// A user counts as "online" if active within this many minutes
define('ONLINE_WINDOW_MINUTES', 10);

date_default_timezone_set(APP_TIMEZONE);

// Show errors while developing on XAMPP. Set to 0 on a live server.
ini_set('display_errors', '1');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

/*
 * Base URL of the app (e.g. "/E-Shongskar"), worked out from the running
 * script so the project works from any folder inside htdocs. Pages in
 * /admin and /api live one level deeper, so that segment is stripped.
 */
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$scriptDir = preg_replace('#/(admin|api)$#', '', $scriptDir);
define('BASE_URL', rtrim($scriptDir, '/'));
