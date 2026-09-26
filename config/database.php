<?php

// ==========================================================
//  Database connection
//
//  Credentials are resolved in this order:
//    1. environment variables
//    2. a .env file sitting next to this file
//    3. local development defaults
//
//  This file lives inside the webroot. config/.htaccess denies
//  every HTTP request to this directory - do not relax that rule.
// ==========================================================

if (!function_exists('prepexus_load_env')) {

    /**
     * Minimal KEY=VALUE reader so the .env file is not committed.
     */
    function prepexus_load_env($path)
    {
        if (!is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {

            $line = trim($line);

            if ($line === '' || $line[0] === '#') {
                continue;
            }

            if (strpos($line, '=') === false) {
                continue;
            }

            list($key, $value) = explode('=', $line, 2);

            $key = trim($key);
            $value = trim($value);

            // strip one layer of matching quotes
            if (strlen($value) >= 2) {
                $first = $value[0];
                if (($first === '"' || $first === "'") && substr($value, -1) === $first) {
                    $value = substr($value, 1, -1);
                }
            }

            if ($key !== '' && getenv($key) === false) {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
            }
        }
    }
}


if (!function_exists('prepexus_env')) {

    function prepexus_env($key, $default)
    {
        $value = getenv($key);
        return ($value === false || $value === '') ? $default : $value;
    }
}


prepexus_load_env(__DIR__ . '/.env');


$host     = prepexus_env('DB_HOST', 'localhost');
$username = prepexus_env('DB_USER', 'root');
$password = prepexus_env('DB_PASS', '');
$database = prepexus_env('DB_NAME', 'prepexus');


mysqli_report(MYSQLI_REPORT_OFF);


$conn = @mysqli_connect($host, $username, $password, $database);

if (!$conn) {

    // Full detail goes to the server log only. The browser must not
    // learn the error number, driver message, or host it tried.
    error_log(
        'Prepexus: database connection failed (errno ' . mysqli_connect_errno() . '): '
        . mysqli_connect_error()
    );

    http_response_code(503);
    exit('Service temporarily unavailable. Please try again later.');

}


mysqli_set_charset($conn, 'utf8mb4');
