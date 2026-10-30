<?php
/**
 * Simedalis — central bootstrap.
 *
 * Defines ONLY constants (DB_*, BASE_PATH) and starts the session. It defines
 * NO helper functions — every shared helper lives in includes/functions.php
 * (included below), declared exactly once, so including this file more than
 * once can never trigger a "Cannot redeclare function" fatal.
 *
 * Credentials are read at runtime from backend/.env (the same file the Node
 * backend uses), so this file carries no secrets of its own and can be tracked.
 */

// --- Database credentials (from backend/.env, with safe fallbacks) ----------
$envFile = dirname(__DIR__) . '/backend/.env';
$env = is_readable($envFile) ? (parse_ini_file($envFile, false, INI_SCANNER_RAW) ?: []) : [];
define('DB_HOST', $env['DB_HOST'] ?? '127.0.0.1');
define('DB_PORT', $env['DB_PORT'] ?? '3306');
define('DB_NAME', $env['DB_NAME'] ?? 'gospel_team');
define('DB_USER', $env['DB_USER'] ?? 'root');
define('DB_PASS', $env['DB_PASSWORD'] ?? '');

// --- Site base path ---------------------------------------------------------
// Under XAMPP (Apache) the project lives under htdocs, so URLs need the
// "/Simedalis" prefix; under the built-in server (php -S -t <root>) the project
// IS the document root, so the prefix is empty. Compute it from the app root
// minus the document root.
//
// NOTE: $_SERVER['DOCUMENT_ROOT'] is the empty STRING in CLI mode, so a plain
// ?? fallback is not enough — an explicit empty-string check is used. The
// comparison is case-insensitive (stripos) because Windows paths are
// case-insensitive and the drive-letter case can differ between Apache's
// DocumentRoot and PHP's __DIR__.
$docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
if ($docRoot === '') {
    $docRoot = 'C:/xampp/htdocs';
}
$docRoot = str_replace('\\', '/', rtrim($docRoot, '/'));
$appRoot = str_replace('\\', '/', dirname(__DIR__)); // Simedalis root
$base = (stripos($appRoot, $docRoot) === 0) ? substr($appRoot, strlen($docRoot)) : '';
define('BASE_PATH', $base === '' ? '' : rtrim($base, '/'));

// --- Helpers (declared exactly once) then the DB connection ------------------
require_once __DIR__ . '/../includes/functions.php'; // e(), url(), csrf_*, image_url(), validate_upload(), require_admin(), ...
require_once __DIR__ . '/db.php';                     // db(): lazy PDO singleton

// --- Session (httponly + SameSite=Lax) ---------------------------------------
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

// --- Language support (must be loaded AFTER session_start) -----------------
require_once __DIR__ . '/../includes/lang.php';
