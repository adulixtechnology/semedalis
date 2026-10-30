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
$host    = $_SERVER['HTTP_HOST'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$redirect = '';

if ($referer !== '' && $host !== '') {
    $refHost = parse_url($referer, PHP_URL_HOST);
    if ($refHost === $host) {
        $redirect = $referer;
    }
}

if ($redirect === '') {
    // Safe fallback: index.php relative to this script's directory.
    $base = rtrim(dirname($_SERVER['REQUEST_URI'] ?? '/lang.php'), '/');
    $redirect = $base . '/index.php';
}

header('Location: ' . $redirect);
exit;
