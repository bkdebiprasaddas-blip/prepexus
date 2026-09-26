<?php

// This file is a library. Reaching it directly over HTTP should never
// do anything useful.
if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename(__FILE__) === basename((string) $_SERVER['SCRIPT_FILENAME'])) {

    http_response_code(403);
    exit;
}

// ==========================================================
//  Prepexus - shared security layer
//
//  Included by every page (root and admin). Owns:
//    - the response security headers
//    - the hardened session bootstrap
//    - output escaping, CSRF, URL allow-listing
//    - login / admin access control
//    - the login throttle
//
//  Every declaration is guarded so a page can still be migrated
//  to this file gradually without a redeclare fatal error.
// ==========================================================

if (!defined('PREPEXUS_ROOT')) {
    define('PREPEXUS_ROOT', dirname(__DIR__));
}

define('LOGIN_THROTTLE_MAX', 5);         // failures allowed ...
define('LOGIN_THROTTLE_WINDOW', 900);    // ... within 15 minutes


// ==========================================================
//  RESPONSE HEADERS
// ==========================================================

if (!headers_sent()) {

    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}


// ==========================================================
//  SESSION BOOTSTRAP
//
//  The cookie is HttpOnly + SameSite=Lax, and Secure whenever the
//  request arrived over HTTPS. use_strict_mode rejects a
//  client-supplied session ID the server has never issued, which
//  removes session fixation as a class.
// ==========================================================

