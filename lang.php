<?php
/**
 * lang.php — Language switch endpoint.
 *
 * Sets $_SESSION['lang'] and redirects back to the referring page.
 * Does NOT include config/config.php to avoid double session start.
 *
 * Usage: /lang.php?lang=am   or   /lang.php?lang=en
 */

// Start session with same cookie params as config/config.php uses.
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Validate and store the requested language.
$requestedLang = $_GET['lang'] ?? 'en';
$lang = in_array($requestedLang, ['en', 'am'], true) ? $requestedLang : 'en';
$_SESSION['lang'] = $lang;

// Build a safe redirect target.
// Use HTTP_REFERER only if it shares the same host as the current request.
// NOTE: HTTP_HOST can theoretically be forged on proxied deployments.
// SERVER_NAME is set by the web-server config (Apache ServerName / php -S host)
// and is harder to spoof; use it as the trusted anchor when available.
$host    = $_SERVER['SERVER_NAME'] ?? ($_SERVER['HTTP_HOST'] ?? '');
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$redirect = '';

if ($referer !== '' && $host !== '') {
    $refHost = parse_url($referer, PHP_URL_HOST);
    if ($refHost === $host) {
        $redirect = $referer;
    }
}

if ($redirect === '') {
    // Safe fallback: use SCRIPT_NAME (server-normalized, no query string, no
    // traversal sequences) rather than REQUEST_URI which an upstream proxy may
    // pass unsanitized.  SCRIPT_NAME always points to this exact file, so its
    // dirname is the app root path under the web root (e.g. /Simedalis).
    $scriptBase = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/lang.php'), '/');
    // Sanity-check: must start with '/' and contain no '..' components.
    if ($scriptBase === '' || str_contains($scriptBase, '..')) {
        $scriptBase = '';
    }
    $redirect = $scriptBase . '/index.php';
}

// Strip CR/LF to prevent HTTP response header injection via a crafted Referer.
$redirect = str_replace(["\r", "\n"], '', $redirect);

header('Location: ' . $redirect);
exit;
