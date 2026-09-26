<?php

require_once __DIR__ . "/includes/security.php";

// Logout is reached from a plain link, so it carries the CSRF token in the
// query string. Without this, any third-party page can log the user out by
// pointing an <img> at logout.php.
$token = $_GET['token'] ?? '';

$token_ok = is_string($token)
    && isset($_SESSION['csrf_token'])
    && hash_equals($_SESSION['csrf_token'], $token);

if ($token_ok) {

    // Clear the session data, expire the cookie, then destroy the record.
    $_SESSION = array();

    if (ini_get('session.use_cookies')) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

header('Location: login.php');
exit();