if (session_status() !== PHP_SESSION_ACTIVE) {

    $https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    // One cookie path for the whole app, so a session opened on a
    // root page is still visible to a page under admin/.
    $cookie_path = '/';
    $doc_root = str_replace('\\', '/', rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/'));
    $app_dir = str_replace('\\', '/', PREPEXUS_ROOT);

    if ($doc_root !== '' && strpos($app_dir . '/', $doc_root . '/') === 0) {
        $cookie_path = rtrim(substr($app_dir, strlen($doc_root)), '/') . '/';
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    // A distinct name avoids colliding with any other app sharing
    // this host's PHPSESSID cookie.
    session_name('PREPEXUSSESSID');

    session_set_cookie_params(array(
        'lifetime' => 0,
        'path' => $cookie_path,
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ));

    session_start();
}


// ==========================================================
//  OUTPUT ESCAPING
// ==========================================================

if (!function_exists('e')) {

    /**
     * Escape for an HTML text node or a quoted attribute.
     * ENT_QUOTES also covers single-quoted attributes, and
     * ENT_SUBSTITUTE prevents an unencodable byte from
     * returning an empty string.
     */
    function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}


if (!function_exists('css_slug')) {

    /**
     * Reduce a value to [a-z0-9-] so it is always safe to drop
     * into a class attribute without escaping.
     */
    function css_slug($value)
    {
        $slug = strtolower(trim((string) $value));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim((string) $slug, '-');
    }
}


if (!function_exists('initial')) {

    /**
     * First character of a name, for the avatar badge. Escaping happens
     * here so no call site can forget it.
     */
    function initial($name)
    {
        $name = trim((string) $name);

        if ($name === '') {
            return '';
        }

        if (function_exists('mb_substr')) {
            return e(mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8'));
        }

        return e(strtoupper(substr($name, 0, 1)));
    }
}


// ==========================================================
//  URL ALLOW-LIST
//
//  htmlspecialchars() does NOT neutralise a "javascript:" URI - it
//  only escapes HTML metacharacters. A stored link rendered into
//  href= must be scheme-checked, not just escaped.
// ==========================================================

if (!function_exists('safe_url')) {

    /**
     * Return the URL if it is an absolute http(s) URL or a
     * site-relative path. Return '' for everything else
     * (javascript:, data:, vbscript:, protocol-relative //host).
     */
    function safe_url($url)
    {
        $url = trim((string) $url);

        if ($url === '') {
            return '';
        }

        // Site-relative, but not protocol-relative: "//evil.tld" is
        // an absolute URL in disguise.
        if (preg_match('#^/(?!/)#', $url)) {
            return $url;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
            $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
            if ($scheme === 'http' || $scheme === 'https') {
                return $url;
            }
        }

        return '';
    }
}


if (!function_exists('is_valid_url')) {

    function is_valid_url($url)
    {
        return safe_url($url) !== '';
    }
}


// ==========================================================
//  CSRF
// ==========================================================

if (!function_exists('csrf_token')) {

    function csrf_token()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}


if (!function_exists('verify_csrf')) {

    function verify_csrf()
    {
        return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
            && is_string($_POST['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    }
}


if (!function_exists('rotate_csrf_token')) {

    function rotate_csrf_token()
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }
}


// ==========================================================
//  ACCESS CONTROL
// ==========================================================

if (!function_exists('is_logged_in')) {

    function is_logged_in()
    {
        return isset($_SESSION['user_id']);
    }
}


if (!function_exists('current_user_id')) {

    function current_user_id()
    {
        return is_logged_in() ? (int) $_SESSION['user_id'] : 0;
    }
}


if (!function_exists('require_login')) {

    /** Call from a page at the app root. */
    function require_login()
    {
        if (!is_logged_in()) {
            header('Location: login.php');
            exit();
        }
    }
}


if (!function_exists('require_admin')) {

    /**
     * Call from a page under admin/.
     *
     * The role in $_SESSION is only a cache. It is re-read from the
     * users row so that an admin who has just been demoted loses
     * access immediately rather than at their next login.
     */
    function require_admin($conn = null)
    {
        if (!is_logged_in()
            || !isset($_SESSION['user_role'])
            || $_SESSION['user_role'] !== 'admin') {

            header('Location: ../login.php');
            exit();
        }

        if ($conn instanceof mysqli) {

            $user_id = (int) $_SESSION['user_id'];

            $stmt = mysqli_prepare($conn, "SELECT role FROM users WHERE id = ? LIMIT 1");

            if ($stmt) {

                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);

                $result = mysqli_stmt_get_result($stmt);
                $row = $result ? mysqli_fetch_assoc($result) : null;

                mysqli_stmt_close($stmt);

                // Fail closed: unknown user, or no longer an admin.
                if (!$row || $row['role'] !== 'admin') {

                    $_SESSION = array();
                    session_regenerate_id(true);

                    header('Location: ../login.php');
                    exit();
                }

                $_SESSION['user_role'] = 'admin';
            }
        }
    }
}


// ==========================================================
//  OWNERSHIP
// ==========================================================

if (!function_exists('user_owns_subject')) {

    /**
     * Stops a student from attaching a material or task to a
     * subject owned by somebody else, which would otherwise leak
     * that subject's name through the JOIN used for listing.
     */
    function user_owns_subject($conn, $user_id, $subject_id)
    {
        // Both must be plain variables: mysqli_stmt_bind_param takes its
        // arguments by reference, so an inline (int) cast is a fatal error.
        $subject_id = (int) $subject_id;
        $user_id = (int) $user_id;

        if ($subject_id <= 0) {
            return false;
        }

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id FROM subjects WHERE id = ? AND user_id = ? LIMIT 1"
        );

        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "ii", $subject_id, $user_id);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;

        mysqli_stmt_close($stmt);

        return $row !== null;
    }
}


// ==========================================================
//  FIELD VALIDATION
// ==========================================================

if (!function_exists('normalize_email')) {

    function normalize_email($email)
    {
        return strtolower(trim((string) $email));
    }
}


if (!function_exists('is_valid_email')) {

    function is_valid_email($email)
    {
        return filter_var(normalize_email($email), FILTER_VALIDATE_EMAIL) !== false;
    }
}


if (!function_exists('is_valid_semester')) {

    function is_valid_semester($value)
    {
        return in_array((string) $value, array(
            '1st Semester', '2nd Semester', '3rd Semester',
            '4th Semester', '5th Semester', '6th Semester',
        ), true);
    }
}


if (!function_exists('is_valid_material_type')) {

    function is_valid_material_type($value)
    {
        return in_array((string) $value, array('PDF', 'Video', 'Website', 'Notes'), true);
    }
}


if (!function_exists('is_valid_priority')) {

    function is_valid_priority($value)
    {
        return in_array((string) $value, array('High', 'Medium', 'Low'), true);
    }
}


if (!function_exists('is_valid_status')) {

    function is_valid_status($value)
    {
        return in_array((string) $value, array('Pending', 'In Progress', 'Completed'), true);
    }
}


// ==========================================================
//  LOGIN THROTTLE
//
//  Without this, login.php accepts an unlimited number of guesses
//  against a known email address.
// ==========================================================

if (!function_exists('login_attempts_table_ready')) {

    function login_attempts_table_ready($conn)
    {
        static $ready = false;

        if ($ready) {
            return true;
        }

        // CREATE ... IF NOT EXISTS so a pre-existing database picks
        // the table up without needing the dump re-imported.
        $sql = "CREATE TABLE IF NOT EXISTS `login_attempts` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `email` varchar(100) NOT NULL,
                    `ip_address` varchar(45) NOT NULL,
                    `attempted_at` datetime NOT NULL,
                    `was_successful` tinyint(1) NOT NULL DEFAULT '0',
                    PRIMARY KEY (`id`),
                    KEY `email_ip_time` (`email`, `ip_address`, `attempted_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        $ready = (bool) mysqli_query($conn, $sql);

        return $ready;
    }
}


if (!function_exists('client_ip')) {

    function client_ip()
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return substr((string) $ip, 0, 45);
    }
}


if (!function_exists('login_is_locked')) {

    function login_is_locked($conn, $email, $ip)
    {
        if (!login_attempts_table_ready($conn)) {
            return false;
        }

        $since = date('Y-m-d H:i:s', time() - LOGIN_THROTTLE_WINDOW);

        $stmt = mysqli_prepare(
            $conn,
            "SELECT COUNT(*) AS fails
             FROM login_attempts
             WHERE email = ?
               AND ip_address = ?
               AND was_successful = 0
               AND attempted_at >= ?"
        );

        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "sss", $email, $ip, $since);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;

        mysqli_stmt_close($stmt);

        $fails = $row ? (int) $row['fails'] : 0;

        return $fails >= LOGIN_THROTTLE_MAX;
    }
}


if (!function_exists('record_login_attempt')) {

    function record_login_attempt($conn, $email, $ip, $success)
    {
        if (!login_attempts_table_ready($conn)) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $cutoff = date('Y-m-d H:i:s', time() - (LOGIN_THROTTLE_WINDOW * 4));

        // Housekeeping: never let the table grow without bound.
        $purge = mysqli_prepare($conn, "DELETE FROM login_attempts WHERE attempted_at < ?");

        if ($purge) {
            mysqli_stmt_bind_param($purge, "s", $cutoff);
            mysqli_stmt_execute($purge);
            mysqli_stmt_close($purge);
        }

        $flag = $success ? 1 : 0;

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO login_attempts (email, ip_address, attempted_at, was_successful)
             VALUES (?, ?, ?, ?)"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssi", $email, $ip, $now, $flag);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}


if (!function_exists('clear_login_attempts')) {

    function clear_login_attempts($conn, $email, $ip)
    {
        if (!login_attempts_table_ready($conn)) {
            return;
        }

        $stmt = mysqli_prepare(
            $conn,
            "DELETE FROM login_attempts WHERE email = ? AND ip_address = ?"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $email, $ip);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}
