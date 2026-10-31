<?php
/**
 * lang.php — Language switch endpoint.
 *
 * Sets $_SESSION['lang'] and redirects back.
 * Accepts ?lang=en|am  and optional ?redirect=<url>
 */
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Validate and store language
$requestedLang = $_GET['lang'] ?? 'en';
$_SESSION['lang'] = in_array($requestedLang, ['en', 'am'], true) ? $requestedLang : 'en';

// Determine trusted host
$host = $_SERVER['SERVER_NAME'] ?? ($_SERVER['HTTP_HOST'] ?? '');

// Prefer explicit ?redirect= param (used by admin toggle)
$redirect = '';
if (!empty($_GET['redirect'])) {
    $candidate = $_GET['redirect'];
    $candidateHost = parse_url($candidate, PHP_URL_HOST);
    // Accept if same host, or if it's a path-only URL (no host)
    if ($candidateHost === null || $candidateHost === $host) {
        $redirect = $candidate;
    }
}

// Fall back to HTTP_REFERER
if ($redirect === '' && !empty($_SERVER['HTTP_REFERER'])) {
    $refHost = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    if ($refHost === $host) {
        $redirect = $_SERVER['HTTP_REFERER'];
    }
}

// Final fallback: app root index
if ($redirect === '') {
    $scriptBase = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/lang.php'), '/');
    if ($scriptBase === '' || str_contains($scriptBase, '..')) {
        $scriptBase = '';
    }
    $redirect = $scriptBase . '/index.php';
}

// Strip CR/LF to prevent header injection
$redirect = str_replace(["\r", "\n"], '', $redirect);

header('Location: ' . $redirect);
exit;
