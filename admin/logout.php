<?php
/**
 * Admin logout — destroy the session and return to the login page.
 *
 * This page does NOT require admin, so an expired session simply bounces back to
 * the login form without error.
 */
require __DIR__ . '/../config/config.php';

// Clear all session variables.
$_SESSION = [];

// Delete the session cookie if cookies are in use.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'] ?? '/',
        $params['domain'] ?? '',
        $params['secure'] ?? false,
        $params['httponly'] ?? false
    );
}

session_destroy();
header('Location: ' . url('admin/login.php'));
exit;
